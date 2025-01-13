<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    // Tentukan nama tabel yang digunakan
    protected $table = 'assessment';

    // Tentukan kolom-kolom yang bisa diisi (fillable)
    protected $fillable = [
        'id',
        'type',
    ];

    // Untuk menggunakan UUID, kita akan menambahkan properti boot
    protected static function boot()
    {
        parent::boot();

        // Generating UUID otomatis pada saat membuat record baru
        static::creating(function ($model) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }
}
