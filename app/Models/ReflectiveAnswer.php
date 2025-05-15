<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;


class ReflectiveAnswer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'reflective_assessment_answer';

    protected $fillable = [
        'mahasiswa_id',
        'question_id',
        'answer',
        'status',
    ];

    public function question()
    {
        return $this->belongsTo(Assessment::class, 'question_id', 'id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }
}
