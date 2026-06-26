<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = ['id'];

    // Nota: price e iva son float en BD — nunca usarlos para cálculos monetarios directos.
    // Usar _PRECIOS_ARTICULOS para precios reales.

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id', 'id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PrecioArticulo::class, 'ARTICULO_ID', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('existencia', 'S')->where('AGOTADO', '!=', 'S');
    }

    public function scopeForBrand($query, string $tipoArticuloVta)
    {
        return $query->where('TIPO_ARTICULO_VTA', $tipoArticuloVta);
    }
}
