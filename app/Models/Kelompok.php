<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kelompok extends Model
{
    use HasFactory;

    protected $table = 'kelompok';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tahun_ajaran',
        'nama_proyek',
        'user_id',
        'dosen_id',
        'kelompok',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kelompok) {
            $kelompok->id = (string) Str::uuid(); 
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, ['tahun_ajaran', 'nama_proyek'], ['tahun_ajaran', 'nama_proyek']);
    }
}
