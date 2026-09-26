<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Concerns;

use BasekitLaravel\BasekitLaravelBlog\Data\PostData;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Maps the post form state, which keeps translations as a list of rows, onto
 * the locale keyed input the write actions validate.
 */
trait InteractsWithPostForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function postInput(array $data): array
    {
        $translations = [];

        foreach ((array) ($data['translations'] ?? []) as $item) {
            $item = (array) $item;

            $locale = LocaleRegistry::normalize(Str::lower(trim((string) ($item['locale'] ?? ''))));
            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '' || $locale === '') {
                continue;
            }

            $translations[$locale] = [
                'title' => $item['title'],
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'] ?? null,
                'status' => $item['status'] ?? 'draft',
                'published_at' => $item['published_at'] ?? null,
                'is_featured' => (bool) ($item['is_featured'] ?? false),
                'featured_image' => $item['featured_image'] ?? null,
                'image_alt' => $item['image_alt'] ?? null,
                'meta_title' => $item['meta_title'] ?? null,
                'meta_description' => $item['meta_description'] ?? null,
                'slug' => $item['slug'] ?? null,
            ];
        }

        return [
            'author_id' => $data['author_id'] ?? null,
            'translations' => $translations,
            'category_ids' => array_values((array) ($data['category_ids'] ?? [])),
            'tag_ids' => array_values((array) ($data['tag_ids'] ?? [])),
            // The form shows every locale at once, so a removed row means the
            // locale is gone rather than left untouched.
            'replace_translations' => true,
        ];
    }

    /**
     * Validated input for the write actions, reporting what the action rejects
     * on the form field it belongs to instead of failing the request.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validatedPostInput(array $data): PostData
    {
        $input = $this->postInput($data);
        $validator = Validator::make(PostData::normalizedInput($input), PostData::rules());

        if ($validator->fails()) {
            $this->reportWriteErrors($validator->errors()->toArray());
            $this->halt();
        }

        return PostData::fromValidated($validator->validated());
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function reportWriteErrors(array $errors): void
    {
        foreach ($this->formErrors($errors) as $key => $message) {
            $this->addError($key, $message);
        }
    }

    /**
     * The actions key their errors by locale, the form addresses its rows by
     * index, so a rejected field is reported where the editor sees it.
     *
     * @param  array<string, list<string>>  $errors
     * @return array<string, string>
     */
    private function formErrors(array $errors): array
    {
        $indexes = [];

        foreach (array_values((array) ($this->data['translations'] ?? [])) as $index => $row) {
            $locale = LocaleRegistry::normalize(Str::lower(trim((string) ((((array) $row)['locale']) ?? ''))));

            if ($locale !== '') {
                $indexes[$locale] = $index;
            }
        }

        $messages = [];

        foreach ($errors as $key => $error) {
            $key = (string) $key;
            $segments = explode('.', $key);

            if (count($segments) >= 3 && $segments[0] === 'translations' && isset($indexes[$segments[1]])) {
                $key = 'translations.'.$indexes[$segments[1]].'.'.implode('.', array_slice($segments, 2));
            }

            $messages[$key] = (string) ($error[0] ?? $key);
        }

        return $messages;
    }

    /**
     * One form row per configured locale, followed by any translation of the
     * post in a locale the site no longer serves so it is not lost on save.
     *
     * @return list<array<string, mixed>>
     */
    protected function translationRows(Post $post): array
    {
        $post->loadMissing(['translations', 'categories', 'tags']);

        $rows = [];
        $seen = [];

        foreach (LocaleRegistry::fromConfig()->all() as $locale) {
            $seen[] = $locale;
            $rows[] = $this->translationRow($post, $locale);
        }

        foreach ($post->translations as $translation) {
            if (! in_array($translation->locale, $seen, true)) {
                $rows[] = $this->translationRow($post, $translation->locale);
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function translationRow(Post $post, string $locale): array
    {
        $translation = $post->translation($locale);

        return [
            'locale' => $locale,
            'title' => $translation?->title,
            'excerpt' => $translation?->excerpt,
            'content' => $translation?->content,
            'status' => $translation?->status->value ?? 'draft',
            'published_at' => $translation?->published_at?->format('Y-m-d H:i:s'),
            'is_featured' => (bool) $translation?->is_featured,
            'featured_image' => $translation?->featured_image,
            'image_alt' => $translation?->image_alt,
            'meta_title' => $translation?->meta_title,
            'meta_description' => $translation?->meta_description,
            'slug' => $post->slug($locale),
        ];
    }
}
