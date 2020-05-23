<?php
/**
 * Created by PhpStorm.
 * User: AHMED HASSAN
 */



if (!function_exists('_isImage')) {
    function _isImage($extension)
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'gif', 'png', 'bmp', 'svg']);
    }
}


if (!function_exists('_search')) {
    function _search($query, $search_fields = [])
    {
        $new_query = $query;
        foreach ($search_fields as $key => $value) {
            if (request()->{$key} !== null) {
                $new_query->where($value, 'LIKE', '%' . request()->{$key} . '%');
            }
        }
        return $new_query;
    }
}


if (!function_exists('_symlink')) {
    function _symlink($rootName, $projectName)
    {
        $to = '/home/'. $rootName .'/public_html/storage';
        if (file_exists($to)) {
            return null;
        }
        symlink('/home/'. $rootName .'/'. $projectName .'/storage/app/public', $to);
    }
}



if (!function_exists('_imagePath')) {
    function _imagePath($path)
    {
        return trim(str_replace(['//', '\\', '/'], '/', $path));
    }
}


if (!function_exists('_prepareSearchString')) {
    function _prepareSearchString($search)
    {
        $chars = [
            ['أ', 'ا', 'آ'],
            ['ة', 'ه'],
            ['ي', 'ى'],
            ['ئ', 'ء', 'ؤ']
        ];


        $searches = [];

        foreach ($chars as $replaces) {
            foreach ($replaces as $replace) {
                if (strpos($search, $replace) !== false) {
                    array_map(function ($r) use (&$searches, $replace, $search) {
                        $searches[] = str_replace($replace, $r, $search);
                    }, $replaces);
                }
            }
        }

        $searches = array_unique($searches);

        return empty($searches) ? $search : $searches;
    }
}


if (!function_exists('geolocation')) {
    function geolocation($accessKey = 'ea6fde4fdb42e4e1faf6b02e67685a28')
    {
        $ip = request()->ip();
        $curl = curl_init('http://api.ipstack.com/'. $ip .'?access_key=' . $accessKey);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);
        curl_close($curl);

        return json_decode($data);
    }
}


if (!function_exists('_setting')) {
    function _setting($key, $default = null)
    {
        return \App\Helpers\Utilities::setting($key, 'settings') ?? $default;
    }
}


if (!function_exists('_clearAfterUpdateOrDelete')) {
    function _clearAfterUpdateOrDelete($file)
    {
        if (!$file) return null;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($file);
        }
    }
}


if (!function_exists('_arrayGet')) {
    function _arrayGet($array, $key, $default = null)
    {
        if (is_null($array)) return $default;
        if (!is_array($array)) return $default;
        if (!isset($array[$key])) return $default;
        return $array[$key];
    }
}


if (!function_exists('_objectGet')) {
    function _objectGet($object, $key, $default = null)
    {
        if (is_null($object)) return $default;
        if (!is_object($object)) return $default;
        if (!property_exists($object, $key)) return $default;
        return $object->{$key};
    }
}


/**
 * Add Logo PNG To Group From Images Or One Image
 *
 * @param $path
 * @param $logo
 * @param $output_dir
 * @param int $logo_width
 * @param int $logo_height
 * @param int $margin_right
 * @param int $margin_bottom
 * @param bool $resize
 * @return bool|null
 */
if (!function_exists('_addLogoToImage')) {
    function _addLogoToImage($path, $logo, $output_dir, $logo_width = 100, $logo_height = 100, $margin_right = 0, $margin_bottom = 0, $new_if_exists = false, $resize = false)
    {
        //check if path not exists || not readable
        if (!file_exists($path) || !is_readable($path)) return null;

        //images on dir or one image path
        $images = is_dir($path) ? new FilesystemIterator($path) : [new SplFileInfo($path)];

        //Handle Logo
        //get logo extension
        $logoExtension = pathinfo($logo, PATHINFO_EXTENSION);
        if ($logoExtension != 'png') return null;
        //logo resource
        $logoResource = imagecreatefrompng($logo);
        //resize logo image
        $logoAfterResize = $resize ? imagecropauto($logoResource) : imagescale($logoResource, $logo_width, $logo_height);

        //Handle Images
        //loop on all images
        foreach ($images as $img) {

            //check if img is already exists in outdir
            if (file_exists($output_dir . DIRECTORY_SEPARATOR . $img->getFilename())) {
                continue;
            }

            $extension = $img->getExtension() == 'jpg' ? 'jpeg' : $img->getExtension(); //get image extension
            $new_image_path = $output_dir . DIRECTORY_SEPARATOR . $img->getFilename();

            //check if this image is exist
            if ($new_if_exists === false) {
                if (file_exists($new_image_path)) {
                    continue;
                }
            }

            $createResource = "imagecreatefrom$extension"; //create resource function name
            $outputImage = "image$extension"; //create output image function name

            if (!function_exists($createResource)) continue;
            if (!function_exists($outputImage)) continue;

            $resource = $createResource($img); //create image resource

            //handle logo position
            if ($margin_right > 0) {
                $dst_x = imagesx($resource) - imagesx($logoAfterResize) - $margin_right;
            }else {
                $dst_x = abs($margin_right);
            }
            if ($margin_bottom > 0) {
                $dst_y = imagesy($resource) - imagesy($logoAfterResize) - $margin_bottom;
            }else {
                $dst_y = abs($margin_bottom);
            }

            //copy from image to new image with logo
            $imageWithLogo = imagecopy(
                $resource,
                $logoAfterResize,
                $dst_x,
                $dst_y,
                0,
                0,
                imagesx($logoAfterResize),
                imagesy($logoAfterResize)
            );

            //image output
            if (!is_dir($output_dir)) mkdir($output_dir, 0777, true);

            $outputImage($resource,  $new_image_path);

            //destroy resources
            imagedestroy($resource);
        }
        imagedestroy($logoResource);

        return true;
    }
}


/**
 * Generate Thumbnails
 *
 * @param $from
 * @param $to
 * @param null $width_thumbnail
 * @param null $height_thumbnail
 * @param null $ratio
 * @return bool|null
 */
if (!function_exists('_makeThumbnails')) {
    function _makeThumbnails($from, $to, $width_thumbnail = null, $height_thumbnail = null, $ratio = null, $new_if_exists = false)
    {
        //check if path not exists || not readable
        if (!file_exists($from) || !is_readable($from)) return null;

        //images on dir or one image path
        $images = is_dir($from) ? new FilesystemIterator($from) : [new SplFileInfo($from)];

        //check dist directory create it if not exists
        if (!is_dir($to)) mkdir($to, 0777, true);

        //handle images
        foreach ($images as $image) {
            $extension = $image->getExtension() == 'jpg' ? 'jpeg' : $image->getExtension(); //get image extension
            $new_image_path = $to . DIRECTORY_SEPARATOR . $image->getFilename();
            //check if this image is exist
            if ($new_if_exists === false) {
                if (file_exists($new_image_path)) {
                    continue;
                }
            }
            $createResource = "imagecreatefrom$extension"; //create resource function name
            $outputImage = "image$extension"; //create output image function name
            if (!function_exists($createResource)) continue;
            if (!function_exists($outputImage)) continue;
            $resource = $createResource($image); //create image resource

            // get original image width and height
            $width = imagesx($resource);
            $height = imagesy($resource);

            if ($ratio != null && is_numeric($ratio)) {
                if ($ratio > 1) $ratio = $ratio / 100;
                $height_thumbnail = $height * $ratio;
                $width_thumbnail = $width * $ratio;
            }

            //get image type
            $type = exif_imagetype($image->getRealPath());

            // create duplicate image based on calculated target size
            $thumbnail = imagecreatetruecolor($width_thumbnail, $height_thumbnail);

            // set transparency options for GIFs and PNGs
            if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {

                // make image transparent
                imagecolortransparent(
                    $thumbnail,
                    imagecolorallocate($thumbnail, 0, 0, 0)
                );

                // additional settings for PNGs
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($thumbnail, false);
                    imagesavealpha($thumbnail, true);
                }
            }

            // copy entire source image to duplicate image and resize
            imagecopyresampled(
                $thumbnail,
                $resource,
                0, 0, 0, 0,
                $width_thumbnail, $height_thumbnail,
                $width, $height
            );

            //save the duplicate version of the image to disk
            $outputImage($thumbnail, $new_image_path);

            //destroy resources
            imagedestroy($resource);
            imagedestroy($thumbnail);
        }
        return true;
    }
}


/**
 * Create Date Form Format || Check Validate Date
 *
 * @param $date
 * @param $format
 * @return null || object
 */
if (!function_exists('_createDateFromFormat')) {
    function _createDateFromFormat($date, $format = 'Y-m-d')
    {
        return DateTime::createFromFormat($format, $date);
    }
}



if (!function_exists('_sendSmsByNexmo')) {
    function _sendSmsByNexmo($phone, $msg = null)
    {
        $Nexmo_API_KEY = env('Nexmo_API_KEY', '7c52606f');
        $Nexmo_API_SECRET = env('Nexmo_API_SECRET', 'iWppLoIiqMm1qLHM');

        $basic  = new \Nexmo\Client\Credentials\Basic($Nexmo_API_KEY, $Nexmo_API_SECRET);
        $client = new \Nexmo\Client($basic);

        try {
            $message = $client->message()->send([
                'to' => $phone,
                'from' => config('app.name'),
                'text' => $msg,
                'type' => 'unicode'
            ]);
        }catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}


/**
 * Convert hex To RGBa
 *
 * @param $hex
 * @return string
 */
if (!function_exists('rgba')) {
    function rgba($hex, $a = 1) {
        sscanf($hex, '#%2x%2x%2x', $r, $g, $b);
        $rgba = sprintf('rgba(%d,%d,%d,%f)', $r, $g, $b, $a);
        return $rgba;
    }
}


/**
 * Generate URL Slug
 *
 * @param $from
 * @return string
 */
if (!function_exists('_slug')) {
    function _slug($from) {
        $val = preg_replace('/[ -]+/', '-', $from);
        $val = preg_replace('/[`~!@#$%^&*()_|+\=?;:\'",.<>\{\}\[\]\\\]+/', '', $val);
        return trim($val);
    }
}
