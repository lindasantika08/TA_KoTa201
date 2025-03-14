<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use PhpParser\Builder\Function_;
use PhpParser\Node\Expr\FuncCall;

class Group extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'groups';

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'batch_year',
        'project_id',
        'mahasiswa_id',
        'group',
        'dosen_id',
        'angkatan',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function peer()
    {
        return $this->belongsTo(Mahasiswa::class, 'peer_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function classroom()
    {
        return $this->belongsTo(ClassRoom::class, 'angkatan', 'angkatan');
    }

    public function report()
    {
        return $this->hasMany(Report::class, 'group_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'group_id');
    }

    public function feedbackAI()
    {
        return $this->hasMany(feedback_ai::class, 'group_id');
    }
}
