<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassRoom extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'class_room';

    protected $fillable = [
        'class_name',
        'prodi_id',
        'batch_year',
    ];

    /**
     * Define relationship with Prodi model
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function mahasiswa() {
        
        return $this->hasMany(Mahasiswa::class, 'class_id');
    }
}
