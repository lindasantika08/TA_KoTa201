<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class type_criteria extends Model
{
    use HasFactory;

    protected $table = 'type_criteria';
    protected $primaryKey = ['aspek', 'kriteria'];
    public $incrementing = false;

    protected $fillable = [
        'aspek',
        'kriteria',
        'bobot_1',
        'bobot_2',
        'bobot_3',
        'bobot_4',
        'bobot_5',
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
