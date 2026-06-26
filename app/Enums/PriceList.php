<?php

namespace App\Enums;

enum PriceList: int
{
    case Lista          = 42;
    case GomaFacilAzul  = 43;
    case GomaFacilCtdo  = 47;
    case GomaFacilPubl  = 58937;
    case GomaFacilVerde = 102889139;
    case GomaFacilVip   = 103582441;

    public function label(): string
    {
        return match($this) {
            self::Lista          => 'Precio Lista',
            self::GomaFacilAzul  => 'GomáFácil Azul',
            self::GomaFacilCtdo  => 'GomáFácil Contado',
            self::GomaFacilPubl  => 'GomáFácil Pública',
            self::GomaFacilVerde => 'GomáFácil Verde',
            self::GomaFacilVip   => 'GomáFácil VIP',
        };
    }

    /** Vista de BD correspondiente a esta lista */
    public function dbView(): string
    {
        return match($this) {
            self::Lista          => 'products_preciolista',
            self::GomaFacilAzul  => 'products_gmazul',
            self::GomaFacilCtdo  => 'products_gcontadosc',
            self::GomaFacilPubl  => 'products_gmpublic',
            self::GomaFacilVerde => 'products_gmverde',
            self::GomaFacilVip   => 'products_gmvip',
        };
    }
}
