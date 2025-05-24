<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable=[
            'text',
        'image',
        'type',
        'exam_id',

    ];


    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
