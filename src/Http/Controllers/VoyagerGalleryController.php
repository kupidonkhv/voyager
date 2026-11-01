<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Models\Gallery;

class VoyagerGalleryController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->firstOrFail();
        $dataTypeContent = Gallery::with('media')->findOrFail($id);

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        $this->removeRelationshipField($dataType, 'edit');
        $this->authorize('edit', $dataTypeContent);

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        View::share('page_slug', $slug);

        return Voyager::view($this->formView($slug), compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function create(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->firstOrFail();

        $this->authorize('add', app($dataType->model_name));
        $dataTypeContent = new Gallery();

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        $this->removeRelationshipField($dataType, 'add');

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        View::share('page_slug', $slug);

        return Voyager::view($this->formView($slug), compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function store(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->firstOrFail();

        $this->authorize('add', app($dataType->model_name));
        $this->validateBread($request->all(), $dataType->addRows)->validate();
        $this->validateUploadedMedia($request);

        try {
            return DB::transaction(function () use ($request, $slug, $dataType) {
                /** @var Gallery $gallery */
                $gallery = $this->insertUpdateData($request, $slug, $dataType->addRows, new Gallery());
                $this->storeUploadedMedia($request, $gallery);

                $this->activityRecordCreated($dataType, $gallery);
                event(new BreadDataAdded($dataType, $gallery));

                if (!$request->has('_tagging')) {
                    $redirect = auth()->user()->can('browse', $gallery)
                        ? redirect()->route("voyager.{$dataType->slug}.index")
                        : redirect()->back();

                    return $redirect->with([
                        'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                        'alert-type' => 'success',
                    ]);
                }

                return response()->json(['success' => true, 'data' => $gallery]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with([
                'message'    => __('voyager::activity.save_failed'),
                'alert-type' => 'error',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->firstOrFail();

        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        /** @var Gallery $gallery */
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        $this->validateUploadedMedia($request);

        $before = clone $gallery;
        if (method_exists($before, 'translations')) {
            $before->loadMissing('translations');
        }

        try {
            return DB::transaction(function () use ($request, $slug, $dataType, $gallery, $before) {
                $this->insertUpdateData($request, $slug, $dataType->editRows, $gallery);
                $this->storeUploadedMedia($request, $gallery);

                $this->activityRecordUpdated($dataType, $before, $gallery);
                event(new BreadDataUpdated($dataType, $gallery));

                $redirect = auth()->user()->can('browse', app($dataType->model_name))
                    ? redirect()->route("voyager.{$dataType->slug}.index")
                    : redirect()->back();

                return $redirect->with([
                    'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                    'alert-type' => 'success',
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with([
                'message'    => __('voyager::activity.save_failed'),
                'alert-type' => 'error',
            ]);
        }
    }

    public function uploadMedia(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $mimeTypes = implode(',', config('voyager.gallery.accepted_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/bmp',
        ]));

        $extensions = implode(',', config('voyager.gallery.accepted_extensions', [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'bmp',
        ]));

        $request->validate([
            'file' => ['required', 'image', 'mimetypes:'.$mimeTypes, 'mimes:'.$extensions, 'max:'.(int) config('voyager.gallery.max_upload_size', 10240)],
        ]);

        $media = $gallery->addMedia($request->file('file'))
            ->withCustomProperties([
                'description' => (string) $request->input('description', ''),
            ])
            ->toMediaCollection('images');

        $media->order_column = ((int) $gallery->images()->max('order_column')) + 1;
        $media->save();

        $this->activityRecordMedia('media.uploaded', (string) $media->getKey(), $this->getSlug($request), [
            'disk' => $media->disk,
            'path' => $media->getPathRelativeToRoot(),
            'size' => $media->size,
        ]);

        return response()->json([
            'success' => true,
            'media'   => $this->mediaPayload($media),
        ]);
    }

    public function deleteMedia(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $request->validate(['media_id' => 'required|integer']);

        $media = $gallery->images()->whereKey($request->integer('media_id'))->first();
        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $metadata = [
            'disk' => $media->disk,
            'path' => $media->getPathRelativeToRoot(),
            'size' => $media->size,
        ];
        $mediaId = (string) $media->getKey();

        $media->delete();

        $this->activityRecordMedia('media.deleted', $mediaId, $this->getSlug($request), $metadata);

        return response()->json(['success' => true]);
    }

    public function regenerateMedia(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $request->validate(['media_id' => 'required|integer']);

        $media = $gallery->images()->whereKey($request->integer('media_id'))->first();
        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        app(\Spatie\MediaLibrary\Conversions\FileManipulator::class)->createDerivedFiles($media);

        return response()->json([
            'success'   => true,
            'thumb_url' => $media->fresh()->getUrl('thumb').'?v='.time(),
        ]);
    }

    public function updateMediaDescription(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $request->validate([
            'media_id'    => 'required|integer',
            'description' => 'nullable|string|max:500',
        ]);

        $media = $gallery->images()->whereKey($request->integer('media_id'))->first();
        if (!$media) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $media->setCustomProperty('description', (string) $request->input('description', ''));
        $media->save();

        return response()->json(['success' => true]);
    }

    public function sortMedia(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        $this->authorize('edit', $gallery);

        $request->validate([
            'media_ids'   => 'required|array',
            'media_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $gallery) {
            $mediaIds = $request->input('media_ids', []);

            foreach ($mediaIds as $order => $mediaId) {
                DB::table('media')
                    ->where('id', $mediaId)
                    ->where('model_id', $gallery->getKey())
                    ->where('model_type', $gallery->getMorphClass())
                    ->update(['order_column' => $order + 1]);
            }

            $this->activity()->recordEventSafe(
                'gallery.reordered',
                'success',
                $gallery,
                $this->getSlug($request),
                [],
                ['count' => count($mediaIds)],
                'gallery',
                (string) $gallery->getKey()
            );
        });

        return response()->json(['success' => true]);
    }

    protected function validateUploadedMedia(Request $request): void
    {
        if (!$request->hasFile('media')) {
            return;
        }

        $mimeTypes = implode(',', config('voyager.gallery.accepted_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/bmp',
        ]));

        $extensions = implode(',', config('voyager.gallery.accepted_extensions', [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'bmp',
        ]));

        $request->validate([
            'media'   => 'array',
            'media.*' => ['image', 'mimetypes:'.$mimeTypes, 'mimes:'.$extensions, 'max:'.(int) config('voyager.gallery.max_upload_size', 10240)],
        ]);
    }

    protected function storeUploadedMedia(Request $request, Gallery $gallery): void
    {
        if (!$request->hasFile('media')) {
            return;
        }

        foreach ($request->file('media') as $file) {
            $media = $gallery->addMedia($file)->toMediaCollection('images');
            $media->order_column = ((int) $gallery->images()->max('order_column')) + 1;
            $media->save();

            $this->activityRecordMedia('media.uploaded', (string) $media->getKey(), 'galleries', [
                'disk' => $media->disk,
                'path' => $media->getPathRelativeToRoot(),
                'size' => $media->size,
            ]);
        }
    }

    protected function mediaPayload($media): array
    {
        return [
            'id'          => $media->id,
            'url'         => $media->getUrl(),
            'thumb'       => $media->getUrl('thumb'),
            'name'        => $media->file_name,
            'size'        => $media->size,
            'order'       => $media->order_column,
            'description' => $media->getCustomProperty('description', ''),
        ];
    }

    protected function formView(string $slug): string
    {
        return view()->exists("voyager::$slug.edit-add")
            ? "voyager::$slug.edit-add"
            : 'voyager::bread.edit-add';
    }
}
