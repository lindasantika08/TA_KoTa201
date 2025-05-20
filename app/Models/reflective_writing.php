<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Project;
use Illuminate\Testing\Fluent\Concerns\Has;

class reflective_writing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reflective_writing';

    protected $fillable = [
        'id',
        'batch_year',
        'project_id',
        'reflective_writing_order',
        'type',
        'point_1',
        'point_2',
        'point_3',
        'point_4',
        'point_5',
        'end_date',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
