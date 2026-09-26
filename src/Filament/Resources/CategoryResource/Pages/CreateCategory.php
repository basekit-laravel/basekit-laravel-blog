<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Support\TermWriter;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TermWriter::class)->save(new Category, $data);
    }
}
