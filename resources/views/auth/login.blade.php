<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — BernyDist</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f3f4f6; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); width: 100%; max-width: 380px; }
        h1 { font-size: 1.25rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: .875rem; margin-bottom: .25rem; color: #374151; }
        input { width: 100%; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 1rem; box-sizing: border-box; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.2); }
        .error { color: #dc2626; font-size: .875rem; margin: -.75rem 0 .75rem; }
        button { width: 100%; padding: .625rem; background: #2563eb; color: white; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .remember { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; font-size: .875rem; }
        .remember input { width: auto; margin: 0; }
    </style>
</head>
<body>
<div class="card">
    <h1>Iniciar sesión</h1>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf

        <label for="code">Correo electrónico o clave de cliente</label>
        <input
            id="code"
            name="code"
            type="text"
            value="{{ old('code') }}"
            autocomplete="username"
            autofocus
        >

        <label for="password">Contraseña</label>
        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
        >

        <div class="remember">
            <input id="remember" name="remember" type="checkbox" value="1">
            <label for="remember" style="margin:0">Recuérdame</label>
        </div>

        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
