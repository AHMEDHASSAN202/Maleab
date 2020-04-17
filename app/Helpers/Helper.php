<?php
/**
 * Created by PhpStorm.
 * User: AHMED HASSAN
 */


//check if current route
if (!function_exists('isRoute')) {
    function isRoute($route_name) : bool
    {
        $uri_first_segment = @sscanf(urldecode(route($route_name)), url('') . '/%s')[0];
        return urldecode(url()->current()) == urldecode(route($route_name)) || request()->is(sprintf('*/%s', $uri_first_segment));
    }
}
