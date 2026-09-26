<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Support\TermWriter;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TermWriter::class)->save(new Tag, $data);
    }
}
