<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Registers the Blog admin screens on a panel:
 *
 *     $panel->plugin(new BlogPlugin)
 */
final class BlogPlugin implements Plugin
{
    public function getId(): string
    {
        return 'basekit-laravel-blog';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PostResource::class,
            CategoryResource::class,
            TagResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
