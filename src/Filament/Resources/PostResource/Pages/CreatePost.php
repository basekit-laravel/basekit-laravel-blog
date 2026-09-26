<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages;

use BasekitLaravel\BasekitLaravelBlog\Actions\SavePost;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Concerns\InteractsWithPostForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePost extends CreateRecord
{
    use InteractsWithPostForm;

    protected static string $resource = PostResource::class;

    /**
     * The write path of the package: the form is turned into the same validated
     * input the API and console callers use, so nothing can be persisted
     * through the admin that the actions would reject.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SavePost::class)->execute($this->validatedPostInput($data));
    }
}
