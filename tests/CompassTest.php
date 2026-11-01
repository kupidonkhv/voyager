<?php

namespace TCG\Voyager\Tests;

use Illuminate\Support\Facades\Auth;

class CompassTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::loginUsingId(1);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists(base_path('app/Models/TestModel.php'))) {
            unlink(base_path('app/Models/TestModel.php'));
        }
    }

    private function enableCompass()
    {
        $this->app['config']->set('voyager.compass_in_production', true);
    }

    private function logString($string)
    {
        \Illuminate\Support\Facades\Log::info($string);
    }

    public function testCantAccessCompass()
    {
        // Can't access compass because environment is != local (testing)
        $response = $this->call('GET', route('voyager.compass.index'));
        $this->assertEquals(403, $response->status());
    }

    public function testCanAccessCompass()
    {
        // Can access compass because we set voyager.compass_in_production configuration to true
        $this->enableCompass();

        $response = $this->call('GET', route('voyager.compass.index'));
        $this->assertEquals(200, $response->status());
    }

    public function testCanSeeLaravelLog()
    {
        $info = 'This is a test log';
        $this->logString($info);
        $this->enableCompass();

        $this->visit(route('voyager.compass.index').'?log='.base64_encode('laravel.log'))
             ->see($info);
    }

    public function testCanExecuteAllowedCommand()
    {
        $this->enableCompass();

        $response = $this->post(route('voyager.compass.index'), [
            'command' => 'list',
            'args'    => '',
        ]);
        $this->assertStringContainsString('Available commands:', $response->response->content());
    }

    public function testCannotExecuteCommandOutsideAllowList()
    {
        $this->enableCompass();

        $response = $this->post(route('voyager.compass.index'), [
            'command' => 'make:model',
            'args'    => 'TestModel',
        ]);
        $this->assertStringContainsString('This command is not allowed.', $response->response->content());
        $this->assertFileDoesNotExist(base_path('app/Models/TestModel.php'));
    }

    public function testCannotExecuteUnknownCommand()
    {
        $this->enableCompass();

        $response = $this->post(route('voyager.compass.index'), [
            'command' => 'unknown:command',
            'args'    => 'AnArgument',
        ]);
        $this->assertStringContainsString('This command is not allowed.', $response->response->content());
    }

    public function testCanDeleteLaravelLog()
    {
        $this->enableCompass();

        $response = $this->post(route('voyager.compass.index'), [
            'del' => base64_encode('laravel.log'),
        ]);
        $this->assertEquals(302, $response->response->getStatusCode()); // Redirect
    }

    public function testCannotDeleteLogWithGet()
    {
        $this->enableCompass();
        $this->logString('keep me');

        $response = $this->call('GET', route('voyager.compass.index').'?del='.base64_encode('laravel.log'));
        $this->assertEquals(405, $response->status());
        $this->assertFileExists(storage_path('logs/laravel.log'));
    }

    public function testCannotReadFileOutsideLogs()
    {
        $this->enableCompass();

        $canary = storage_path('audit-compass-canary.txt');
        file_put_contents($canary, 'AUDIT_COMPASS_CANARY');

        try {
            $response = $this->call('GET', route('voyager.compass.index').'?download='.base64_encode($canary));
            $this->assertEquals(404, $response->status());
        } finally {
            @unlink($canary);
        }
    }
}
