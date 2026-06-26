<?php

namespace App\Enums;

enum Brand: string
{
    case BernyDist  = 'BR';
    case Tecnolite  = 'TL';
    case GomaFacil  = 'GM';
    case Equimaq    = 'EQ';

    public function label(): string
    {
        return match($this) {
            self::BernyDist => 'Berny Distribuidora',
            self::Tecnolite => 'Tecnolite',
            self::GomaFacil => 'GomáFácil',
            self::Equimaq   => 'Equimaq',
        };
    }

    /** Tabla de productos que corresponde a esta marca */
    public function productsTable(): string
    {
        return match($this) {
            self::Equimaq => 'products_eqm',
            default       => 'products',
        };
    }
}
