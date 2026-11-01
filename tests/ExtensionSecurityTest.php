<?php

namespace TCG\Voyager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use MonstreX\VoyagerExtension\VoyagerExtensionServiceProvider;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Gallery;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;

class ExtensionSecurityTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [
            VoyagerExtensionServiceProvider::class,
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('voyager.storage.disk', 'public'));

        // The extension listens to the Voyager routing event, which can fire
        // before the installer has created the DataTypes in this test setup.
        (new VoyagerExtensionServiceProvider($this->app))->addRoutes(app('router'));
        app('router')->getRoutes()->refreshNameLookups();
    }

    private function assetUrl(string $path): string
    {
        $prefix = trim((string) config('voyager-extension.assets_path_prefix', 'admin'), '/');

        return '/'.$prefix.'/voyager-extension/'.$path;
    }

    private function traversalToProjectRoot(string $target): string
    {
        $projectRoot = realpath(dirname(__DIR__));
        $assetsRoot = realpath($projectRoot.'/publishable/assets/vendor/voyager-extension');
        $relative = str_replace('\\', '/', substr($assetsRoot, strlen($projectRoot) + 1));
        $depth = count(explode('/', $relative));

        return str_repeat('..%252F', $depth).str_replace('/', '%252F', $target);
    }

    public function testAssetsRegularStillServesRealAssets()
    {
        $assetsRoot = dirname(__DIR__).'/publishable/assets/vendor/voyager-extension';
        $file = File::allFiles($assetsRoot)[0];
        $relative = str_replace('\\', '/', $file->getRelativePathname());

        $response = $this->get($this->assetUrl($relative))->response;

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAssetsRegularDoesNotReadOutsideAssetsDirectory()
    {
        $canary = storage_path('audit-extension-canary.txt');
        file_put_contents($canary, 'AUDIT_EXTENSION_CANARY');

        try {
            $payload = $this->traversalToProjectRoot('storage/audit-extension-canary.txt');
            $response = $this->get($this->assetUrl($payload))->response;

            $this->assertEquals(404, $response->getStatusCode());
            $this->assertStringNotContainsString('AUDIT_EXTENSION_CANARY', $response->getContent());
        } finally {
            @unlink($canary);
        }
    }

    public function testAssetsRegularReturnsNotFoundForMissingFile()
    {
        $this->assertEquals(404, $this->get($this->assetUrl('robots.txt'))->response->getStatusCode());
    }

    public function testReaderCannotDeleteGalleryMedia()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();

        Auth::login($reader);

        $response = $this->post(route('voyager.galleries.ext-media.remove'), [
            'media_ids' => [$media->getKey()],
        ]);

        $this->assertEquals(403, $response->response->getStatusCode());
        $this->seeInDatabase('media', ['id' => $media->getKey()]);
        Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());
    }

    public function testSuperAdminCanDeleteGalleryMedia()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();

        Auth::loginUsingId(1);

        $response = $this->post(route('voyager.galleries.ext-media.remove'), [
            'media_ids' => [$media->getKey()],
        ]);

        $this->assertEquals(200, $response->response->getStatusCode());
        $this->notSeeInDatabase('media', ['id' => $media->getKey()]);
    }

    public function testMediaFromAnotherModelCannotBeDeletedThroughGalleriesRoute()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();

        $foreign = Media::create([
            'model_type'   => 'App\\Models\\OtherGallery',
            'model_id'     => 999,
            'uuid'         => '00000000-0000-0000-0000-000000000001',
            'collection_name' => 'images',
            'name'         => 'foreign',
            'file_name'    => 'foreign.jpg',
            'mime_type'    => 'image/jpeg',
            'disk'         => 'public',
            'size'         => 1,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
        ]);

        Auth::loginUsingId(1);

        $response = $this->post(route('voyager.galleries.ext-media.remove'), [
            'media_ids' => [$foreign->getKey()],
        ]);

        $this->assertEquals(403, $response->response->getStatusCode());
        $this->seeInDatabase('media', ['id' => $foreign->getKey()]);
    }

    public function testReaderCannotSortGalleryMedia()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();
        $order = $media->order_column;

        Auth::login($reader);

        $response = $this->post(route('voyager.galleries.ext-media.sort'), [
            'files_ids_order' => [$media->getKey()],
        ]);

        $this->assertEquals(403, $response->response->getStatusCode());
        $this->assertEquals($order, $media->fresh()->order_column);
    }

    public function testChangeMediaRejectsServerSideExtension()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();

        $dataType = DataType::where('slug', 'galleries')->firstOrFail();
        DataRow::create([
            'data_type_id' => $dataType->id,
            'field' => 'images',
            'type' => 'image',
            'display_name' => 'Images',
            'required' => 0,
            'browse' => 1,
            'read' => 1,
            'edit' => 1,
            'add' => 1,
            'delete' => 1,
            'details' => '{}',
            'order' => 99,
        ]);

        Auth::loginUsingId(1);

        $response = $this->call('POST', route('voyager.galleries.ext-media.change'), [
            'id' => $gallery->getKey(),
            'field' => 'images',
            'media_file_id' => $media->getKey(),
        ], [], [
            'images' => UploadedFile::fake()->image('payload.php', 20, 20),
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(1, $gallery->getMedia('images')->count());
    }

    public function testUpdateMediaOnlyAcceptsKnownFields()
    {
        [$reader, $gallery, $media] = $this->readerAndGalleryMedia();

        $dataType = DataType::where('slug', 'galleries')->firstOrFail();
        DataRow::create([
            'data_type_id' => $dataType->id,
            'field' => 'images',
            'type' => 'image',
            'display_name' => 'Images',
            'required' => 0,
            'browse' => 1,
            'read' => 1,
            'edit' => 1,
            'add' => 1,
            'delete' => 1,
            'details' => '{}',
            'order' => 99,
        ]);

        Auth::loginUsingId(1);

        $response = $this->post(route('voyager.galleries.ext-media.update'), [
            'id' => $gallery->getKey(),
            'field' => 'images',
            'media_file_id' => $media->getKey(),
            'title' => 'Allowed title',
            'alt' => 'Allowed alt',
            '_token' => 'forbidden',
            'role_id' => 999,
        ]);

        $this->assertEquals(200, $response->response->getStatusCode());
        $this->assertEquals('Allowed title', $media->fresh()->getCustomProperty('title'));
        $this->assertEquals('Allowed alt', $media->fresh()->getCustomProperty('alt'));
        $this->assertNull($media->fresh()->getCustomProperty('_token'));
        $this->assertNull($media->fresh()->getCustomProperty('role_id'));
    }

    private function readerAndGalleryMedia(): array
    {
        $role = Role::create(['name' => 'reader', 'display_name' => 'Reader']);
        $permissions = [
            Permission::firstOrCreate(['key' => 'browse_admin', 'table_name' => 'admin']),
            Permission::firstOrCreate(['key' => 'browse_galleries', 'table_name' => 'galleries']),
            Permission::firstOrCreate(['key' => 'read_galleries', 'table_name' => 'galleries']),
        ];
        $role->permissions()->sync(array_map(fn ($permission) => $permission->id, $permissions));

        $reader = User::create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        $gallery = Gallery::create(['title' => 'Gallery A', 'dimensions' => '400x300']);
        $media = $gallery->addMedia(UploadedFile::fake()->image('a.jpg', 20, 20))
            ->toMediaCollection('images');

        return [$reader, $gallery, $media];
    }
}
