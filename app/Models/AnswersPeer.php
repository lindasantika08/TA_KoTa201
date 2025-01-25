<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswersPeer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'answersPeer';

    protected $fillable = [
        'user_id',
        'peer_id',
        'question_id',
        'answer',
        'score',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'peer_id' => 'string',
        'question_id' => 'string',
        'score' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function kelompok()
    {
        return $this->belongsTo(Kelompok::class, 'user_id', 'user_id');
    }

    public function peer()
    {
        return $this->belongsTo(User::class, 'peer_id', 'id');
    }

    public function question() {
        return $this->belongsTo(Assessment::class, 'question_id');
    }
}
