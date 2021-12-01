<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $fillable = [
        'slug',
        'name',
        'description',
        'status',
        'meta_title',
        'meta_keyword',
        'meta_description'
    ];

    public function product()
    {
        return $this->belongsToMany(Product::class, 'id', 'category_id');
    }
}
