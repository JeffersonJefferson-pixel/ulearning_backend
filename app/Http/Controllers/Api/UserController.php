<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function loginUser(Request $request) 
    {
        try {
            $validateUser = Validator::make($request->all(), 
            [
                'avatar' => 'required',
                'type' => 'required',
                'open_id' => 'required',
                'name' => 'required',
                'email' => 'required',
                // 'password' => 'required|min:6'
            ]);
            
            if($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $validated = $validateUser->validated();

            $map=[];
            // email, phone, google, facebook, apple
            $map['type'] = $validated['type'];
            $map['open_id'] = $validated['open_id'];

            $user = User::where($map)->first();

            // whether user has already registered
            if (empty($user->id)) {
                // save user in db
                // token ~ id
                $validated["token"] = md5(uniqid().rand(10000, 99999));
                $validated["created_at"] = Carbon::now();
                // encrypt password
                // $validated["password"] = Hash::make($validated["password"]);
                $userID = User::insertGetId($validated);

                $userInfo = User::where('id', '=', $userID)->first();

                $accessToken = $userInfo->createToken(uniqid())->plainTextToken;

                $userInfo->access_token = $accessToken;
                
                User::where('id', '=', $userID)->update(['access_token'=>$accessToken]);

                return response()->json([
                    'code' => 200,
                    'msg' => 'User Created Successfully',
                    'data' => $userInfo
                ], 200);
            }

            // user has registered
            $accessToken = $user->createToken(uniqid())->plainTextToken;
            $user->access_token = $accessToken;
            User::where('id', '=', $user->id)->update(['access_token'=>$accessToken]);
            return response()->json([
                'code' => 200,
                'msg' => 'User logged in Successfully',
                'data' => $user
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
