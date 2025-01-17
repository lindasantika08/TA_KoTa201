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
}
