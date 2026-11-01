<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Support\Image as VoyagerImage;
use TCG\Voyager\Support\SafeUpload;
use TCG\Voyager\Events\MediaFileAdded;
use TCG\Voyager\Facades\Voyager;

class VoyagerMediaController extends Controller
{
    /** @var string */
    private $filesystem;

    /** @var string */
    private $directory = '';

    public function __construct()
    {
        $this->filesystem = config('voyager.storage.disk');
    }

    public function index()
    {
        // Check permission
        $this->authorize('browse_media');

        return Voyager::view('voyager::media.index');
    }

    public function files(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        $options = $request->details ?? [];
        $thumbnail_names = [];
        $thumbnails = [];
        if (!($options->hide_thumbnails ?? false)) {
            $thumbnail_names = array_column(($options['thumbnails'] ?? []), 'name');
        }

        try {
            $folder = SafeUpload::safeRelativePath((string) $request->folder);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $dir = ltrim($this->directory.'/'.$folder, '/');

        $files = [];
        if (class_exists(\League\Flysystem\Plugin\ListWith::class)) {
            $storage = Storage::disk($this->filesystem)->addPlugin(new \League\Flysystem\Plugin\ListWith());
            $storageItems = $storage->listWith(['mimetype'], $dir);
        } else {
            $storage = Storage::disk($this->filesystem);
            $storageItems = $storage->listContents($dir)->sortByPath()->toArray();
        }

        foreach ($storageItems as $item) {
            if ($item['type'] == 'dir') {
                $files[] = [
                    'name'          => $item['basename'] ?? basename($item['path']),
                    'type'          => 'folder',
                    'path'          => Storage::disk($this->filesystem)->url($item['path']),
                    'relative_path' => $item['path'],
                    'items'         => '',
                    'last_modified' => '',
                ];
            } else {
                if (empty(pathinfo($item['path'], PATHINFO_FILENAME)) && !config('voyager.hidden_files')) {
                    continue;
                }
                // Its a thumbnail and thumbnails should be hidden
                if (Str::endsWith($item['path'], $thumbnail_names)) {
                    $thumbnails[] = $item;
                    continue;
                }
                $mime = 'file';
                if (class_exists(\League\MimeTypeDetection\ExtensionMimeTypeDetector::class)) {
                    $mime = (new \League\MimeTypeDetection\ExtensionMimeTypeDetector())->detectMimeTypeFromFile($item['path']);
                }
                $files[] = [
                    'name'          => $item['basename'] ?? basename($item['path']),
                    'filename'      => $item['filename'] ?? basename($item['path'], '.'.pathinfo($item['path'])['extension']),
                    'type'          => $item['mimetype'] ?? $mime,
                    'path'          => Storage::disk($this->filesystem)->url($item['path']),
                    'relative_path' => $item['path'],
                    'size'          => $item['size'] ?? $item->fileSize(),
                    'last_modified' => $item['timestamp'] ?? $item->lastModified(),
                    'thumbnails'    => [],
                ];
            }
        }

        foreach ($files as $key => $file) {
            foreach ($thumbnails as $thumbnail) {
                if ($file['type'] != 'folder' && Str::startsWith($thumbnail['filename'], $file['filename'])) {
                    $thumbnail['thumb_name'] = str_replace($file['filename'].'-', '', $thumbnail['filename']);
                    $thumbnail['path'] = Storage::disk($this->filesystem)->url($thumbnail['path']);
                    $files[$key]['thumbnails'][] = $thumbnail;
                }
            }
        }

        return response()->json($files);
    }

    public function new_folder(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        try {
            $new_folder = SafeUpload::safeRelativePath((string) $request->new_folder, false);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $success = false;
        $error = '';

        if (Storage::disk($this->filesystem)->exists($new_folder)) {
            $error = __('voyager::media.folder_exists_already');
        } elseif (Storage::disk($this->filesystem)->makeDirectory($new_folder)) {
            $success = true;
        } else {
            $error = __('voyager::media.error_creating_dir');
        }

        $this->activityRecordMedia('media.directory_created', null, 'media', [
            'disk' => $this->filesystem,
            'path' => $new_folder,
        ], $success ? 'success' : 'failure');

        return compact('success', 'error');
    }

    public function delete(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        try {
            $path = SafeUpload::safeRelativePath((string) $request->path);
            $path = $path === '' ? '' : $path.'/';
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $success = true;
        $error = '';
        $files = $request->get('files');

        if (!is_array($files)) {
            return ['success' => false, 'error' => 'Invalid file list.'];
        }

        foreach ($files as $file) {
            try {
                $name = SafeUpload::safeRelativePath((string) ($file['name'] ?? ''), false);
            } catch (\InvalidArgumentException $e) {
                $success = false;
                $error = $e->getMessage();
                continue;
            }

            $file_path = $path.$name;
            $isFolder = ($file['type'] ?? '') == 'folder';
            $deleted = $isFolder
                ? Storage::disk($this->filesystem)->deleteDirectory($file_path)
                : Storage::disk($this->filesystem)->delete($file_path);

            if (!$deleted) {
                $error = $isFolder
                    ? __('voyager::media.error_deleting_folder')
                    : __('voyager::media.error_deleting_file');
                $success = false;
            }

            $this->activityRecordMedia(
                $isFolder ? 'media.directory_deleted' : 'media.deleted',
                null,
                'media',
                ['disk' => $this->filesystem, 'path' => $file_path],
                $deleted ? 'success' : 'failure'
            );
        }

        return compact('success', 'error');
    }

    public function move(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');
        try {
            $path = SafeUpload::safeRelativePath((string) $request->path);
            $dest = SafeUpload::safeRelativePath((string) $request->destination);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $path = $path === '' ? '' : $path.'/';
        $dest = $dest === '' ? '' : $dest.'/';

        $success = true;
        $error = '';
        $files = $request->get('files');

        if (!is_array($files)) {
            return ['success' => false, 'error' => 'Invalid file list.'];
        }

        foreach ($files as $file) {
            try {
                $name = SafeUpload::safeRelativePath((string) ($file['name'] ?? ''), false);
            } catch (\InvalidArgumentException $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }

            $old_path = $path.$name;
            $new_path = $dest.$name;
            $isFolder = ($file['type'] ?? '') == 'folder';

            try {
                Storage::disk($this->filesystem)->move($old_path, $new_path);

                $this->activityRecordMedia(
                    $isFolder ? 'media.directory_moved' : 'media.moved',
                    null,
                    'media',
                    ['disk' => $this->filesystem, 'path' => $new_path]
                );
            } catch (\Exception $ex) {
                $success = false;
                $error = $ex->getMessage();

                $this->activityRecordMedia(
                    $isFolder ? 'media.directory_moved' : 'media.moved',
                    null,
                    'media',
                    ['disk' => $this->filesystem, 'path' => $old_path],
                    'failure'
                );

                return compact('success', 'error');
            }
        }

        return compact('success', 'error');
    }

    public function rename(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        $folderLocation = $request->folder_location;
        $filename = basename(str_replace('\\', '/', (string) $request->filename));
        $newFilename = basename(str_replace('\\', '/', (string) $request->new_filename));
        $success = false;
        $error = false;

        try {
            $folderLocation = is_array($folderLocation) ? implode('/', $folderLocation) : (string) $folderLocation;
            $folderLocation = SafeUpload::safeRelativePath($folderLocation);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        if ($filename === '' || $newFilename === '' || str_contains($filename, '..') || str_contains($newFilename, '..')) {
            return ['success' => false, 'error' => 'Invalid filename.'];
        }

        if (pathinfo($filename)['extension'] !== pathinfo($newFilename)['extension']) {
            $error = __('voyager::media.error_renaming_ext');
        } else {
            $location = $this->directory.($folderLocation === '' ? '' : '/'.$folderLocation);

            if (!Storage::disk($this->filesystem)->exists("{$location}/{$newFilename}")) {
                if (Storage::disk($this->filesystem)->move("{$location}/{$filename}", "{$location}/{$newFilename}")) {
                    $success = true;

                    $this->activityRecordMedia('media.renamed', null, 'media', [
                        'disk' => $this->filesystem,
                        'path' => "{$location}/{$newFilename}",
                    ]);
                } else {
                    $error = __('voyager::media.error_moving');
                }
            } else {
                $error = __('voyager::media.error_may_exist');
            }
        }

        return compact('success', 'error');
    }

    public function upload(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        $file = $request->file('file');

        if (!$file instanceof UploadedFile && $request->input('file') instanceof UploadedFile) {
            $file = $request->input('file');
        }

        if (!$file instanceof UploadedFile) {
            return response()->json([
                'success' => false,
                'message' => __('voyager::media.error_uploading'),
                'path'    => '',
            ]);
        }

        $extension = SafeUpload::normalizeExtension($file->getClientOriginalExtension());
        $details = json_decode($request->get('details') ?? '{}');

        try {
            $uploadPath = SafeUpload::safeRelativePath((string) $request->get('upload_path', ''));
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'path'    => '',
            ]);
        }

        $storedPath = null;
        $createdFiles = [];

        try {
            SafeUpload::assertUploadedFile($file);
            SafeUpload::assertAllowedExtension($extension);

            $realPath = Storage::disk($this->filesystem)->path('/');

            $allowedMimeTypes = config('voyager.media.allowed_mimetypes', '*');
            if ($allowedMimeTypes != '*' && (!is_array($allowedMimeTypes) || !in_array($file->getMimeType(), $allowedMimeTypes))) {
                throw new Exception(__('voyager::generic.mimetype_not_allowed'));
            }

            $name = Str::replaceLast('.'.$extension, '', $file->getClientOriginalName());

            if (!$request->has('filename') || $request->get('filename') == 'null') {
                while (Storage::disk($this->filesystem)->exists(
                    Str::finish($uploadPath, '/').SafeUpload::sanitizeBaseName($name, $extension)
                )) {
                    $name = get_file_name($name);
                }
            } else {
                $name = str_replace('{uid}', Auth::user()->getKey(), (string) $request->get('filename'));
                if (Str::contains($name, '{date:')) {
                    $name = preg_replace_callback('/\{date:([^\/\}]*)\}/', function ($date) {
                        return \Carbon\Carbon::now()->format($date[1]);
                    }, $name);
                }
                if (Str::contains($name, '{random:')) {
                    $name = preg_replace_callback('/\{random:([0-9]+)\}/', function ($random) {
                        return Str::random((int) $random[1]);
                    }, $name);
                }
            }

            $storedName = SafeUpload::sanitizeBaseName($name, $extension);
            $storedPath = $file->storeAs($uploadPath, $storedName, $this->filesystem);
            $storedPath = preg_replace('#/+#', '/', $storedPath);
            $createdFiles[] = $storedPath;

            $imageMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/svg+xml',
            ];
            if (in_array($file->getMimeType(), $imageMimeTypes)) {
                $content = Storage::disk($this->filesystem)->get($storedPath);
                $image = VoyagerImage::read($content);

                if ($extension == 'gif') {
                    copy($file->getRealPath(), $realPath.$storedPath);
                } else {
                    $image = $image->orient();
                    // Generate thumbnails
                    if (property_exists($details, 'thumbnails') && is_array($details->thumbnails)) {
                        foreach ($details->thumbnails as $thumbnail_data) {
                            $type = $thumbnail_data->type ?? 'fit';
                            $thumbnail = clone $image;
                            if ($type == 'fit') {
                                $thumbnail = VoyagerImage::fit(
                                    $thumbnail,
                                    $thumbnail_data->width,
                                    ($thumbnail_data->height ?? null),
                                    ($thumbnail_data->position ?? 'center')
                                );
                            } elseif ($type == 'crop') {
                                $thumbnail = VoyagerImage::crop(
                                    $thumbnail,
                                    $thumbnail_data->width,
                                    $thumbnail_data->height,
                                    ($thumbnail_data->x ?? null),
                                    ($thumbnail_data->y ?? null)
                                );
                            } elseif ($type == 'resize') {
                                $thumbnail = ($thumbnail_data->upsize ?? true)
                                    ? $thumbnail->scale($thumbnail_data->width, ($thumbnail_data->height ?? null))
                                    : $thumbnail->scaleDown($thumbnail_data->width, ($thumbnail_data->height ?? null));
                            }
                            if (
                                property_exists($details, 'watermark') &&
                                property_exists($details->watermark, 'source') &&
                                property_exists($thumbnail_data, 'watermark') &&
                                $thumbnail_data->watermark
                            ) {
                                $thumbnail = $this->addWatermarkToImage($thumbnail, $details->watermark);
                            }
                            $thumbnailName = dirname($storedPath).'/'.pathinfo($storedPath, PATHINFO_FILENAME)
                                .'-'.($thumbnail_data->name ?? 'thumbnail').'.'.$extension;
                            Storage::disk($this->filesystem)->put(
                                $thumbnailName,
                                VoyagerImage::encode($thumbnail, $extension, ($details->quality ?? 90))->toString()
                            );
                            $createdFiles[] = $thumbnailName;
                        }
                    }
                    // Add watermark to image
                    if (property_exists($details, 'watermark') && property_exists($details->watermark, 'source')) {
                        $image = $this->addWatermarkToImage($image, $details->watermark);
                    }
                    Storage::disk($this->filesystem)->put($storedPath, VoyagerImage::encode($image, $extension, ($details->quality ?? 90))->toString());
                }
            }

            $success = true;
            $message = __('voyager::media.success_uploaded_file');
            $path = preg_replace('/^public\//', '', $storedPath);

            event(new MediaFileAdded($path));

            $this->activityRecordMedia('media.uploaded', null, 'media', [
                'disk' => $this->filesystem,
                'path' => $path,
                'size' => $file->getSize(),
            ]);
        } catch (Exception $e) {
            $disk = Storage::disk($this->filesystem);
            foreach (array_unique($createdFiles) as $createdFile) {
                try {
                    $disk->delete($createdFile);
                } catch (\Throwable $ignored) {
                    // Best-effort cleanup must not hide the original upload error.
                }
            }

            $success = false;
            $message = $e->getMessage();
            $path = '';
        }

        return response()->json(compact('success', 'message', 'path'));
    }

    public function crop(Request $request)
    {
        // Check permission
        $this->authorize('browse_media');

        $createMode = $request->get('createMode') === 'true';
        $x = $request->get('x');
        $y = $request->get('y');
        $height = $request->get('height');
        $width = $request->get('width');

        $realPath = Storage::disk($this->filesystem)->path('/');
        $originImageName = basename(str_replace('\\', '/', (string) $request->originImageName));

        try {
            $uploadPath = SafeUpload::safeRelativePath((string) $request->upload_path);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        $originImagePath = ltrim($uploadPath.'/'.$originImageName, '/');

        try {
            if ($createMode) {
                // create a new image with the cpopped data
                $fileNameParts = explode('.', $originImageName);
                array_splice($fileNameParts, count($fileNameParts) - 1, 0, 'cropped_'.time());
                $newImageName = implode('.', $fileNameParts);
                $destImagePath = ltrim($uploadPath.'/'.$newImageName, '/');
            } else {
                // override the original image
                $destImagePath = $originImagePath;
            }

            $content = Storage::disk($this->filesystem)->get($originImagePath);
            $image = VoyagerImage::crop(VoyagerImage::read($content), $width, $height, $x, $y);
            Storage::disk($this->filesystem)->put($destImagePath, VoyagerImage::encode($image, pathinfo($originImagePath, PATHINFO_EXTENSION))->toString());

            $success = true;
            $message = __('voyager::media.success_crop_image');

            $this->activityRecordMedia('media.replaced', null, 'media', [
                'disk' => $this->filesystem,
                'path' => $destImagePath,
            ]);
        } catch (Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json(compact('success', 'message'));
    }

    private function addWatermarkToImage($image, $options)
    {
        $watermark = VoyagerImage::read(Storage::disk($this->filesystem)->path($options->source));
        // Resize watermark
        $width = $image->width() * (($options->size ?? 15) / 100);
        $watermark->scale($width);

        return VoyagerImage::place(
            $image,
            $watermark,
            ($options->position ?? 'top-left'),
            ($options->x ?? 0),
            ($options->y ?? 0)
        );
    }
}
