<?php

namespace App\Http\Controllers;

use App\Constants\MessageConstants;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    // User registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users|unique:students|unique:teachers',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            // return response()->json($validator->errors()->toJson(), 400);
            return $this->apiResponse($validator->errors()->toJson(), MessageConstants::QUERY_NOT_EXECUTED, 400);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'password' => Hash::make($request->get('password')),
        ]);

        $token = JWTAuth::fromUser($user);
// 
        return $this->apiResponse(compact('user', 'token'), MessageConstants::STORE_SUCCESS, 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        // نحاول أولاً مع جدول users
        if ($user = User::where('username', $credentials['username'])->first()) {

            if (! $token = Auth::guard('user')->attempt($credentials)) {
                return $this->apiResponse('Wrong password', MessageConstants::QUERY_NOT_EXECUTED, 401);
            }

            return $this->apiResponse([
                'token' => $token,
                'type' => 'user',
                'user' => Auth::guard('user')->user()
            ], MessageConstants::QUERY_EXECUTED, 200);
        }

        // معلم
        if ($teacher = Teacher::where('username', $credentials['username'])->first()) {
            if (! $teacher->is_active) {
                return $this->apiResponse('Teacher account is not active', MessageConstants::QUERY_NOT_EXECUTED, 403);
            }

            if (! $token = Auth::guard('teacher')->attempt($credentials)) {
                return $this->apiResponse('Wrong password', MessageConstants::QUERY_NOT_EXECUTED, 401);
            }

            return $this->apiResponse([
                'token' => $token,
                'type' => 'teacher',
                'teacher' => Auth::guard('teacher')->user()
            ], MessageConstants::QUERY_EXECUTED, 200);
        }

        // طالب
        if ($student = Student::where('username', $credentials['username'])->first()) {
            if (! $student->is_active) {
                return $this->apiResponse('Student account is not active', MessageConstants::QUERY_NOT_EXECUTED, 403);
            }

            if (! $token = Auth::guard('student')->attempt($credentials)) {
                return $this->apiResponse('Wrong password', MessageConstants::QUERY_NOT_EXECUTED, 401);
            }

            return $this->apiResponse([
                'token' => $token,
                'type' => 'student',
                'student' => Auth::guard('student')->user()
            ], MessageConstants::QUERY_EXECUTED, 200);
        }

        return $this->apiResponse('Username not found', MessageConstants::QUERY_NOT_EXECUTED, 404);
    }

    public function logout(Request $request)
    {
        try {
            auth()->logout();
            return $this->apiResponse('Logged out successfully', MessageConstants::QUERY_EXECUTED, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to logout'], 500);
            return $this->apiResponse('Unable to logout', MessageConstants::QUERY_NOT_EXECUTED, 400);
        }
    }

    // public function profile(Request $request)
    // {
    //     try {
    //         $user = auth()->user();
    //         $guard = $user instanceof \App\Models\Teacher ? 'teacher' : 'user';

    //         return response()->json([
    //             'type' => $guard,
    //             'user' => $user,
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->apiResponse('Unauthenticated', MessageConstants::QUERY_NOT_EXECUTED, 401);
    //     }
    // }
}
