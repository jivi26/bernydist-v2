<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioArticulo extends Model
{
    protected $table = '_PRECIOS_ARTICULOS';
    protected $primaryKey = 'PRECIO_ARTICULO_ID';
    public $timestamps = false;

    protected $guarded = ['PRECIO_ARTICULO_ID'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ARTICULO_ID', 'id');
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(PrecioEmpresa::class, 'PRECIO_EMPRESA_ID', 'Precio_Empresa_id');
    }

    public function scopeForList($query, int $precioEmpresaId)
    {
        return $query->where('PRECIO_EMPRESA_ID', $precioEmpresaId);
    }
}
