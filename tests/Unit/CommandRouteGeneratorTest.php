<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CommandRouteGeneratorTest extends TestCase
{
    protected function tearDown(): void
    {
        if (file_exists(base_path('resources/js')) && is_dir(base_path('resources/js'))) {
            array_map(function ($file) {
                unlink($file);
            }, glob(base_path('resources/js/*')));
        }

        parent::tearDown();
    }

    /** @test */
    public function can_create_file()
    {
        Artisan::call('ziggy:generate');

        $this->assertFileExists(base_path('resources/js/ziggy.js'));
    }

    /** @test */
    public function can_create_file_in_correct_location_when_called_outside_project_root()
    {
        chdir('..');
        $this->assertNotEquals(base_path(), getcwd());

        Artisan::call('ziggy:generate');

        $this->assertFileExists(base_path('resources/js/ziggy.js'));
    }

    /** @test */
    public function can_generate_file_with_named_routes()
    {
        $router = app('router');
        $router->get('posts/{post}/comments', $this->noop())->name('postComments.index');
        $router->getRoutes()->refreshNameLookups();

        Artisan::call('ziggy:generate');

        if ($this->laravelVersion(7)) {
            $this->assertFileEquals('./tests/fixtures/ziggy.js', base_path('resources/js/ziggy.js'));
        } else {
            $this->assertSame(
                str_replace(',"bindings":[]', '', file_get_contents(__DIR__ . '/../fixtures/ziggy.js')),
                file_get_contents(base_path('resources/js/ziggy.js'))
            );
        }
    }

    /** @test */
    public function can_generate_file_with_custom_url()
    {
        $router = app('router');
        $router->get('posts/{post}/comments', $this->noop())->name('postComments.index');
        $router->getRoutes()->refreshNameLookups();

        Artisan::call('ziggy:generate', ['--url' => 'http://example.org']);

        if ($this->laravelVersion(7)) {
            $this->assertFileEquals('./tests/fixtures/custom-url.js', base_path('resources/js/ziggy.js'));
        } else {
            $this->assertSame(
                str_replace(',"bindings":[]', '', file_get_contents(__DIR__ . '/../fixtures/custom-url.js')),
                file_get_contents(base_path('resources/js/ziggy.js'))
            );
        }
    }

    /** @test */
    public function can_generate_file_with_config_applied()
    {
        config(['ziggy' => [
            'except' => ['admin.*'],
        ]]);
        $router = app('router');
        $router->get('posts/{post}/comments', $this->noop())->name('postComments.index');
        $router->get('admin', $this->noop())->name('admin.dashboard'); // Excluded, should NOT be present in file
        $router->getRoutes()->refreshNameLookups();

        Artisan::call('ziggy:generate');

        if ($this->laravelVersion(7)) {
            $this->assertFileEquals('./tests/fixtures/ziggy.js', base_path('resources/js/ziggy.js'));
        } else {
            $this->assertSame(
                str_replace(',"bindings":[]', '', file_get_contents(__DIR__ . '/../fixtures/ziggy.js')),
                file_get_contents(base_path('resources/js/ziggy.js'))
            );
        }
    }

    /** @test */
    public function can_generate_file_for_specific_configured_route_group()
    {
        config(['ziggy' => [
            'except' => ['admin.*'],
            'groups' => [
                'admin' => ['admin.*'],
            ],
        ]]);
        $router = app('router');
        $router->get('posts/{post}/comments', $this->noop())->name('postComments.index');
        $router->get('admin', $this->noop())->name('admin.dashboard');
        $router->getRoutes()->refreshNameLookups();

        Artisan::call('ziggy:generate', ['path' => 'resources/js/admin.js', '--group' => 'admin']);

        if ($this->laravelVersion(7)) {
            $this->assertFileEquals('./tests/fixtures/admin.js', base_path('resources/js/admin.js'));
        } else {
            $this->assertSame(
                str_replace(',"bindings":[]', '', file_get_contents(__DIR__ . '/../fixtures/admin.js')),
                file_get_contents(base_path('resources/js/admin.js'))
            );
        }
    }
}
