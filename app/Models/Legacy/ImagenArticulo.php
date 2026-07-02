<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagenArticulo extends Model
{
    protected $table = 'IMAGENES_ARTICULOS';
    protected $primaryKey = 'IMAGEN_ARTICULO_ID';
    public $timestamps = false;

    protected $guarded = ['IMAGEN_ARTICULO_ID'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ARTICULO_ID', 'id');
    }
}
