<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebBlog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'description',
        'user_id',
        'category_id',
        'user_id',
        'status',
        'image_path',
        'view_count',
        'read_count',
        'style',

    ];

    protected static function booted()
    {
        static::creating(function ($blog) {
            $slug = Str::slug($blog->title);
            $original = $slug;
            $count = 1;

            while (WebBlog::where('slug', $slug)->exists()) {
                $slug = $original.'-'.$count++;
            }

            $blog->slug = $slug;
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function images()
    {
        return $this->hasMany(BlogImage::class, 'blog_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
