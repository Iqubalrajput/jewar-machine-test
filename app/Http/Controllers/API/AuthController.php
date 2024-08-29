<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helpers;
use Tymon\JWTAuth\Facades\JWTAuth; 
use Tymon\JWTAuth\Exceptions\JWTException; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Illuminate\Validation\Rule;
use App\Events\UserOnline;
use App\Events\UserOffline;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getAllUsers()
    {
        try {
            $users = User::all(); 
            $formattedUsers = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'online_status' => $user->online_status,
                    'created_at' => $user->created_at->toDateTimeString(),
                    'updated_at' => $user->updated_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'users' => $formattedUsers,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }
    public function getUserById($id)
    {
        try {
            $user = User::findOrFail($id);

            $formattedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'online_status' => $user->online_status,
                'created_at' => $user->created_at->toDateTimeString(),
                'updated_at' => $user->updated_at->toDateTimeString(),
            ];

            return response()->json([
                'user' => $formattedUser,
                'status' => 200
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            }
    
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = auth('api')->login($user);
            $expiry = now()->addHours(1)->toDateTimeString(); 
            $user->online_status = true;
            return response()->json([
                'message' => 'User registered successfully',
                'token' => $token,
                'token_expiry' => $expiry,
                'status' => 200
            ], 201);
    
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    // public function login(Request $request)
    // {
    //     try {
    //         // Validate the request data
    //         $validator = Validator::make($request->all(), [
    //             'email' => 'required|email',
    //             'password' => 'required|min:6'
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //         }

    //         // Find the user by email
    //         $user = User::where('email', $request->email)->first();

    //         // Check if user exists and password is correct
    //         if (!$user || !Hash::check($request->password, $user->password)) {
    //             return response()->json(['errors' => ['email' => ['The provided credentials are incorrect.']]], 403);
    //         }

    //         // Generate the token
    //         $token = $user->createToken('authToken')->plainTextToken;
    //         $expiry = now()->addHours(1)->toDateTimeString();

    //         return response()->json([
    //             'token' => $token,
    //             'token_expiry' => $expiry,
    //             'user' => $user->name,
    //             'user_role' => $user->role,
    //             'status' => 200
    //         ], 200);

    //     } catch (ValidationException $e) {
    //         return response()->json(['errors' => $e->errors()], 422);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'An unexpected error occurred.'], 500);
    //     }
    // }
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 403);
            }

            $user = User::where('email', $request->email)->first();
            $user->online_status = true;
            $user->save();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['errors' => ['email' => ['The provided credentials are incorrect.']]], 403);
            }

            if (!$token = JWTAuth::fromUser($user)) {
                return response()->json(['errors' => ['error' => 'Unable to generate token.']], 500);
            }
            event(new UserOnline($user));
            return response()->json([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60, 
                'user' => $user->name,
                'user_email' => $user->email,
                'status' => 200
            ], 200);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    public function update(Request $request)
    {
        try {          
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:users,id',
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes','string','email','max:255', Rule::unique('users')->ignore($request->id)],
                'password' => 'sometimes|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            }
            $user = User::findOrFail($request->id);
            if ($user->role === 'admin') {
                return response()->json([
                    'message' => 'Admin users cannot be updated.',
                    'status' => 403
                ], 403);
            }
            // Update user details
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Return success response
            return response()->json([
                'message' => 'User updated successfully',
                'status' => 200
            ], 200);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            $user = Auth::user();
            $user->online_status = false;
            $user->save();
            event(new UserOffline($user));
            return response()->json([
                'message' => 'Logged out successfully',
                'status' => 200
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Failed to logout. Token could not be invalidated.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred.'
            ], 500);
        }
    }
}
