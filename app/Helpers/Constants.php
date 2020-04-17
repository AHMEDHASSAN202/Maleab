<?php
/**
 * Created by PhpStorm.
 * User: AHMED HASSAN
 */


namespace App\Helpers;


class Roles {
    const Admin = 'admin';
    const Playground = 'playground';
    const User = 'user';
    const All = [self::Admin, self::Playground, self::User];
}


class Config {
    const LogoWidth = 100;
    const LogoHeight = 100;
    const ThumbnailHeight = 300;
    const ThumbnailsWidth = 350;
}

