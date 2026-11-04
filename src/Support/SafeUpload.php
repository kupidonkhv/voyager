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
        // [fix-v3] активный контент: такие файлы браузер исполняет в контексте админки
        // (H1/H2 — остаток VULN-03/VULN-01: .html отдавался как text/html, .svg — как image/svg+xml)
        'html',
        'htm',
        'xhtml',
        'shtml',
        'shtm',
        'svg',
        'svgz',
        'xml',
        'xsl',
        'xslt',
        'mhtml',
        'mht',
        'hta',
    ];

    /**
     * [fix-v3] MIME-типы активного контента: даже если расширение нейтральное, такой файл нельзя
     * сохранять на публичный диск (сверка по finfo, а не по имени от клиента).
     */
    public const DEFAULT_BLOCKED_MIMETYPES = [
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'application/ecmascript',
        'text/ecmascript',
        'application/x-httpd-php',
        'application/x-php',
        'text/x-php',
        'application/x-shockwave-flash',
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

    /**
     * [fix-v2] Проверка расширения для штатной загрузки файла (BREAD type=file).
     * В отличие от assertAllowedExtension(), пустое расширение допустимо: файл без расширения
     * веб-сервером не исполняется, а прежний запрет ломал штатную загрузку (тест
     * tests/FormfieldsTest.php::testFormfieldFile).
     */
    public static function assertSafeExtension(string $extension): void
    {
        $extension = self::normalizeExtension($extension);

        if ($extension === '') {
            return;
        }

        if (in_array($extension, self::blockedExtensions(), true)) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }

        if (!preg_match('/^[a-z0-9]{1,16}$/', $extension)) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }
    }

    public static function blockedMimetypes(): array
    {
        $blocked = config('voyager.media.blocked_mimetypes', self::DEFAULT_BLOCKED_MIMETYPES);

        return array_values(array_unique(array_filter(array_map(
            static fn ($mime) => strtolower(trim((string) $mime)),
            is_array($blocked) ? $blocked : self::DEFAULT_BLOCKED_MIMETYPES
        ))));
    }

    /**
     * [fix-v3] Фактический MIME файла по содержимому (finfo), а не по имени от клиента.
     */
    public static function detectMimeType(UploadedFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (!is_string($path) || $path === '' || !is_readable($path)) {
            return '';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? strtolower(trim($mime)) : '';
    }

    /**
     * [fix-v3] Шаблоны, по которым браузер выставляет text/html, когда в ответе нет Content-Type
     * (MIME Sniffing Standard, «rules for identifying an unknown MIME type»). Проверка ровно по этому
     * списку повторяет решение браузера и не зависит от эвристик libmagic: finfo на `<div>hi</div>`
     * даёт text/plain, а на `<div>hi</div><script>…` — text/html, поэтому MIME-барьер тут ненадёжен.
     */
    public const HTML_SNIFFING_TEMPLATES = [
        '<!doctype html',
        '<html',
        '<head',
        '<script',
        '<iframe',
        '<h1',
        '<div',
        '<font',
        '<table',
        '<body',
        '<br',
        '<a',
        '<style',
        '<title',
        '<b',
        '<p',
        '<!--',
    ];

    public static function htmlSniffingTemplates(): array
    {
        return self::HTML_SNIFFING_TEMPLATES;
    }

    /**
     * [fix-v3] Начинается ли тело файла (после BOM и ведущих пробелов) с шаблона, по которому
     * браузер определит text/html при отдаче без Content-Type? Именно так файл с нейтральным или
     * пустым расширением исполняется в контексте админки (H1/H2, остаток VULN-03/VULN-01).
     */
    public static function startsWithHtmlSniffingTemplate(UploadedFile $file): bool
    {
        $path = $file->getRealPath() ?: $file->getPathname();

        if (!is_string($path) || $path === '' || !is_readable($path)) {
            return false;
        }

        $head = @file_get_contents($path, false, null, 0, 1024);

        if (!is_string($head) || $head === '') {
            return false;
        }

        $head = strtolower(ltrim($head, "\xEF\xBB\xBF \t\n\r\x0C\x0B"));

        foreach (self::HTML_SNIFFING_TEMPLATES as $template) {
            if (str_starts_with($head, $template)) {
                return true;
            }
        }

        return false;
    }

    /**
     * [fix-v3] Проверка загружаемого файла для штатных полей type=file (BREAD и настройки):
     * расширение (нормализация + denylist) И фактический MIME по finfo (запрет активного контента),
     * И префикс по списку сниффинга WHATWG. Нужна потому, что denylist расширений не мешает
     * сохранить HTML-содержимое под нейтральным именем (x.html.png, полиглот, файл без расширения),
     * а браузер исполняет такой файл в контексте админки (H1/H2, VULN-03).
     */
    public static function assertSafeUploadedFile(UploadedFile $file, string $extension): void
    {
        self::assertUploadedFile($file);
        self::assertSafeExtension($extension);

        // барьер 1: фактический MIME по содержимому (активный контент под нейтральным именем)
        $mime = self::detectMimeType($file);

        if ($mime !== '' && in_array($mime, self::blockedMimetypes(), true)) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }

        // барьер 2: тело начинается с шаблона, по которому браузер сниффингом выставит text/html.
        // Проверяется всегда, независимо от расширения и от того, что сказал finfo.
        if (self::startsWithHtmlSniffingTemplate($file)) {
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

        $extension = self::normalizeExtension($extension);

        // [fix-v2] без расширения сохраняем имя как есть (иначе получилось бы «name.»)
        return $extension === '' ? $name : $name.'.'.$extension;
    }

    public static function assertUploadedFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException(__('voyager::media.error_uploading'));
        }
    }
}
