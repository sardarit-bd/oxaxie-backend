<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegisterUserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;
    // public function __construct()
    // {
    //     $this->middleware('auth:api', ['except' => ['login', 'register']]);
    // }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth()->login($user);

        return $this->authResponse(new RegisterUserResource($user), $token, 'Successfully registered');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->authResponse(
            new RegisterUserResource(auth()->user()),
            $token,
            'Login successful'
        );
    }

    public function logout()
    {
        auth()->logout();
        
        return $this->successResponse(
            null,
            'Successfully logged out'
        );
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function refresh()
    {
        $token = auth()->refresh();
        
        return $this->authResponse(
            new RegisterUserResource(auth()->user()),
            $token,
            'Token refreshed successfully'
        );
    }
}