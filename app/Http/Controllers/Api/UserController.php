<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function show()
    {
        $user = getUser(auth()->user()->id);

        return ResponseFormatter::success([$user], '', 200);
    }

    public function getUserByUsername(Request $request, $username)
    {
        $user = User::select('id', 'username', 'verified', 'profile_picture')
            ->where('username', 'LIKE', '%' . $username . '%')
            ->where('id', '<>', auth()->user()->id)
            ->get();

        $user->map(function ($item) {
            $item->profile_picture = $item->profile_picture ? url('storage/' . $item->profile_picture) : '';
            return $item;
        });

        return ResponseFormatter::success([$user], '', 200);
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);
            $data = $request->only('name', 'username', 'ktp', 'email', 'password');

            if ($request->username != $user->username) {
                $isExistUsername = User::where('username', $request->username)->exists();
                if ($isExistUsername) {
                    return ResponseFormatter::error([], 'This username already taken', 400);
                }
            }

            if ($request->email != $user->email) {
                $isExistEmail = User::where('email', $request->email)->exists();
                if ($isExistEmail) {
                    return ResponseFormatter::error([], 'This email already taken', 400);
                }
            }

            if ($request->password) {
                $data['password'] = bcrypt($request->password);
            }

            if ($request->profile_picture) {
                $profilePicture = uploadBase64Image($request->profile_picture);
                $data['profile_picture'] = $profilePicture;
                if ($user->profile_picture) {
                    Storage::delete('public/' . $user->profile_picture);
                }
            }

            if ($request->ktp) {
                $ktp = uploadBase64Image($request->ktp);
                $data['ktp'] = $ktp;
                $data['verified'] = true;
                if ($user->ktp) {
                    Storage::delete('public/' . $user->ktp);
                }
            }

            $user->update($data);
            return ResponseFormatter::success([], 'User Updated', 200);
        } catch (\Throwable $th) {
            return ResponseFormatter::error([], $th->getMessage(), 500);
        }
    }

    public function isEmailExist(Request $request)
    {
        $validator = Validator::make($request->only('email'), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);
        }

        $isExist = User::where('email', $request->email)->exists();
        return ResponseFormatter::success(['is_email_exists' => $isExist], '', 200);
    }

    public function logout()
    {
        auth()->logout();

        return ResponseFormatter::success([], 'Log out success', 200);
    }
}
