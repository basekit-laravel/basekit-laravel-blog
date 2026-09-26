<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Actions\SavePost;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Concerns\InteractsWithPostForm;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPost extends EditRecord
{
    use InteractsWithPostForm;

    protected static string $resource = PostResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Post $post */
        $post = $this->getRecord();

        $data['translations'] = $this->translationRows($post);
        $data['category_ids'] = $post->categories->pluck('id')->all();
        $data['tag_ids'] = $post->tags->pluck('id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Post $post */
        $post = $record;

        return app(SavePost::class)->execute($this->validatedPostInput($data), $post);
    }
}
