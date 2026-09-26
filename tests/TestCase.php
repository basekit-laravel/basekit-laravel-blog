<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Tests;

use BasekitLaravel\BasekitLaravelBlog\BasekitLaravelBlogServiceProvider;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\Models\User;
use BasekitLaravel\BasekitLaravelSeo\BasekitLaravelSeoServiceProvider;
use BasekitLaravel\BasekitLaravelSlugs\BasekitLaravelSlugsServiceProvider;
use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->migratePackageDatabase();
    }

    #[\Override]
    /**
     * @return string[]
     *
     * @psalm-return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BasekitLaravelSlugsServiceProvider::class,
            BasekitLaravelSeoServiceProvider::class,
            BasekitLaravelBlogServiceProvider::class,
        ];
    }

    #[\Override]
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('basekit-laravel-blog.models.user', User::class);

        $app['config']->set('basekit-laravel-seo.defaults.site_name', 'Testing Blog');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * The blog publishes its migrations to the consuming application, so the
     * suite applies them itself in filename order. The slugs package loads its
     * migration from the package, so it is located through the installed class.
     */
    protected function migratePackageDatabase(): void
    {
        $paths = [
            __DIR__.'/../database/migrations',
            $this->slugsMigrationPath(),
        ];

        foreach ($paths as $path) {
            $files = glob($path.'/*.php');

            if ($files === false) {
                continue;
            }

            sort($files);

            foreach ($files as $file) {
                $migration = include $file;

                $migration->up();
            }
        }
    }

    protected function slugsMigrationPath(): string
    {
        /** @var string $trait */
        $trait = new ReflectionClass(HasSlugs::class)->getFileName();

        return dirname($trait, 2).'/database/migrations';
    }
}
