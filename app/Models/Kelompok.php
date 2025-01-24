<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kelompok extends Model
{
    use HasFactory;

    protected $table = 'kelompok';

    // Atur $keyType dan $incrementing untuk UUID
    protected $keyType = 'string';
    public $incrementing = false;

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'id',
        'tahun_ajaran',
        'nama_proyek',
        'user_id',
        'dosen_id',
        'kelompok',
    ];

    // Menghasilkan UUID untuk kolom id secara otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kelompok) {
            $kelompok->id = (string) Str::uuid(); // Menetapkan UUID ke kolom id
        });
    }

    // Relasi ke User (Mahasiswa/Dosen)
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
        return $this->hasMany(User::class, 'user_id'); // Relasi ke tabel users
    }

    // Definisikan relasi dengan model Project
    public function project()
    {
        return $this->belongsTo(Project::class, ['tahun_ajaran', 'nama_proyek'], ['tahun_ajaran', 'nama_proyek']);
    }
}
