<?php

namespace App\Http\Controllers\API\Admin;

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

    public function signup(Request $request)
    {
        $validatedData = $request->validate($this->signupRules());

        //generate password
        $validatedData['password'] = Hash::make($validatedData['password']);

        //ser admin role
        $validatedData['role'] = Roles::Admin;

        //create new user
        $newUser = User::create($validatedData);

        //create auth token
        $accessToken = $this->getAccessToken($newUser);
        $profile = $this->getProfile($newUser);

        return response()->json(compact('profile', 'accessToken'));
    }

    private function signupRules($editProfile = false)
    {
        $sometimes = $editProfile ? 'sometimes|' : '';

        $rules = [
            'name'           => $sometimes."required|min:3|max:200",
            'email'          => $sometimes.'required|email|unique:users',
            'password'       => $sometimes.'required|min:6|max:100',
            'phone'          => $sometimes.'required|digits:11',
            'address'        => $sometimes.'required|min:3|max:100',
        ];

        return $rules;
    }

    private function getAccessToken(User $user)
    {
        return $user->createToken('maleaby')->accessToken;
    }

    public function signin(Request $request)
    {
        $certificate = $request->validate(['email' => 'required', 'password' => 'required']);
        $user = User::where('email', $certificate['email'])->where('role', Roles::Admin)->first();

        //check email and password
        if (!$user || !Hash::check($certificate['password'], $user->password)) {
            return response()->json(['status' => false, 'msg' => 'البريد الالكتروني او كلمة المرور غير صحيحة'], 400);
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
        if ($request->address) $profile->address = $request->address;

        //save change
        $profile->save();

        return response()->json(['profile' => $this->getProfile($profile)]);
    }

    public function createRegisterToken(Request $request)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(8));
        $data = ['token' => $token];

        $t = DB::table('register_tokens')->insert($data);

        if (!$t) {
            return response()->json(['status' => false, 'msg' => 'something error'], 400);
        }

        return response()->json(['status' => true, 'token' => $data['token']], 201);
    }
}
