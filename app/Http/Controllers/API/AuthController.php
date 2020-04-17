<?php

namespace App\Http\Controllers\API;

use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function verifyRegisterToken(Request $request)
    {
        $request->validate(['token' => 'required|min:16']);
        $exists = DB::table('register_tokens')->where('token', $request->token)->exists();
        $msg  = $exists ? 'الكود صحيح' : 'كود التسجيل غير صحيح';
        return response()->json(['msg' => $msg, 'allow_register' => $exists], $exists ? 200 : 400);
    }

    public function signup(Request $request)
    {
        $validatedData = $request->validate($this->signupRules(), ['role.*' => 'Please Send User Role [playground || user]']);

        //check if playground register
        if ($validatedData['role'] == Roles::Playground) {
            $request->validate(['register_token' => 'required']);
            $registerToken = DB::table('register_tokens')->where('token', $request->register_token);
            //check if register token not exists
            if (!$registerToken->first()) {
                return response()->json(['msg' => 'كود التسجيل غير صحيح'], 400);
            }
        }

        //generate password
        $validatedData['password'] = Hash::make($validatedData['password']);

        //create new user
        $newUser = User::create($validatedData);

        //remove this register token
        if ($newUser && isset($registerToken) && !is_null($registerToken)) {
            $registerToken->delete();
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
            'phone'          => $sometimes.'required|digits:11',
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
        $rules['email'] = [
            'sometimes',
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($profile->id)
        ];
        //validate data
        $request->validate($rules);

        //edit exists fields
        if ($request->name) $profile->name = $request->name;
        if ($request->email) $profile->email = $request->email;
        if ($request->password) $profile->password = Hash::make($request->password);
        if ($request->phone) $profile->phone = $request->phone;
        if ($request->address) $profile->addess = $request->address;

        //save change
        $profile->save();

        return response()->json(['profile' => $this->getProfile($profile)]);
    }
}
