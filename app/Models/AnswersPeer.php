<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswersPeer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'answers_peer';

    protected $fillable = [
        'id',
        'mahasiswa_id',
        'peer_id',
        'question_id',
        'answer',
        'score',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'mahasiswa_id' => 'string',
        'peer_id' => 'string',
        'question_id' => 'string',
        'score' => 'integer',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function peer()
    {
        return $this->belongsTo(Mahasiswa::class, 'peer_id', 'id');
    }

    public function question()
    {
        return $this->belongsTo(Assessment::class, 'question_id', 'id');
    }
}
