<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    {{-- Datos del servidor disponibles para Vue vía window.appData --}}
    @php
        $sessionUser = session('user');

        // Notificaciones de pedidos — solo si hay sesión activa
        $notifData = ['total' => 0, 'pedidos' => 0, 'pagos' => 0, 'proceso' => 0];
        if ($sessionUser) {
            try {
                $rows = \DB::select(
                    "SELECT ESTATUS, COUNT(*) as total FROM pedidos_web2
                     WHERE CLIENTE_ID = ? AND ESTATUS IN (0,4,5) AND TOTAL > 0
                     GROUP BY ESTATUS",
                    [$sessionUser['CLIENTE_ID']]
                );
                foreach ($rows as $row) {
                    $notifData['total'] += $row->total;
                    if ($row->ESTATUS == 0) $notifData['pedidos'] = $row->total;
                    if ($row->ESTATUS == 4) $notifData['pagos']   = $row->total;
                    if ($row->ESTATUS == 5) $notifData['proceso'] = $row->total;
                }
            } catch (\Throwable $e) {
                // Si la tabla aún no existe en dev, ignoramos silenciosamente
            }
        }

        $appData = [
            'user' => $sessionUser ? [
                'id'                 => auth()->id(),
                'cliente_id'         => $sessionUser['CLIENTE_ID'],
                'nombre'             => $sessionUser['NOMBRE'],
                'clave'              => $sessionUser['CLAVE_CLIENTE'],
                'tipo_cliente_id'    => $sessionUser['TIPO_CLIENTE_ID'],
                'asesor_login_id'    => $sessionUser['ASESOR_LOGIN_ID'],
                'solo_vta_contado'   => $sessionUser['SOLO_VTA_CONTADO'],
                'client_type'        => $sessionUser['client_type'],
                'price_level_reached'=> $sessionUser['price_level_reached'],
                'distrib_label'      => $sessionUser['distrib_label'],
                'distrib_url'        => $sessionUser['distrib_url'],
            ] : null,
            'csrfToken'     => csrf_token(),
            'baseUrl'       => config('app.url'),
            'catalogo_url'  => config('berny.catalogo_url'),
            'errors'        => $errors->toArray(),
            'old'           => session()->getOldInput(),
            'flash'         => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
            'videos'        => [
                'distribuidor' => config('berny.videos.distribuidor'),
            ],
            'notifications' => $notifData,
        ];
    @endphp
    <script>
        window.appData = @json($appData);
    </script>

    <div id="app">
        @yield('content')
    </div>

</body>
</html>
