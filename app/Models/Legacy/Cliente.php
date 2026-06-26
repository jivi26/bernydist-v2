<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = '_CLIENTES';
    protected $primaryKey = 'CLIENTE_ID';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = ['CLIENTE_ID'];
}
