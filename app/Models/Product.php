<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'image',
        'sizes',
        'gallery_images', // <-- Yeh add kiya hai
    ];

    // Sizes aur Gallery Images ko array mein convert karne ke liye
    protected $casts = [
        'sizes' => 'array',
        'gallery_images' => 'array', // <-- Yeh add kiya hai
        'compare_at_price' => 'decimal:2',
    ];

    // Ek product kisi ek category ka hota hai
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}