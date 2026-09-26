<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Tests;

use BasekitLaravel\BasekitLaravelBlog\Tests\TestSupport\BlogPanelProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;

/**
 * The blog suite runs against the framework only. Filament registers its
 * providers through package discovery, which Testbench does not run, so the
 * admin tests opt in to the same providers an application gets.
 */
abstract class FilamentTestCase extends TestCase
{
    /**
     * @return array<int, class-string>
     */
    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ...self::filamentProviders(),
            BlogPanelProvider::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    protected static function filamentProviders(): array
    {
        return [
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
        ];
    }
}
