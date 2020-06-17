<?php

namespace App\Http\Controllers\API;

use App\Helpers\Config;
use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationsController extends Controller
{

    public function store(Request $request)
    {
        $reservationData = $request->validate([
            'playground_id' => 'required|exists:users,id',
            'day'           => 'required|date|after_or_equal:' . date("d-m-Y"),
            'start'         => 'required|date_format:H:i',
            'end'           => 'required|date_format:H:i',
            'notes'         => 'sometimes|required'
        ]);

        $me = Auth::guard('api')->user();

        if ($me->role == Roles::User) {
            $userId = $me->id;
        }else {
            $request->validate(['user_id' => 'required', ['user_id.required' => 'صاحب الحجز مطلوب']]);
            $userId = $request->user_id;
        }

        $reservationData['user_id'] = $userId;
        $reservationData['status'] = $request->status ?? 'pending';

        $reservation = Reservation::create($reservationData);

        return response()->json(['status' => true, 'reservation' => $reservation]);
    }


    public function show($id)
    {
        $reservation = Reservation::with('playground', 'user')->findOrFail($id);

        return response()->json(['status' => true, 'reservation' => $reservation]);
    }

    public function index(Request $request)
    {
        $query = Reservation::query();

        $r = ['playground', 'user'];

        if ($request->playground_id) {
            $query->where('playground_id', $request->playground_id);
            $r = ['user'];
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
            $r = ['playground'];
        }
        if ($request->day) {
            $query->where('day', $request->day);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reservations = $query->with(...$r)->get();

        return response()->json(['status' => true, 'reservations' => $reservations]);
    }

    public function update(Request $request, $reservation_id)
    {
        $reservation = Reservation::findOrFail($reservation_id);

        if ($reservation->status == Config::PreventEditWhenStatus) {
            return response()->json(['status' => false, 'message' => 'عفوا! لا يمكنك التعديل على الحجز'], 400);
        }

        $me = Auth::guard('api')->user();

        if (!in_array($me->id, [$reservation->user_id, $reservation->playground_id])) {
            return response()->json(['status' => false], 400);
        }

        $reservationData = $request->validate([
            'day'           => 'sometimes|date|after_or_equal:' . date("d-m-Y"),
            'start'         => 'sometimes|date_format:H:i',
            'end'           => 'sometimes|date_format:H:i',
            'status'        => 'sometimes',
            'notes'         => 'sometimes'
        ]);

        if ($me->role != Roles::User) {
            $request->validate(['user_id' => 'required', ['user_id.required' => 'صاحب الحجز مطلوب']]);
            $reservationData['user_id'] = $request->user_id;
        }

        if (!empty($reservationData['user_id'])) $reservation->user_id = $reservationData['user_id'];
        if (!empty($reservationData['day'])) $reservation->day = $reservationData['day'];
        if (!empty($reservationData['start'])) $reservation->start = $reservationData['start'];
        if (!empty($reservationData['end'])) $reservation->end = $reservationData['end'];
        if (!empty($reservationData['status'])) $reservation->status = $reservationData['status'];
        if (!empty($reservationData['notes'])) $reservation->notes = $reservationData['notes'];

        $reservation->save();

        return response(['status' => true, 'reservation' => $reservation]);
    }
}
