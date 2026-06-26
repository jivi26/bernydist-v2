<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = ['id'];

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'groups_divisions', 'group_id', 'division_id')
            ->withPivot('category_id');
    }
}
