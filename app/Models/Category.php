<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    // Ek category ke andar bohat se products ho sakte hain
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}