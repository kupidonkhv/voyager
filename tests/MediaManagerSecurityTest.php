<?php

namespace TCG\Voyager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaManagerSecurityTest extends TestCase
{
    protected $storage;

    public function setUp(): void
    {
        parent::setUp();

        Auth::loginUsingId(1);
        $this->storage = Storage::fake(config('voyager.storage.disk', 'public'));
    }

    private function upload(UploadedFile $file, string $path = '/')
    {
        return $this->call('POST', route('voyager.media.upload'), [
            'upload_path' => $path,
        ], [], [
            'file' => $file,
        ]);
    }

    public function testServerSideExtensionIsRejectedBeforeStorage()
    {
        $response = $this->upload(UploadedFile::fake()->image('payload.php', 20, 20));

        $this->assertFalse($response->getData(true)['success']);
        $this->assertEmpty(array_filter($this->storage->allFiles(), function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php';
        }));
    }

    public function testDoubleExtensionIsSanitizedBeforeStorage()
    {
        $response = $this->upload(UploadedFile::fake()->image('shell.php.png', 20, 20));

        $body = $response->getData(true);
        $this->assertTrue($body['success'], $body['message'] ?? json_encode($body));

        $files = $this->storage->allFiles();
        $this->assertNotEmpty($files);
        $this->assertStringEndsWith('.png', $files[0]);
        $this->assertStringNotContainsString('.php.', $files[0]);
        $this->assertStringNotContainsString('.php', $files[0]);
    }

    public function testUploadPathTraversalIsRejected()
    {
        $response = $this->upload(UploadedFile::fake()->image('photo.png', 20, 20), '../outside');

        $body = $response->getData(true);
        $this->assertFalse($body['success']);
        $this->assertEmpty($this->storage->allFiles());
    }

    public function testNormalImageUploadsStillWork()
    {
        $response = $this->upload(UploadedFile::fake()->image('photo.png', 20, 20));

        $body = $response->getData(true);
        $this->assertTrue($body['success'], $body['message'] ?? json_encode($body));
        $this->assertNotEmpty($this->storage->allFiles());
    }
}
