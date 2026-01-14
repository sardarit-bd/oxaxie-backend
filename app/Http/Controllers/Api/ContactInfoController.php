<?php

namespace App\Http\Controllers\Api;

use App\Models\ContactInfo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ContactInfoController extends Controller
{
    public function index()
    {
        $info = ContactInfo::first();

        if (!$info) {
            return response()->json([
                'phone' => '+1 (555) 000-0000',
                'email' => 'admin@example.com',
                'address' => 'Address not set',
                'twitter_url' => '#',
                'instagram_url' => '#',
                'linkedin_url' => '#'
            ]);
        }

        return response()->json($info);
    }
}
