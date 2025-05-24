<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilesOffice extends Model
{
    protected $table = 'files_offices';

    protected $fillable=[
        'category_id',
        'file',
        'teacher_id',
    ];

    public function filesCategory()
    {
        return $this->belongsTo(filesCategory::class, 'category_id');
    }

    public function teacher()
    {
        return $this->belongsTo(teacher::class, 'teacher_id');
    }
}
