<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class filesCategory extends Model
{
    protected $table = 'files_categories';
    protected $fillable=[
        'name',
        'teacher_id'
];

    public function filesOffice()
    {
        return $this->hasMany(FilesOffice::class, 'category_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

}
