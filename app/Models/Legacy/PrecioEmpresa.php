<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrecioEmpresa extends Model
{
    protected $table = '_PRECIOS_EMPRESA';
    protected $primaryKey = 'Precio_Empresa_id';
    public $timestamps = false;

    protected $guarded = ['Precio_Empresa_id'];

    public function precios(): HasMany
    {
        return $this->hasMany(PrecioArticulo::class, 'PRECIO_EMPRESA_ID', 'Precio_Empresa_id');
    }
}
