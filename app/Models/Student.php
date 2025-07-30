<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Student extends Authenticatable implements JWTSubject
{
    protected $fillable = ['name', 'username', 'password', 'is_active'];

    protected $hidden = ['password'];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class)->withTimestamps();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
