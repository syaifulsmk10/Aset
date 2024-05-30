<?php

namespace App\Models;

use App\Enums\StatusApplicant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'asset_id', 'submission_date', 'expiry_date', 'accepted_at', 'denied_at', 'type', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

     public function images()
    {
        return $this->hasMany(Image::class); // One applicant has many images
    }

    public function getImageAttribute($value)
        {
            return env('APP_URL') . $value;
        }

     
    public function getStatusApplicantAttribute($value)
    {
        return StatusApplicant::fromValue((int) $value)->key;
    }   
}
