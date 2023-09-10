<?php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Storage;
use Melihovv\Base64ImageDecoder\Base64ImageDecoder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

function getUser($param)
{
  $user = User::where('id', $param)->orWhere('email', $param)->orWhere('username', $param)->first();

  $wallet = Wallet::where('user_id', $user->id)->first();
  $user->profile_picture = $user->profile_picture ? url('storage/' . $user->profile_picture) : '';
  $user->ktp = $user->ktp ? url('storage/' . $user->ktp) : '';

  $user->balance = $wallet->balance;
  $user->card_number = $wallet->card_number;
  $user->pin = $wallet->pin;

  return $user;
}

function pinChecker($pin)
{
  $userId = auth()->user()->id;
  $wallet = Wallet::where('user_id', $userId)->first();

  if (!$wallet) return false;

  if ($wallet->pin == $pin) return true;

  return false;
}

function uploadBase64Image($base64image)
{
  $decoder = new Base64ImageDecoder($base64image, ['jpeg', 'jpg', 'png']);
  $decodedContent = $decoder->getDecodedContent();
  $format = $decoder->getFormat();
  $image = Str::random(10) . '.' . $format;
  Storage::disk('public')->put($image, $decodedContent);

  return $image;
}

function getGoogleAccessToken()
{
  $credentialsFilePath = app_path('Helpers/service_account.json'); //replace this with your actual path and file name
  $client = new \Google_Client();
  $client->setAuthConfig($credentialsFilePath);
  $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
  $client->refreshTokenWithAssertion();
  $token = $client->getAccessToken();
  return $token['access_token'];
}

function sendNotifToUser($deviceToken, $title, $body)
{
  $apiurl = env('API_URL_FCM');

  $body = [
    'message' => [
      'token' => $deviceToken,
      'notification' => [
        'body' => $body,
        'title' => $title,
      ]
    ]
  ];
  $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . getGoogleAccessToken(),
  ])->withBody(json_encode($body))->post($apiurl);

  $result = $response->successful();
  return $result;
}
