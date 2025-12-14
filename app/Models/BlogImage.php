<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogImage extends Model
{
    use HasFactory;
    protected $table = 'web_blog_images';
    protected $fillable = ['blog_id', 'image_path', 'image_description'];

    // public function images()
    // {
    //     return $this->hasMany(BlogImage::class);
    // }
}
