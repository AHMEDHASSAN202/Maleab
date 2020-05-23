<?php
/**
 * Created by PhpStorm.
 * User: AHMED HASSAN
 */

/**
 * Handle SMS Msg
 *
 * @param $msg
 * @param $code
 * @return mixed|string
 */
function getMsgCode($msg, $code)
{
    if (strpos($msg, '[code]') !== false) {
        $msg = str_replace("[code]", $code, $msg);
    }else {
        $msg = $msg . ' ' . $code;
    }
    return $msg;
}
