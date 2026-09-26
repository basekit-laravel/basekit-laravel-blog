<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport;

use BasekitLaravel\BasekitLaravelBlog\Filament\BlogPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;

/**
 * A panel that only exists for the suite, so the package resources can be
 * exercised the way an application registers them.
 */
class BlogPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('blog')
            ->path('admin')
            ->plugin(new BlogPlugin)
            ->authMiddleware([Authenticate::class]);
    }
}
