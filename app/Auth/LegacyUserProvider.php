<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\DB;

class LegacyUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return User::find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return User::where('id', $identifier)
            ->where('remember_token', $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->forceFill(['remember_token' => $token])->save();
    }

    /**
     * Busca el cliente por email (en _BR_EMAILS_CLIENTES) o CLAVE_CLIENTE (en _CLIENTES).
     * Carga el PASS legacy en texto plano para que validateCredentials lo compare.
     * No verifica la contraseña aquí — eso es responsabilidad de validateCredentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $code = trim($credentials['code'] ?? '');
        if ($code === '') {
            return null;
        }

        $isEmail = filter_var($code, FILTER_VALIDATE_EMAIL) !== false;
        $condition = $isEmail ? 'bec.EMAIL = ?' : 'c.CLAVE_CLIENTE = ?';

        $row = DB::selectOne("
            SELECT c.CLIENTE_ID, c.CLAVE_CLIENTE, c.ESTATUS, dc.PASS
            FROM _CLIENTES c
            INNER JOIN _BR_EMAILS_CLIENTES bec ON bec.CLIENTE_ID = c.CLIENTE_ID
            INNER JOIN _DIRS_CLIENTES dc       ON dc.CLIENTE_ID  = c.CLIENTE_ID
            WHERE {$condition}
              AND dc.ES_DIR_PPAL = 'S'
              AND c.ESTATUS = 'A'
            LIMIT 1
        ", [$code]);

        if (! $row) {
            return null;
        }

        $user = User::firstOrCreate(['cliente_id' => $row->CLIENTE_ID]);

        // Cargamos el PASS en memoria — no se persiste en la tabla users
        $user->legacyPassword = $row->PASS;

        return $user;
    }

    /**
     * Compara la contraseña ingresada contra el PASS del ERP (texto plano).
     * No se usa Hash::check() porque el ERP es dueño de las contraseñas.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        /** @var User $user */
        return isset($credentials['password'])
            && $user->legacyPassword !== null
            && $credentials['password'] === $user->legacyPassword;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // El ERP es el dueño del PASS — nunca re-hasheamos desde Laravel
    }
}
