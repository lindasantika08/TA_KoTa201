<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\type_criteria;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'assessment';

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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }
}
