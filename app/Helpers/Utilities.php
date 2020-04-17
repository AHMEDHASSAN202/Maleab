<?php
/**
 * Created by PhpStorm.
 * User: AHMED HASSAN
 */

namespace App\Helpers;


use Illuminate\Support\Facades\DB;

class Utilities
{

    private static $settings = null;

    /**
     * Get Setting
     *
     * @param $key
     * @param $table
     * @return null
     */
    public static function setting($key, $table)
    {
        if (self::$settings == null) {
            self::$settings = \Illuminate\Support\Facades\DB::table($table)->get();
        }
        return _objectGet(self::$settings->where('key', $key)->first(), 'value');
    }

    /**
     * Update Setting
     *
     * @param $column
     * @param $updateValues
     * @return int
     */
    public static function updateSetting($column, $updateValues)
    {
        $exists = DB::table('settings')->where('key', $column)->count();
        if ($exists) {
            return DB::table('settings')->where('key', $column)->update($updateValues);
        }
        return DB::table('settings')->insert(array_merge($updateValues, ['key' => $column]));
    }

}
