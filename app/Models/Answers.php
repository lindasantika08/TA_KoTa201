<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Answers extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'question_id',
        'answer',
        'score',
        'status',
    ];

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Get the user who submitted the answer
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question this answer belongs to
     */
    public function question()
    {
        return $this->belongsTo(Assessment::class, 'question_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, ['tahun_ajaran', 'nama_proyek'], ['tahun_ajaran', 'nama_proyek']);
    }
}
