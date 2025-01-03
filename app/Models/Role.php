<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Authenticatable{

    use HasApiTokens;
    use Notifiable;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'id',
        'role',
    ];

}