<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Support\TermWriter;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tag $record */
        $record = $this->getRecord();

        $data['translations'] = app(TermWriter::class)->formRows($record);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Tag $term */
        $term = $record;

        return app(TermWriter::class)->save($term, $data);
    }
}
