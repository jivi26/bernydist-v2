<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $table = 'divisions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = ['id'];
}
