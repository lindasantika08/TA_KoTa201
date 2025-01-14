<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\type_criteria;

class Assessment extends Model
{
    use HasFactory;

    // Tentukan nama tabel yang digunakan
    protected $table = 'assessment';

    // Tentukan kolom-kolom yang bisa diisi (fillable)
    protected $fillable = [
        'id',
        'tahun_ajaran',
        'nama_proyek',
        'type',
        'pertanyaan',
        'aspek',
        'kriteria',
    ];

    public function typeCriteria()
    {
        return $this->belongsTo(type_criteria::class, ['aspek', 'kriteria'], ['aspek', 'kriteria']);
    }

    public function project()
    {
        return $this->belongsTo(project::class, ['tahun_ajaran', 'nama_proyek'], ['tahun_ajaran', 'nama_proyek']);
    }
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
