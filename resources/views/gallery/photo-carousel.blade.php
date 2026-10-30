<style>
    .voyager-gallery-carousel { display:flex; gap:12px; overflow-x:auto; padding-bottom:8px; scroll-snap-type:x mandatory; }
    .voyager-gallery-carousel > a { flex:0 0 auto; scroll-snap-align:start; }
    .voyager-gallery-carousel img { border-radius:4px; display:block; height:220px; object-fit:cover; width:auto; }
</style>

<div class="voyager-gallery voyager-gallery-carousel" data-gallery-id="{{ $gallery->id }}">
    @foreach ($gallery->images as $media)
        <a href="{{ $media->getUrl() }}" rel="gallery" data-fancybox="gallery-{{ $gallery->id }}" class="frame-image image-popup fancybox">
            <img src="{{ $media->getUrl('thumb') }}"
                 loading="lazy"
                 alt="{{ $media->getCustomProperty('description', $gallery->title) }}">
        </a>
    @endforeach
</div>
