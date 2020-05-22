<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param $roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $me = Auth::guard('api')->user();
        $my_role = $me->role;

        //admin,artist
        //OR
        //admin,!artist
        $original_roles = [];

        foreach ($roles as $role) {
            if ($role[0] == '!') {
                $original_name = substr($role, 1);
                if ($my_role == $original_name) {
                    return $this->unauthorizedResponse();
                }
            }else {
                array_push($original_roles, $role);
            }
        }

        if (!empty($original_roles)) {
            if (!in_array($my_role, $original_roles)) {
                return $this->unauthorizedResponse();
            }
        }

        return $next($request);
    }

    private function unauthorizedResponse()
    {
        return response()->json(['status' => false, 'msg' => 'unauthorized'], 403);
    }
}
