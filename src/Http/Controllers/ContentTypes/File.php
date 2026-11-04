<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Support\SafeUpload;

class File extends BaseType
{
    /**
     * @return string
     */
    public function handle()
    {
        if (!$this->request->hasFile($this->row->field)) {
            return;
        }

        $files = Arr::wrap($this->request->file($this->row->field));

        $filesPath = [];
        $path = $this->generatePath();

        foreach ($files as $file) {
            // [fix-v2] в массиве file[] может прийти null — пропускаем, не роняя всю загрузку
            if (!$file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }
            $filename = $this->generateFileName($file, $path);
            // [fix] расширение из имени клиента проверяется тем же allowlist, что и в медиа-менеджере
            $extension = SafeUpload::normalizeExtension($file->getClientOriginalExtension());
            // [fix-v2] пустое расширение не ошибка (сохраняем без расширения), опасные — по-прежнему запрещены
            // [fix-v3] плюс барьеры по содержимому: MIME активного контента (text/html, image/svg+xml, …)
            // и разметка в файле с нейтральным/неизвестным расширением (H1/H2 — остаток VULN-03/VULN-01)
            SafeUpload::assertSafeUploadedFile($file, $extension);
            $storedName = SafeUpload::sanitizeBaseName($filename, $extension);
            $file->storeAs(
                $path,
                $storedName,
                config('voyager.storage.disk', 'public')
            );

            array_push($filesPath, [
                'download_link' => $path.$storedName,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return json_encode($filesPath);
    }

    /**
     * @return string
     */
    protected function generatePath()
    {
        return $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function generateFileName($file, $path)
    {
        if (isset($this->options->preserveFileUploadName) && $this->options->preserveFileUploadName) {
            $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension());
            $filename_counter = 1;

            // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension()).(string) ($filename_counter++);
            }
        } else {
            $filename = Str::random(20);

            // Make sure the filename does not exist, if it does, just regenerate
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = Str::random(20);
            }
        }

        return $filename;
    }
}
