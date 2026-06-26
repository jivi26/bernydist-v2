<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Nuevo      = '0';   // Cotización en proceso — cliente armando el pedido
    case EnRevision = '1';   // En revisión interna
    case Procesado  = '2';   // Procesado por ERP / facturado (protegido por trigger)
    case Cancelado  = '4';   // Cancelado
    case EnPago     = '5';   // En proceso de pago (protegido por trigger)
    case Confirmado = '11';  // Confirmado y enviado al CRM

    public function label(): string
    {
        return match($this) {
            self::Nuevo      => 'En proceso',
            self::EnRevision => 'En revisión',
            self::Procesado  => 'Procesado',
            self::Cancelado  => 'Cancelado',
            self::EnPago     => 'En pago',
            self::Confirmado => 'Confirmado',
        };
    }

    /** Estados en los que el cliente puede editar el pedido */
    public function isEditable(): bool
    {
        return in_array($this, [self::Nuevo, self::Cancelado]);
    }

    /** Estados protegidos por trigger de BD — no se puede eliminar el detalle */
    public function isLocked(): bool
    {
        return in_array($this, [self::Procesado, self::EnPago]);
    }
}
