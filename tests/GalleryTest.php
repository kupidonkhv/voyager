<?php

namespace TCG\Voyager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Gallery;
use TCG\Voyager\Models\Menu;

class GalleryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::loginUsingId(1);
        Storage::fake(config('voyager.storage.disk', 'public'));
    }

    public function testGalleryBreadAndMenuAreInstalled()
    {
        $dataType = DataType::where('slug', 'galleries')->firstOrFail();

        $this->assertEquals(Gallery::class, $dataType->model_name);
        $this->seeInDatabase('menu_items', [
            'menu_id' => Menu::where('name', 'admin')->value('id'),
            'route'   => 'voyager.galleries.index',
            'title'   => __('voyager::seeders.menu_items.galleries'),
        ]);
        $this->seeInDatabase('data_rows', [
            'data_type_id' => $dataType->id,
            'field'        => 'title',
        ]);
        $this->seeInDatabase('permissions', [
            'key'        => 'browse_galleries',
            'table_name' => 'galleries',
        ]);
    }

    public function testGalleryCanBeCreatedAndEdited()
    {
        $response = $this->post(route('voyager.galleries.store'), [
            'title'       => 'Test gallery',
            'description' => 'Gallery description',
            'dimensions'  => '600x450',
            'sort_order'  => 0,
        ]);
        $this->assertEquals(302, $response->response->status());
        $this->assertEquals(route('voyager.galleries.index'), $response->response->headers->get('Location'));

        $gallery = Gallery::where('title', 'Test gallery')->firstOrFail();

        $this->visitRoute('voyager.galleries.edit', $gallery)
            ->see('Test gallery')
            ->see('media-dropzone');

        $response = $this->put(route('voyager.galleries.update', $gallery), [
            'title'       => 'Updated gallery',
            'description' => 'Updated description',
            'dimensions'  => '800x600',
            'sort_order'  => 1,
        ]);
        $this->assertEquals(302, $response->response->status());
        $this->assertEquals(route('voyager.galleries.index'), $response->response->headers->get('Location'));

        $this->seeInDatabase('galleries', [
            'id'          => $gallery->id,
            'title'       => 'Updated gallery',
            'dimensions'  => '800x600',
            'description' => 'Updated description',
        ]);
    }

    public function testImagesCanBeUploadedSortedDescribedAndDeleted()
    {
        $gallery = Gallery::create([
            'title'      => 'Media gallery',
            'dimensions' => '400x300',
        ]);

        foreach (['one.jpg', 'two.jpg', 'three.jpg'] as $name) {
            $response = $this->call('POST', route('voyager.galleries.upload-media', $gallery), [], [], [
                'file' => UploadedFile::fake()->image($name, 640, 480)->size(200),
            ]);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertTrue($response->getData(true)['success']);
        }

        $media = $gallery->images()->get();
        $this->assertCount(3, $media);

        $response = $this->post(route('voyager.galleries.update-media-description', $gallery), [
            'media_id'    => $media[0]->id,
            'description' => 'First photo',
        ]);
        $this->assertEquals(200, $response->response->status());
        $this->assertTrue($response->response->json('success'));

        $this->assertEquals('First photo', $media[0]->fresh()->getCustomProperty('description'));

        $response = $this->post(route('voyager.galleries.sort-media', $gallery), [
            'media_ids' => [$media[2]->id, $media[0]->id, $media[1]->id],
        ]);
        $this->assertEquals(200, $response->response->status());
        $this->assertTrue($response->response->json('success'));

        $this->assertEquals(
            [$media[2]->id, $media[0]->id, $media[1]->id],
            $gallery->images()->pluck('id')->all()
        );

        $response = $this->post(route('voyager.galleries.delete-media', $gallery), [
            'media_id' => $media[1]->id,
        ]);
        $this->assertEquals(200, $response->response->status());
        $this->assertTrue($response->response->json('success'));

        $this->assertCount(2, $gallery->images()->get());
    }

    public function testGalleryTokenRendersFrontendMarkup()
    {
        $gallery = Gallery::create([
            'title'      => 'Frontend gallery',
            'dimensions' => '400x300',
        ]);

        foreach (['one.jpg', 'two.jpg', 'three.jpg'] as $name) {
            $gallery->addMedia(UploadedFile::fake()->image($name, 640, 480)->size(200))
                ->toMediaCollection('images');
        }

        $html = Str::parseGalleries('<p>Before</p>%gallery="Frontend gallery" form="default"%<p>After</p>');

        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('After', $html);
        $this->assertStringContainsString('voyager-gallery', $html);
        $this->assertEquals(3, substr_count($html, '<img'));
        $this->assertStringNotContainsString('%gallery=', $html);
    }

    public function testMissingGalleryLeavesSafeComment()
    {
        $html = Str::parseGalleries('%gallery="Does not exist"%');

        $this->assertStringNotContainsString('%gallery=', $html);
        $this->assertStringContainsString('not found or empty', $html);
    }
}
