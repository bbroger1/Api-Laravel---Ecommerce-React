<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'description',
        'meta_description',
        'meta_keyword',
        'meta_title',
        'selling_price',
        'original_price',
        'quantity',
        'brand',
        'featured',
        'popular',
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
