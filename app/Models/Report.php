<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Report extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reports';

    protected $fillable = [
        'project_id',
        'group_id',
        'mahasiswa_id',
        'typeCriteria_id',
        'skor_self',
        'skor_peer',
        'selisih',
        'nilai_total',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'group_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'id');
    }

    public function typeCriteria()
    {
        return $this->belongsTo(TypeCriteria::class, 'typeCriteria_id', 'id');
    }

    public function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }
}
