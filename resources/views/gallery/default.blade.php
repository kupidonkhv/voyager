<div class="row voyager-gallery" data-gallery-id="{{ $gallery->id }}">
    @foreach ($gallery->images as $media)
        <div class="col-6 col-xs-6 col-md-4 col-lg-3 pb30 pb-3">
            <div class="text-center text-lg-left">
                <a href="{{ $media->getUrl() }}" rel="gallery" data-fancybox="gallery-{{ $gallery->id }}" class="frame-image image-popup fancybox">
                    <img src="{{ $media->getUrl('thumb') }}"
                         loading="lazy"
                         alt="{{ $media->getCustomProperty('description', $gallery->title) }}"
                         class="img-fluid w100 img-thumbnail img-thumbnail-no-borders rounded-0 image-popup img-square">
                </a>
                @if ($description = $media->getCustomProperty('description'))
                    <div class="text-center mt-2">
                        <small class="text-muted">{{ $description }}</small>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
