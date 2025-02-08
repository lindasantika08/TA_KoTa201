<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Major extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'major';

    protected $fillable = [
        'major_name',
    ];

    public function prodis()
    {
        return $this->hasMany(Prodi::class, 'major_id');
    }
}
