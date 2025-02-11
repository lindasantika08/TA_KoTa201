<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dosen extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dosen';

    protected $fillable = [
        'user_id',
        'major_id',
        'nip',
        'kode_dosen',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(Answers::class, 'dosen_id');
    }

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'dosen_id');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class, 'dosen_id');
    }
}
