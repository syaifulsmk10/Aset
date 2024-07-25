<?php

namespace App\Models;

use App\Enums\ItemCondition;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

     protected $fillable = ['asset_code', 'asset_name', 'category_id', 'item_condition', 'price', 'received_date', 'expiration_date', 'status'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

     public function imageAssets()
    {
        return $this->hasMany(ImageAsset::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

     public function getItemConditionAttribute($value)
    {
        return ItemCondition::fromValue((int) $value)->key;
    }

    public function getStatusAttribute($value)
    {
        return Status::fromValue((int) $value)->key;
    }   
}
