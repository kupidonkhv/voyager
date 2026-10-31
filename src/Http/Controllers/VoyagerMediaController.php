<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Support\Image as VoyagerImage;
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

        $folder = $request->folder;

        if ($folder == '/') {
            $folder = '';
        }

        $dir = $this->directory.$folder;

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

        $new_folder = $request->new_folder;
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

        $path = str_replace('//', '/', Str::finish($request->path, '/'));
        $success = true;
        $error = '';

        foreach ($request->get('files') as $file) {
            $file_path = $path.$file['name'];
            $isFolder = $file['type'] == 'folder';
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
        $path = str_replace('//', '/', Str::finish($request->path, '/'));
        $dest = str_replace('//', '/', Str::finish($request->destination, '/'));
        if (strpos($dest, '/../') !== false) {
            $dest = substr($path, 0, -1);
            $dest = substr($dest, 0, strripos($dest, '/') + 1);
        }
        $dest = str_replace('//', '/', Str::finish($dest, '/'));

        $success = true;
        $error = '';

        foreach ($request->get('files') as $file) {
            $old_path = $path.$file['name'];
            $new_path = $dest.$file['name'];
            $isFolder = $file['type'] == 'folder';

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
        $filename = $request->filename;
        $newFilename = $request->new_filename;
        $success = false;
        $error = false;

        if (pathinfo($filename)['extension'] !== pathinfo($newFilename)['extension']) {
            $error = __('voyager::media.error_renaming_ext');
        } else {
            if (is_array($folderLocation)) {
                $folderLocation = rtrim(implode('/', $folderLocation), '/');
            }

            $location = "{$this->directory}/{$folderLocation}";

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

        $extension = $request->file->getClientOriginalExtension();
        $name = Str::replaceLast('.'.$extension, '', $request->file->getClientOriginalName());
        $details = json_decode($request->get('details') ?? '{}');
        $absolute_path = Storage::disk($this->filesystem)->path($request->upload_path);

        try {
            $realPath = Storage::disk($this->filesystem)->path('/');

            $allowedMimeTypes = config('voyager.media.allowed_mimetypes', '*');
            if ($allowedMimeTypes != '*' && (is_array($allowedMimeTypes) && !in_array($request->file->getMimeType(), $allowedMimeTypes))) {
                throw new Exception(__('voyager::generic.mimetype_not_allowed'));
            }

            if (!$request->has('filename') || $request->get('filename') == 'null') {
                while (Storage::disk($this->filesystem)->exists(Str::finish($request->upload_path, '/').$name.'.'.$extension, $this->filesystem)) {
                    $name = get_file_name($name);
                }
            } else {
                $name = str_replace('{uid}', Auth::user()->getKey(), $request->get('filename'));
                if (Str::contains($name, '{date:')) {
                    $name = preg_replace_callback('/\{date:([^\/\}]*)\}/', function ($date) {
                        return \Carbon\Carbon::now()->format($date[1]);
                    }, $name);
                }
                if (Str::contains($name, '{random:')) {
                    $name = preg_replace_callback('/\{random:([0-9]+)\}/', function ($random) {
                        return Str::random($random[1]);
                    }, $name);
                }
            }

            $file = $request->file->storeAs($request->upload_path, $name.'.'.$extension, $this->filesystem);
            $file = preg_replace('#/+#', '/', $file);

            $imageMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/bmp',
                'image/svg+xml',
            ];
            if (in_array($request->file->getMimeType(), $imageMimeTypes)) {
                $content = Storage::disk($this->filesystem)->get($file);
                $image = VoyagerImage::read($content);

                if ($request->file->getClientOriginalExtension() == 'gif') {
                    copy($request->file->getRealPath(), $realPath.$file);
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
                            $thumbnail_file = $request->upload_path.$name.'-'.($thumbnail_data->name ?? 'thumbnail').'.'.$extension;
                            Storage::disk($this->filesystem)->put($thumbnail_file, VoyagerImage::encode($thumbnail, $extension, ($details->quality ?? 90))->toString());
                        }
                    }
                    // Add watermark to image
                    if (property_exists($details, 'watermark') && property_exists($details->watermark, 'source')) {
                        $image = $this->addWatermarkToImage($image, $details->watermark);
                    }
                    Storage::disk($this->filesystem)->put($file, VoyagerImage::encode($image, $extension, ($details->quality ?? 90))->toString());
                }
            }

            $success = true;
            $message = __('voyager::media.success_uploaded_file');
            $path = preg_replace('/^public\//', '', $file);

            event(new MediaFileAdded($path));

            $this->activityRecordMedia('media.uploaded', null, 'media', [
                'disk' => $this->filesystem,
                'path' => $path,
                'size' => $request->file->getSize(),
            ]);
        } catch (Exception $e) {
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
        $originImagePath = $request->upload_path.'/'.$request->originImageName;
        $originImagePath = preg_replace('#/+#', '/', $originImagePath);

        try {
            if ($createMode) {
                // create a new image with the cpopped data
                $fileNameParts = explode('.', $request->originImageName);
                array_splice($fileNameParts, count($fileNameParts) - 1, 0, 'cropped_'.time());
                $newImageName = implode('.', $fileNameParts);
                $destImagePath = preg_replace('#/+#', '/', $request->upload_path.'/'.$newImageName);
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
