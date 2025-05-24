<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // العلاقة مع السؤال
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

}
