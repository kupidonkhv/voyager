<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Gallery extends Model implements HasMedia
{
    use InteractsWithMedia;

    public bool $registerMediaConversionsUsingModelInstance = true;

    protected $table = 'galleries';

    protected $fillable = [
        'title',
        'description',
        'dimensions',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(config('voyager.gallery.accepted_mime_types', [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        [$width, $height] = $this->thumbnailDimensions();

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, $width, $height)
            ->nonQueued();
    }

    public function images()
    {
        return $this->media()
            ->where('collection_name', 'images')
            ->orderBy('order_column');
    }

    public function thumbnailDimensions(): array
    {
        $default = config('voyager.gallery.thumb_dimensions', '400x300');

        if (!preg_match('/^(\d{1,4})x(\d{1,4})$/i', (string) ($this->dimensions ?: $default), $matches)) {
            [$width, $height] = explode('x', $default);

            return [(int) $width, (int) $height];
        }

        return [max(1, (int) $matches[1]), max(1, (int) $matches[2])];
    }
}
