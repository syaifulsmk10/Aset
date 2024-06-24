<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageAsset extends Model
{
    use HasFactory;

    protected $fillable = ['asset_id', 'path'];
    
     public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getPathAttribute($value) {
        return $value;
    }
}
