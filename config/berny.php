<?php

/**
 * Configuración global de la aplicación BernyDist.
 * Equivale a las constantes definidas en el index.php de la versión CI.
 *
 * Credenciales (SMTP passwords, API keys) viven en .env — aquí solo valores
 * estáticos que no cambian entre entornos o que se leen de env().
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Videos YouTube
    |--------------------------------------------------------------------------
    */
    'videos' => [
        'distribuidor' => 'https://www.youtube.com/embed/RhHRLWISDYU',
        'costo_envio'  => 'https://www.youtube.com/embed/i9gn24CpRg0',
        'mejor_precio' => 'https://www.youtube.com/embed/gE7UZpugZHc',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogo digital
    |--------------------------------------------------------------------------
    */
    'catalogo_url' => 'https://online.fliphtml5.com/umoiu/Catalogo-Referencia-0226/',

    /*
    |--------------------------------------------------------------------------
    | Imagen de promoción mensual (relativa a public/uploads/promotions/)
    |--------------------------------------------------------------------------
    */
    'promo_image' => env('BERNY_PROMO_IMAGE', 'promomensual.webp'),

    /*
    |--------------------------------------------------------------------------
    | Correos electrónicos
    | Las contraseñas SMTP van en .env (MAIL_*).
    | Aquí solo se guardan direcciones de destino para notificaciones.
    |--------------------------------------------------------------------------
    */
    'emails' => [

        // Remitente principal (desde el que se envían emails al cliente)
        'ppal'         => env('MAIL_FROM_ADDRESS', 'web@berny.mx'),
        'notificaciones' => env('BERNY_EMAIL_NOTIFICACIONES', 'notificaciones@berny.mx'),
        'aclaraciones'   => env('BERNY_EMAIL_ACLARACIONES',   'aclaraciones@berny.mx'),
        'activaciones'   => env('BERNY_EMAIL_ACTIVACIONES',   'activaciones@berny.mx'),
        'ferretero'      => env('BERNY_EMAIL_FERRETERO',      'servicioalcliente@berny.mx'),

        // Destinatarios de notificaciones internas (staff)
        'distribuidor_staff'         => ['staff1@berny.mx'],
        'pago_cliente_general'       => ['fberny@berny.mx', 'randrade@berny.mx'],
        'pago_pedido'                => ['randrade@berny.mx'],
        'cuenta_activada'            => ['staff1@berny.mx'],
        'aclaraciones_staff'         => ['fberny@berny.mx', 'ivillarino@berny.mx', 'lmukul@berny.mx', 'randrade@berny.mx'],
        'google_ads'                 => ['jlizama@berny.mx', 'ivillarino@berny.mx', 'staff1@berny.mx'],
        'validacion_notificacion'    => ['staff1@berny.mx'],
    ],

];
