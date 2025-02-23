<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\TypeCriteria;
use App\Models\Project;

class Assessment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'assessment';

    protected $fillable = [
        'id',
        'batch_year',
        'project_id',
        'type',
        'question',
        'criteria_id',
        'skill_type',
        'end_date'
    ];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function typeCriteria()
    {
        return $this->belongsTo(TypeCriteria::class, 'criteria_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
