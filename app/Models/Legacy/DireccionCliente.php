<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionCliente extends Model
{
    protected $table = '_DIRS_CLIENTES';
    protected $primaryKey = 'DIR_CLI_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = ['DIR_CLI_ID'];

    // PASS no se expone — el ERP es el dueño y se sincroniza cada noche
    protected $hidden = ['PASS'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'CLIENTE_ID', 'CLIENTE_ID');
    }

    public function scopePrincipal($query)
    {
        return $query->where('ES_DIR_PPAL', 'S');
    }
}
