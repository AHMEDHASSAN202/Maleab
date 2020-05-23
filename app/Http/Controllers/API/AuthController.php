<?php

namespace App\Http\Controllers\API;

use App\Helpers\Config;
use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\PlaygroundInfo;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $validatedData = $request->validate($this->signupRules(), ['role.*' => 'Please Send User Role [playground || user]']);

        //check if playground register
        if ($validatedData['role'] == Roles::Playground) {
            $request->validate(['register_token' => 'required']);
            $registerToken = DB::table('register_tokens')->where('token', $request->register_token)->first();
            //check if register token not exists
            if (!$registerToken) {
                return response()->json(['status' => false, 'message' => 'كود التسجيل غير صحيح'], 400);
            }
            //validate playground info
            $playgroundInfoData = $request->validate([
                'lat' => 'required',
                'long' => 'required',
                'price_day' => 'sometimes|regex:/^\d*(\.\d{1,2})?$/',
                'price_night' => 'sometimes|regex:/^\d*(\.\d{1,2})?$/'
            ]);
        }

        //generate password
        $validatedData['password'] = Hash::make($validatedData['password']);

        //create new user
        $newUser = User::create($validatedData);

        if ($newUser->role == Roles::Playground) {
            $playgroundInfo = new PlaygroundInfo();
            $playgroundInfo->playground_id = $newUser->id;
            $playgroundInfo->lat = $playgroundInfoData['lat'];
            $playgroundInfo->long = $playgroundInfoData['long'];
            $playgroundInfo->price_day = $playgroundInfoData['price_day'];
            $playgroundInfo->price_night = $playgroundInfoData['price_night'];
            $playgroundInfo->save();
        }

        //remove this register token
        if ($newUser && isset($registerToken) && !is_null($registerToken)) {
            DB::table('register_tokens')->where('token', $registerToken->token)->delete();
        }

        //create auth token
        $accessToken = $this->getAccessToken($newUser);
        $profile = $this->getProfile($newUser);

        return response()->json(compact('profile', 'accessToken'));
    }

    private function signupRules($editProfile = false)
    {
        $sometimes = $editProfile ? 'sometimes|' : '';

        $rules = [
            'role'           => ['required', Rule::in([Roles::Playground, Roles::User])],
            'name'           => $sometimes."required|min:3|max:200",
            'email'          => $sometimes.'required|email|unique:users',
            'password'       => $sometimes.'required|min:6|max:100',
            'phone'          => $sometimes.'required|digits:11|unique:users',
            'address'        => $sometimes.'required|min:3|max:100',
        ];

        if ($editProfile) {
            unset($rules['role']);
        }

        return $rules;
    }

    private function getAccessToken(User $user)
    {
        return $user->createToken('maleaby')->accessToken;
    }

    public function signin(Request $request)
    {
        $certificate = $request->validate(['email' => 'required', 'password' => 'required']);
        $user = User::where('email', $certificate['email'])->first();

        //check email and password
        if (!$user || !Hash::check($certificate['password'], $user->password)) {
            return response()->json(['البريد الالكتروني او كلمة المرور غير صحيحة'], 400);
        }

        $accessToken = $this->getAccessToken($user);
        $profile = $this->getProfile($user);

        return response()->json(compact('profile', 'accessToken'));
    }

    private function getProfile($user)
    {
        if ($user->role == Roles::Playground) {
            $user->load('playgroundInfo');
        }

        return $user;
    }

    public function profile()
    {
        $profile = $this->getProfile(Auth::guard('api')->user());

        return response()->json(compact('profile'));
    }

    public function editProfile(Request $request)
    {
        $profile = Auth::guard('api')->user();

        //get validation rules
        $rules = $this->signupRules(true);

        //fix unique email rule
        $rules['email'] = ['sometimes', 'required', 'email',
            Rule::unique('users', 'email')->ignore($profile->id)
        ];
        $rules['phone'] = ['sometimes', 'required', 'digits:11',
            Rule::unique('users', 'phone')->ignore($profile->id)
        ];

        //validate data
        $request->validate($rules);

        //edit exists fields
        if ($request->name) $profile->name = $request->name;
        if ($request->email) $profile->email = $request->email;
        if ($request->password) $profile->password = Hash::make($request->password);
        if ($request->phone) $profile->phone = $request->phone;
        if ($request->address) $profile->address = $request->address;

        //save change
        $profile->save();

        if ($profile->role == Roles::Playground) {
            if ($request->lat) $profile->playgroundInfo->lat = $request->lat;
            if ($request->long) $profile->playgroundInfo->long = $request->long;
            if ($request->price_day) $profile->playgroundInfo->price_day = $request->price_day;
            if ($request->price_night) $profile->playgroundInfo->price_night = $request->price_night;
            $profile->playgroundInfo->save();
        }

        return response()->json(['status' => true, 'profile' => $this->getProfile($profile)]);
    }

    public function resetPassword(Request $request)
    {
        //validate data
        $request->validate(['phone' => 'required|exists:users']);
        //reset password code
        $code = mt_rand(1111, 9999);
        //remove records if exists
        DB::table('password_resets')->where('phone', $request->phone)->delete();
        //add new record
        DB::table('password_resets')->insert(['phone' => $request->phone, 'code' => $code]);
        //send sms
        $phone = Config::PhoneKey.$request->phone;
        $msg = getMsgCode(Config::MsgResetPassword, $code);
        _sendSmsByNexmo($phone, $msg);

        return response()->json(['status' => true]);
    }

    public function resetPasswordChange(Request $request)
    {
        //validate data
        $data = $request->validate(['code' => 'required', 'password' => 'required|confirmed|min:6|max:100']);
        //check code exists
        $codeRecord = DB::table('password_resets')->where('code', $data['code'])->first();
        if (!$codeRecord) {
            return response()->json(['status' => false, 'message' => "الكود غير صحيح"], 400);
        }
        //get user from phone
        $user = User::where('phone', $codeRecord->phone)->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => "حدث خطأ"], 400);
        }
        //update password
        $user->password = Hash::make($data['password']);
        $user->save();
        //delete code record
        DB::table('password_resets')->where('phone', $codeRecord->phone)->delete();

        return response()->json(['status' => true]);
    }

    public function deleteProfile()
    {
        $user = Auth::guard('api')->user();

        $user->delete();

        return response()->json(['status' => true]);
    }
}
