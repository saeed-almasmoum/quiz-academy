<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Teacher extends Authenticatable implements JWTSubject
{


    protected $fillable = ['name', 'username', 'password', 'is_active'];

    protected $hidden = ['password'];

    public function students()
    {
        return $this->belongsToMany(Student::class)->withTimestamps();
    }

    public function filesCategory()
    {
        return $this->hasMany(filesCategory::class, 'teacher_id');
    }
    
    public function filesOffice()
    {
        return $this->hasMany(FilesOffice::class, 'teacher_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'teacher_id');
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
