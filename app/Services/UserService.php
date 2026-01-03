<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    // this function used as general account sign up.
    public function SignUp(array $data): User {
        $fullname = trim(preg_replace('/\s+/', ' ', $data['full_name']));
        $parts = explode(' ', $fullname);

        if (count($parts) != 3) {
            throw new \InvalidArgumentException(
                'Full name must contain first, mid and last name'
            );
        }

        [$first, $mid, $last] = $parts;

        $user = User::where('phone', $data['phone_number'])->exists();
    
        if ($user) {
            throw new \RuntimeException(
                'Phone number already exists'
            );
        }

        return User::create([
            'first_name' => $first,
            'mid_name' => $mid,
            'last_name' => $last,
            'phone' => $data['phone_number'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'gender' => $data['gender'],
            'birth_date' => $data['date_of_birth'],
            'profile_photo_url' => $data['profile_image'],
        ]);
    }
}
