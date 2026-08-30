<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Tests;

use BasekitLaravel\BasekitLaravelBlocks\BasekitLaravelBlocksServiceProvider;
use BasekitLaravel\BasekitLaravelBlog\BasekitLaravelBlogServiceProvider;
use BasekitLaravel\BasekitLaravelUi\BasekitServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $migration = include __DIR__.'/../database/migrations/2026_01_01_000001_create_posts_table.php';
        $migration->up();
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
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BasekitLaravelBlogServiceProvider::class,
            BasekitLaravelBlocksServiceProvider::class,
            BasekitServiceProvider::class,
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
        ]);
    }
}
