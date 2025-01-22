<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswersPeer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'AnswersPeer';

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
}
