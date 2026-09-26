<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
