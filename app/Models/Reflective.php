<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\ReflectiveRubric;
use App\Models\Project;

class Reflective extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reflective_assessment';

    protected $fillable = [
        'id',
        'batch_year',
        'project_id',
        'reflective_assessment_order',
        'question',
        'criteria_id',
        'end_date',
        'is_published' // Tambahkan kolom ini ke fillable
    ];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function rubric()
    {
        return $this->belongsTo(ReflectiveRubric::class, 'criteria_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
