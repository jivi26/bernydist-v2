<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = ['cliente_id'];

    protected $hidden = ['remember_token'];

    public $timestamps = true;

    /**
     * Contraseña en texto plano cargada desde _DIRS_CLIENTES.PASS durante el intento de login.
     * No persiste — solo existe en memoria durante Auth::attempt().
     */
    public ?string $legacyPassword = null;

    /**
     * Laravel llama este método para validar credenciales via Hash::check().
     * Devolvemos el PASS legacy para que LegacyUserProvider lo compare directamente.
     */
    public function getAuthPassword(): string
    {
        return $this->legacyPassword ?? '';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Datos completos del cliente desde _CLIENTES.
     * Uso: auth()->user()->cliente->NOMBRE
     */
    public function cliente(): HasOne
    {
        return $this->hasOne(Legacy\Cliente::class, 'CLIENTE_ID', 'cliente_id');
    }

    protected function casts(): array
    {
        return [];
    }
}
