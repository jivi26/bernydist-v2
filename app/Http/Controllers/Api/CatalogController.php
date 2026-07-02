<?php

namespace App\Http\Controllers\Api;

use App\Enums\PriceList;
use App\Http\Controllers\Controller;
use App\Models\Legacy\Category;
use App\Models\Legacy\PrecioArticulo;
use App\Models\Legacy\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    private const ROL_IMAGEN_PRINCIPAL = 104183870;
    private const CDN_URL = 'https://berny.mx/uploads/products/400/';

    public function products(Request $request): JsonResponse
    {
        $priceListId = PriceList::Lista->value;

        $query = Product::query()
            ->active()
            ->where('TIPO_ARTICULO_VTA', 'BR')
            ->select([
                'id', 'product_code', 'details', 'packing',
                'empaque_venta', 'category_id', 'orden_catalogo',
            ])
            ->selectSub(function ($q) use ($priceListId) {
                $q->select('PRECIO')
                    ->from('_PRECIOS_ARTICULOS')
                    ->whereColumn('ARTICULO_ID', 'products.id')
                    ->where('PRECIO_EMPRESA_ID', $priceListId)
                    ->limit(1);
            }, 'precio')
            ->selectSub(function ($q) {
                $q->select('IMAGEN_ARTICULO_ID')
                    ->from('IMAGENES_ARTICULOS')
                    ->whereColumn('ARTICULO_ID', 'products.id')
                    ->where('ROL_IMAGEN_ART_ID', self::ROL_IMAGEN_PRINCIPAL)
                    ->limit(1);
            }, 'imagen_id')
            ->with('category:id,name,url');

        if ($divisionId = $request->integer('division')) {
            $categoryIds = DB::table('groups_divisions')
                ->where('division_id', $divisionId)
                ->whereNotNull('category_id')
                ->pluck('category_id');
            $query->whereIn('category_id', $categoryIds);
        }

        if ($categoryId = $request->integer('categoria')) {
            $query->where('category_id', $categoryId);
        }

        $keywords = $this->extractKeywords($request->input('q', ''));
        if (!empty($keywords)) {
            // Al menos una keyword debe coincidir (OR por keyword)
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere(function ($inner) use ($kw) {
                        $inner->where('products.details', 'like', "%{$kw}%")
                            ->orWhere('products.product_code', 'like', "%{$kw}%")
                            ->orWhere('products.ofb', 'like', "%{$kw}%")
                            ->orWhereExists(fn ($sub) => $sub
                                ->from('categories as sc')
                                ->whereColumn('sc.id', 'products.category_id')
                                ->where('sc.name', 'like', "%{$kw}%")
                            )
                            ->orWhereExists(fn ($sub) => $sub
                                ->from('INFORMACION_ARTS as ia')
                                ->whereColumn('ia.ARTICULO_ID', 'products.id')
                                ->where('ia.INFORMACION', 'like', "%{$kw}%")
                            );
                    });
                }
            });

            // Ordenar por número de keywords que coinciden (más coincidencias = primero)
            if (count($keywords) > 1) {
                $scoreParts = [];
                $scoreBindings = [];
                foreach ($keywords as $kw) {
                    $like = "%{$kw}%";
                    $scoreParts[] = '(CASE WHEN (products.details LIKE ? OR products.product_code LIKE ? OR products.ofb LIKE ?'
                        . ' OR EXISTS (SELECT 1 FROM categories sc WHERE sc.id = products.category_id AND sc.name LIKE ?)'
                        . ' OR EXISTS (SELECT 1 FROM INFORMACION_ARTS ia WHERE ia.ARTICULO_ID = products.id AND ia.INFORMACION LIKE ?)'
                        . ') THEN 1 ELSE 0 END)';
                    array_push($scoreBindings, $like, $like, $like, $like, $like);
                }
                $query->orderByRaw(implode(' + ', $scoreParts) . ' DESC', $scoreBindings);
            }
        }

        $query->orderBy('orden_catalogo');

        $paginator = $query->paginate(24, ['*'], 'page', max(1, $request->integer('page', 1)));

        $cdnUrl = self::CDN_URL;
        $paginator->getCollection()->transform(function ($p) use ($cdnUrl) {
            $p->imagen_url = $p->imagen_id ? $cdnUrl . $p->imagen_id . '.webp' : null;
            unset($p->imagen_id);
            return $p;
        });

        return response()->json($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $priceListId = PriceList::Lista->value;

        $product = Product::query()
            ->active()
            ->select([
                'id', 'product_code', 'details', 'packing',
                'empaque_venta', 'category_id', 'barcode', 'inner', 'master',
            ])
            ->with('category:id,name,url')
            ->findOrFail($id);

        $precio = PrecioArticulo::where('ARTICULO_ID', $id)
            ->where('PRECIO_EMPRESA_ID', $priceListId)
            ->value('PRECIO');

        $imagenes = DB::table('IMAGENES_ARTICULOS')
            ->where('ARTICULO_ID', $id)
            ->orderBy('IMAGEN_ARTICULO_ID')
            ->pluck('IMAGEN_ARTICULO_ID')
            ->map(fn ($imgId) => self::CDN_URL . $imgId . '.webp')
            ->values()
            ->all();

        return response()->json([
            ...$product->toArray(),
            'precio'   => $precio,
            'imagenes' => $imagenes,
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $brProductsSub = fn ($q) => $q->from('products')
            ->whereColumn('products.category_id', 'categories.id')
            ->where('products.TIPO_ARTICULO_VTA', 'BR')
            ->where('products.existencia', 'S')
            ->where(fn ($i) => $i->where('products.AGOTADO', '!=', 'S')->orWhereNull('products.AGOTADO'));

        $query = Category::query()
            ->select('id', 'name', 'url', 'parent_id', 'orden_relevancia', 'orden_catalogo')
            ->selectSub(fn ($q) => $q->selectRaw('COUNT(*)')->tap($brProductsSub), 'number_products')
            ->whereExists($brProductsSub)
            ->orderBy('orden_relevancia')
            ->orderBy('name');

        if ($divisionId = $request->integer('division')) {
            $categoryIds = DB::table('groups_divisions')
                ->where('division_id', $divisionId)
                ->whereNotNull('category_id')
                ->pluck('category_id');
            $query->whereIn('id', $categoryIds);
        }

        return response()->json($query->get());
    }

    /**
     * Procesa el término de búsqueda igual que el legacy (mcatalogo.php):
     * elimina stop words, diacríticos y sufijos, devuelve keywords individuales.
     */
    private function extractKeywords(string $input): array
    {
        if (trim($input) === '') return [];

        // 1. Quitar diacríticos (igual que removeDiacritics() del legacy)
        $map = [
            ['Á','À','Â','Ä','á','à','ä','â'], ['A','A','A','A','a','a','a','a'],
            ['É','È','Ê','Ë','é','è','ë','ê'], ['E','E','E','E','e','e','e','e'],
            ['Í','Ì','Ï','Î','í','ì','ï','î'], ['I','I','I','I','i','i','i','i'],
            ['Ó','Ò','Ö','Ô','ó','ò','ö','ô'], ['O','O','O','O','o','o','o','o'],
            ['Ú','Ù','Ü','Û','ú','ù','ü','û'], ['U','U','U','U','u','u','u','u'],
            ['Ñ','ñ'], ['N','n'],
        ];
        $search = $input;
        for ($i = 0; $i < count($map); $i += 2) {
            $search = str_replace($map[$i], $map[$i + 1], $search);
        }

        // 2. Lowercase y reemplazar guiones/signos por espacio
        $search = strtolower(preg_replace('/[-\/\\\\]/', ' ', $search));

        // 3. Eliminar stop words (preposiciones + artículos comunes — mismo listado legacy)
        $stopWords = [
            'algo','alguna','alguno','aquel','aquella','aquello','bastante','como',
            'cual','cuanta','cuanto','cuya','cuyo','demasiada','demasiado','donde',
            'el','esa','ese','eso','esta','este','esto','lo','me','mi','mia','mio',
            'mucha','mucho','nada','ninguna','ninguno','nuestra','nuestro','poca',
            'poco','que','quien','se','su','suya','suyo','tanta','tanto','te','tu',
            'tuya','tuyo','usted','vuestra','vuestro','yo',
            // preposiciones
            'a','ante','bajo','cabe','con','contra','de','del','desde','durante',
            'en','entre','hacia','hasta','mediante','para','por','segun','sin',
            'so','sobre','tras','versus','via','y','o','las','los','la','un','una',
        ];
        $words = array_filter(
            explode(' ', $search),
            fn ($w) => strlen($w) >= 2 && !in_array($w, $stopWords, true)
        );

        // 4. Quitar sufijos plurales (legacy: "es","s","en","n") — solo si la raíz queda >= 4 chars
        $words = array_map(function ($w) {
            if (strlen($w) > 5 && str_ends_with($w, 'es')) return substr($w, 0, -2);
            if (strlen($w) > 4 && str_ends_with($w, 's'))  return substr($w, 0, -1);
            return $w;
        }, $words);

        return array_values(array_unique(array_filter($words, fn ($w) => strlen($w) >= 2)));
    }

    public function divisions(): JsonResponse
    {
        $divisions = DB::table('divisions as d')
            ->join('groups_divisions as gd', 'gd.division_id', '=', 'd.id')
            ->join('categories as c', 'c.id', '=', 'gd.category_id')
            ->join('products as p', function ($j) {
                $j->on('p.category_id', '=', 'c.id')
                    ->where('p.TIPO_ARTICULO_VTA', 'BR')
                    ->where('p.existencia', 'S');
            })
            ->select('d.id', 'd.name')
            ->distinct()
            ->orderBy('d.name')
            ->get();

        return response()->json($divisions);
    }
}
