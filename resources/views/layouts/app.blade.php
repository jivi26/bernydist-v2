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
        $appData = [
            'user' => auth()->check() ? [
                'id'         => auth()->id(),
                'cliente_id' => auth()->user()->cliente_id,
                'nombre'     => auth()->user()->cliente?->NOMBRE,
                'clave'      => auth()->user()->cliente?->CLAVE_CLIENTE,
            ] : null,
            'csrfToken' => csrf_token(),
            'baseUrl'   => config('app.url'),
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
