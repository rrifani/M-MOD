<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_name', 'file_path', 'file_size', 'folder_name', 'category_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()  // Relasi dengan kategori
    {
        return $this->belongsTo(Category::class);
    }
}
