<?php

namespace TCG\Voyager\Support;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Compatibility layer over Intervention Image 3.x and 4.x.
 *
 * Laravel 13 ships its own `image` container binding backed by
 * Intervention Image 4, so Voyager builds its manager explicitly
 * instead of resolving the facade binding.
 */
class Image
{
    public static function manager()
    {
        if (method_exists(ImageManager::class, 'usingDriver')) {
            return ImageManager::usingDriver(Driver::class);
        }

        return ImageManager::gd();
    }

    public static function read($source)
    {
        $manager = static::manager();

        return method_exists($manager, 'read')
            ? $manager->read($source)
            : $manager->decode($source);
    }

    public static function create($width, $height)
    {
        $manager = static::manager();

        return method_exists($manager, 'create')
            ? $manager->create($width, $height)
            : $manager->createImage($width, $height);
    }

    public static function fit($image, $width, $height = null, $position = 'center')
    {
        return is_null($height)
            ? $image->scale($width)
            : $image->cover($width, $height, $position);
    }

    public static function crop($image, $width, $height, $x = 0, $y = 0)
    {
        return $image->crop($width, $height, (int) $x, (int) $y);
    }

    public static function place($image, $watermark, $position = 'top-left', $x = 0, $y = 0)
    {
        if (method_exists($image, 'place')) {
            return $image->place($watermark, $position, (int) $x, (int) $y);
        }

        return $image->insert($watermark, (int) $x, (int) $y, $position);
    }

    public static function encode($image, $extension = null, $quality = null)
    {
        if (is_null($extension)) {
            return $image->encode();
        }

        $method = method_exists($image, 'encodeByExtension')
            ? 'encodeByExtension'
            : 'encodeUsingFileExtension';

        return is_null($quality)
            ? $image->{$method}($extension)
            : $image->{$method}($extension, $quality);
    }
}
