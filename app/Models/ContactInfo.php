<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'email',
        'address',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
    ];
}
