<?php

namespace App\Http\Controllers\API\Admin;

use App\Helpers\Config;
use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $certificate = $request->validate(['email' => 'required', 'password' => 'required']);
        $user = User::where('email', $certificate['email'])->where('role', Roles::Admin)->first();

        //check email and password
        if (!$user || !Hash::check($certificate['password'], $user->password)) {
            return response()->json(['status' => false, 'message' => 'البريد الالكتروني او كلمة المرور غير صحيحة'], 400);
        }

        $accessToken = $this->getAccessToken($user);
        $profile = $this->getProfile($user);

        return response()->json(compact('profile', 'accessToken'));
    }

    private function getAccessToken(User $user)
    {
        return $user->createToken('maleaby')->accessToken;
    }

    private function getProfile($user)
    {
        return $user;
    }

    public function profile()
    {
        $profile = $this->getProfile(Auth::guard('api')->user());
        return response()->json(compact('profile'));
    }

    public function createRegisterToken(Request $request)
    {
        $request->validate(['phone' => 'required']);

        $token = mt_rand(1111,9999);
        $data = ['token' => $token];

        $t = DB::table('register_tokens')->insert($data);

        if (!$t) {
            return response()->json(['status' => false, 'message' => 'something error'], 400);
        }

        //send sms
        if (Config::AllowedSendSMS) {
            $phoneNumber = Config::PhoneKey.$request->phone;
            $msg = getMsgCode(Config::MsgSendCode, $token);
            _sendSmsByNexmo($phoneNumber, $msg);
        }

        return response()->json(['status' => true], 201);
    }
}
