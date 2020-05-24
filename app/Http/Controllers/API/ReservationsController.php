<?php

namespace App\Http\Controllers\API;

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

        $reservationData['user_id'] = Auth::id();
        $reservationData['status'] = 'pending';

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
}
