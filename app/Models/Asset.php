<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

     protected $fillable = ['asset_code', 'asset_name', 'category_id', 'item_condition', 'price', 'received_date', 'expiration_date', 'status', 'image'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

   

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
