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

        return ResponseFormatter::success($user, '', 200);
    }

    public function getUserByUsername(Request $request, $username)
    {
        $user = User::select('id', 'name', 'username', 'verified', 'profile_picture')
            ->where('username', 'LIKE', '%' . $username . '%')
            ->where('id', '<>', auth()->user()->id)
            ->get();

        $user->map(function ($item) {
            $item->profile_picture = $item->profile_picture ? url('storage/' . $item->profile_picture) : '';
            return $item;
        });

        return ResponseFormatter::success($user, '', 200);
    }

    public function update(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);
            $data = $request->only('username', 'name', 'email', 'password', 'ktp', 'profile_picture');

            if ($request->username != $user->username) {
                $isExistUsername = User::where('username', $request->username)->exists();
                if ($isExistUsername) {
                    return ResponseFormatter::error(message: 'This username already taken', code: 400);
                }
            }

            if ($request->email != $user->email) {
                $isExistEmail = User::where('email', $request->email)->exists();
                if ($isExistEmail) {
                    return ResponseFormatter::error(message: 'This email already taken', code: 400);
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
            return ResponseFormatter::success('', 'User Updated', 200);
        } catch (\Throwable $th) {
            return ResponseFormatter::error(message: $th->getMessage(), code: 500);
        }
    }

    public function isDataExist(Request $request)
    {
        $validator = Validator::make($request->only('is_email_exists', 'is_username_exists'), [
            'is_email_exists' => 'required|email',
            'is_username_exists' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(message: 'Validation Failed', code: 400);
        }

        $token = sendNotifToUser('fWIU1Y2XRJmJvzt3PtFNSM:APA91bElh-9kQYRY_qpYkwtGoVQDeNGlT4Sf-k4O5Qs3slrWNbrfZ75ZhbjWqMndb7lZ--5vgZAHy48MzsARaJ-jpIFZrD7ZPbvCsrut4GD9opeJDy4AhUUqJUuOciUlZYfauimAJ3Bc', 'From Laravel', 'FCM Message');
        $isEmailExist = User::where('email', $request->is_email_exists)->exists();
        $isUsernameExists = User::where('username', $request->is_username_exists)->exists();

        return ResponseFormatter::success([
            'is_email_exists' => $isEmailExist,
            'is_username_exists' => $isUsernameExists,
            'token' => $token,
        ], '', 200);
    }

    public function logout()
    {
        auth()->logout();

        return ResponseFormatter::success('', 'Log out success', 200);
    }
}
