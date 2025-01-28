<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prodi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'prodi';

    protected $fillable = [
        'major_id',
        'prodi_name',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id');
    }
}
