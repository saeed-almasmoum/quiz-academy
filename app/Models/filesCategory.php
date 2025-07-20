<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class filesCategory extends Model
{
    protected $table = 'files_categories';
    protected $fillable=[
        'name',
];

    public function filesOffice()
    {
        return $this->hasMany(FilesOffice::class, 'category_id');
    }
}
