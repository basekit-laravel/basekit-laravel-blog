<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\CategoryResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Support\TermWriter;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Category $record */
        $record = $this->getRecord();

        $data['translations'] = app(TermWriter::class)->formRows($record);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Category $term */
        $term = $record;

        return app(TermWriter::class)->save($term, $data);
    }
}
