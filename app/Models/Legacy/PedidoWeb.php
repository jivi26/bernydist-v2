<?php

namespace App\Models\Legacy;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoWeb extends Model
{
    protected $table = 'pedidos_web2';
    protected $primaryKey = 'pedidos_web_id';
    public $timestamps = true;

    protected $guarded = ['pedidos_web_id'];

    protected function casts(): array
    {
        return [
            'ESTATUS' => OrderStatus::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'CLIENTE_ID', 'CLIENTE_ID');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoWebDetalle::class, 'pedido_web_id', 'pedidos_web_id');
    }

    public function scopeForCliente($query, int $clienteId)
    {
        return $query->where('CLIENTE_ID', $clienteId);
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('ESTATUS', [
            OrderStatus::Nuevo->value,
            OrderStatus::EnRevision->value,
            OrderStatus::EnPago->value,
        ]);
    }
}
