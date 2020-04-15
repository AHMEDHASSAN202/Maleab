<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function verifyRegisterToken(Request $request)
    {
        $request->validate(['token' => 'required|min:16']);
        $exists = DB::table('register_tokens')->where('token', $request->token)->exists();
        $msg  = $exists ? 'الكود صحيح' : 'خطأ في الكود';
        return response()->json(['msg' => $msg, 'allow_register' => $exists], $exists ? 200 : 403);
    }
}
