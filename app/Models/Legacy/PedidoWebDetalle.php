<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoWebDetalle extends Model
{
    protected $table = 'pedidos_web_detalle';
    protected $primaryKey = 'pedidos_web_detalle_id';
    public $timestamps = true;

    protected $guarded = ['pedidos_web_detalle_id'];

    // Todos los campos monetarios usan decimal(18,5-6) en BD — tratar como string/float con cuidado
    // NUNCA usar para suma directa sin castear a Decimal o calcular server-side

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoWeb::class, 'pedido_web_id', 'pedidos_web_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ARTICULO_ID', 'id');
    }
}
