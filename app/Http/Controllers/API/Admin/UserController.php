<?php

namespace App\Http\Controllers\API\Admin;

use App\Helpers\Roles;
use App\Helpers\Utilities;
use App\Http\Controllers\Controller;
use App\PlaygroundInfo;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $query = User::select("users.*");

        //search
        if ($request->name) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }
        if ($request->email) {
            $query->where('email', 'LIKE', '%' . $request->email . '%');
        }
        if ($request->phone) {
            $query->where('phone', 'LIKE', '%' . $request->phone . '%');
        }
        if ($request->address) {
            $query->where('address', 'LIKE', '%' . $request->address . '%');
        }
        if ($request->role){
            $query->where('role', $request->role);
        }

        if ($request->role == Roles::Playground) {
            $query->addSelect("playground_info.lat", "playground_info.long", "playground_info.price_day", "playground_info.price_night", "playground_info.images")
                  ->join('playground_info', 'playground_id', '=', 'users.id');
            if ($request->lat && $request->long) {
                $distanceQuery = Utilities::distanceQuery($request->lat, $request->long, 'playground_info');
                $query->selectRaw("{$distanceQuery} as distance");
                $query->orderBy("distance", "ASC");
            }
        }

        $users = $query->get();

        if ($request->role == Roles::Playground) {
            $users = $users->map(function ($user) {
                unset($user->playground_id);
                if (isset($user->distance)) {
                    $user->distance = Utilities::convertMilesToKm($user->distance);
                }
                return $user;
            });
        }

        return response()->json(['status' => true, 'users' => $users]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json(['status' => true, 'user' => $user]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->rules());

        if ($validatedData['role'] == Roles::Playground) {
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

            $newUser->load('playgroundInfo');
        }

        return response()->json(['status' => true, 'user' => $newUser], 201);
    }

    private function rules($edit = false)
    {
        $sometimes = $edit ? 'sometimes|' : '';

        $rules = [
            'role'           => [$sometimes.'required', Rule::in(Roles::All)],
            'name'           => $sometimes."required|min:3|max:200",
            'email'          => $sometimes.'required|email|unique:users',
            'password'       => $sometimes.'required|min:6|max:100',
            'phone'          => $sometimes.'required|digits:11|unique:users,phone',
            'address'        => $sometimes.'required|min:3|max:100',
        ];

        return $rules;
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        //get validation rules
        $rules = $this->rules(true);
        //fix unique email rule
        $rules['email'] = ['sometimes', 'required', 'email',
            Rule::unique('users', 'email')->ignore($user->id)
        ];
        //fix unique phone rule
        $rules['phone'] = ['sometimes', 'required', 'digits:11',
            Rule::unique('users', 'phone')->ignore($user->id)
        ];
        //validate data
        $request->validate($rules);

        //edit exists fields
        if ($request->name) $user->name = $request->name;
        if ($request->email) $user->email = $request->email;
        if ($request->password) $user->password = Hash::make($request->password);
        if ($request->phone) $user->phone = $request->phone;
        if ($request->address) $user->address = $request->address;
        if ($request->role) $user->role = $request->role;

        //save change
        $user->save();

        if ($user->role == Roles::Playground) {
            $playgroundInfo = $profile->playgroundInfo ?? new PlaygroundInfo(['playground_id' => $user->id]);
            if ($request->lat) $playgroundInfo->lat = $request->lat;
            if ($request->long) $playgroundInfo->long = $request->long;
            if ($request->price_day) $playgroundInfo->price_day = $request->price_day;
            if ($request->price_night) $playgroundInfo->price_night = $request->price_night;
            $playgroundInfo->save();

            $user->load('playgroundInfo');
        }

        return response()->json(['status' => true, 'user' => $user]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json(['status' => true]);
    }
}
