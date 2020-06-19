<?php

namespace App\Http\Controllers\API;

use App\Helpers\Roles;
use App\Helpers\Utilities;
use App\Http\Controllers\Controller;
use App\Reservation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlaygroundController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select("users.*", "playground_info.lat", "playground_info.long", "playground_info.price_day", "playground_info.price_night", "playground_info.status")
                        ->join("playground_info", "playground_id", "=", "users.id")
                        ->where('role', Roles::Playground);

        //filter by name
        if ($request->name) {
            $query->where('name', 'LIKE', '%' . $request->name . '%');
        }

        //filter by phone
        if ($request->phone) {
            $query->where('phone', 'LIKE', '%' .   $request->phone . '%');
        }

        //get distance
        if ($request->lat && $request->long) {
            $distanceQuery = Utilities::distanceQuery($request->lat, $request->long, 'playground_info');
            $query->selectRaw("{$distanceQuery} as distance");
            $query->orderBy("distance", "ASC");
        }

        $playgrounds = $query->with('playgroundImages')->get()->map(function ($playground) {
            unset($playground->playground_id);
            if (isset($playground->distance)) {
                $playground->distance = Utilities::convertMilesToKm($playground->distance);
            }
            return $playground;
        });

        return response()->json(['status' => true, 'playgrounds' => $playgrounds]);
    }

    public function show(Request $request, $playground_id)
    {
        $query = User::select("users.*", "playground_info.lat", "playground_info.long", "playground_info.price_day", "playground_info.price_night")
            ->join("playground_info", "playground_id", "=", "users.id")
            ->where('role', Roles::Playground)
            ->where('users.id', $playground_id);

        if ($request->lat && $request->long) {
            $distanceQuery = Utilities::distanceQuery($request->lat, $request->long, 'playground_info');
            $query->selectRaw("{$distanceQuery} as distance");
        }

        $playground = $query->with('playgroundImages')->firstOrFail();

        if (isset($playground->distance)) {
            $playground->distance = Utilities::convertMilesToKm($playground->distance);
        }

        return response()->json(['status' => true, 'playground' => $playground]);
    }


    public function deleteImage(Request $request)
    {
        $request->validate(['ids' => 'required'], ['ids.required' => 'مطلوب صورة على الاقل']);
        $ids = is_array($request->ids) ? $request->ids : [$request->ids];
        $me_playground = Auth::guard('api')->user();
        $imgs = $me_playground->playgroundImages()->whereIn('id', $ids)->get();
        $imgs->map(function ($img) {
            Storage::disk('public')->delete($img->image);
        });
        $me_playground->playgroundImages()->whereIn('id', $ids)->delete();
        return response()->json(['status' => true]);
    }


    public function getUsers(Request $request)
    {
        $myId = Auth::guard('api')->id();

        $users = User::select('users.id', 'users.name', 'users.avatar', 'users.phone')
                       ->join('reservations', function ($j) use ($myId) {
                           $j->on('users.id', '=', 'reservations.user_id')
                             ->where('reservations.playground_id', $myId);
                       })
                       ->get();

        $users = $users->unique();

        if ($request->has('withReservations')) {
            $users = $users->map(function ($user) use ($myId) {
                $user->load(['reservations' => function ($q) use ($myId) {
                    $q->where('playground_id', $myId);
                }]);
                return $user;
            });
        }

        $reservationAnonymousUsers = Reservation::where('playground_id', $myId)->whereNull('user_id')->get()->where('status', '!=', 'busy');

        return response()->json(compact('users', 'reservationAnonymousUsers'));
    }
}
