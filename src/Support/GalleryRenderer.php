<?php

namespace TCG\Voyager\Support;

use Throwable;
use TCG\Voyager\Facades\Voyager;

class GalleryRenderer
{
    public function render(string $body): string
    {
        return preg_replace_callback(
            '/%gallery="([^"]+)"(?:\s+form="([^"]+)")?%/u',
            fn (array $matches) => $this->renderGallery($matches[1], $matches[2] ?? 'default'),
            $body
        ) ?? $body;
    }

    public function renderGallery(string $title, string $form = 'default'): string
    {
        $form = preg_replace('/[^a-z0-9_-]/i', '', $form) ?: 'default';

        try {
            $gallery = Voyager::model('Gallery')->where('title', $title)->first();
        } catch (Throwable $e) {
            $gallery = null;
        }

        if (!$gallery || $gallery->images->isEmpty()) {
            return '<!-- Gallery "'.e($title).'" not found or empty -->';
        }

        $views = array_unique([
            "gallery.{$form}",
            "voyager::gallery.{$form}",
            'gallery.default',
            'voyager::gallery.default',
        ]);

        foreach ($views as $view) {
            if (!view()->exists($view)) {
                continue;
            }

            try {
                return view($view, ['gallery' => $gallery])->render();
            } catch (Throwable $e) {
                continue;
            }
        }

        return '<!-- Gallery "'.e($title).'" view not found -->';
    }
}
