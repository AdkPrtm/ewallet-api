<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'pin' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);
        }

        $user = User::where('email', $request->email)->exists();

        if ($user) {
            return ResponseFormatter::error([], 'Email already taken', 400);
        }

        $user = User::where('username', $request->username)->exists();

        if ($user) {
            return ResponseFormatter::error([], 'Username already taken', 400);
        }


        DB::beginTransaction();

        try {
            $profilePicture = null;
            if ($request->profile_picture) {
                $profilePicture = uploadBase64Image($request->profile_picture);
            }

            $ktp = null;
            if ($request->ktp) {
                $ktp = uploadBase64Image($request->ktp);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => $request->password,
                'profile_picture' => $profilePicture,
                'ktp' => $ktp,
                'verified' => ($ktp) ? true : false
            ]);

            $cardNumber = $this->generateCardNumber(16);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pin' => $request->pin,
                'card_number' => $cardNumber
            ]);

            $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);
            DB::commit();

            $userResponse = getUser($user->id);
            $userResponse->token = $token;
            $userResponse->token_expires_in = JWTAuth::factory()->getTTL() * 180;
            $userResponse->token_type = 'bearer';

            return ResponseFormatter::success([$userResponse], '', 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return ResponseFormatter::error([], $th->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);
        }

        try {
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                return ResponseFormatter::error([], 'Invalid Credentials', 400);
            }

            $userResponse = getUser($request->email);
            $userResponse->token = $token;
            $userResponse->token_expires_in = JWTAuth::factory()->getTTL() * 180;
            $userResponse->token_type = 'bearer';

            return ResponseFormatter::success([$userResponse], '', 200);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $th) {
                return ResponseFormatter::error([], 'Something went wrong', 500);
        }
    }

    private function generateCardNumber($length)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        $wallet = Wallet::where('card_number', $result)->exists();

        if ($wallet) {
            return $this->generateCardNumber($length);
        }

        return $result;
    }
}
