<?php

namespace TCG\Voyager\Support;

use Illuminate\Http\UploadedFile;

class SafeUpload
{
    public const DEFAULT_BLOCKED_EXTENSIONS = [
        'php',
        'php3',
        'php4',
        'php5',
        'php7',
        'php8',
        'phtml',
        'pht',
        'phps',
        'phar',
        'cgi',
        'pl',
        'py',
        'sh',
        'bash',
        'exe',
        'bat',
        'cmd',
        'com',
        'htaccess',
        'htpasswd',
        'env',
    ];

    public const DEFAULT_IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'bmp',
    ];

    public static function normalizeExtension(?string $extension): string
    {
        return strtolower(trim((string) $extension, " \t\n\r\0\x0B."));
    }

    public static function blockedExtensions(): array
    {
        $blocked = config('voyager.media.blocked_extensions', self::DEFAULT_BLOCKED_EXTENSIONS);

        return array_values(array_unique(array_map(
            static fn ($extension) => self::normalizeExtension($extension),
            is_array($blocked) ? $blocked : self::DEFAULT_BLOCKED_EXTENSIONS
        )));
    }

    public static function assertAllowedExtension(string $extension): void
    {
        $extension = self::normalizeExtension($extension);

        if ($extension === '' || in_array($extension, self::blockedExtensions(), true)) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }

        if (!preg_match('/^[a-z0-9]{1,16}$/', $extension)) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }
    }

    public static function assertImageExtension(string $extension): void
    {
        self::assertAllowedExtension($extension);

        $allowed = config('voyager.gallery.accepted_extensions', self::DEFAULT_IMAGE_EXTENSIONS);
        $allowed = array_map(
            static fn ($item) => self::normalizeExtension($item),
            is_array($allowed) ? $allowed : self::DEFAULT_IMAGE_EXTENSIONS
        );

        if (!in_array(self::normalizeExtension($extension), $allowed, true)) {
            throw new \InvalidArgumentException(__('voyager::media.uploading_wrong_type'));
        }
    }

    public static function safeRelativePath(string $path, bool $allowEmpty = true): string
    {
        if (str_contains($path, "\0")) {
            throw new \InvalidArgumentException('Invalid path.');
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = trim((string) $path, '/');

        if ($path === '') {
            if ($allowEmpty) {
                return '';
            }

            throw new \InvalidArgumentException('Invalid path.');
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Invalid path.');
            }
        }

        return $path;
    }

    public static function sanitizeBaseName(string $name, string $extension): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/\.(?:'.implode('|', array_map('preg_quote', self::blockedExtensions())).')$/i', '', $name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $name);
        $name = trim((string) $name, '.-_');

        if ($name === '') {
            $name = 'file';
        }

        return $name.'.'.self::normalizeExtension($extension);
    }

    public static function assertUploadedFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }
    }
}
