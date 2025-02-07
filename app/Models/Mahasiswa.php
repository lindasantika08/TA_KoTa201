<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mahasiswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'class_id',
        'nim',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function group()
    {
        return $this->hasMany(Group::class, 'mahasiswa_id');
    }

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id');
    }
    public function report()
    {
        return $this->hasMany(Report::class, 'mahasiswa_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'mahasiswa_id');
    }
}
