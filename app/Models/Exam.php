<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'description',
        'duration_minutes',
        'is_active',
        'allow_review',
        'is_scheduled',
        'start_at',
        'end_at',
        'teacher_id',
        'attempt_limit',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

}
