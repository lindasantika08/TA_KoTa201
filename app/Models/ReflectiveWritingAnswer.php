<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;



class ReflectiveWritingAnswer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'reflective__writing_answer';

    protected $fillable = [
        'mahasiswa_id',
        'reflectiveWriting_id',
        'answer',
        'status',
    ];

    public function reflectiveWriting()
    {
        return $this->belongsTo(reflective_writing::class, 'reflectiveWriting_id');
    }
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }
}
