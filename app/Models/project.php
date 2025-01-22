<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;


class project extends Model
{

    use HasFactory;

    protected $table = 'project';
    protected $primaryKey = ['tahun_ajaran', 'nama_proyek'];
    public $incrementing = false;

    protected $fillable = [
        'semester',
        'tahun_ajaran',
        'nama_proyek',
        'jurusan',
        'start_date',
        'end_date',
        'status',
    ];

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
