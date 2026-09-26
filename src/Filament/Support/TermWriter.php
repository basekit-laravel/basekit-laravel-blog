<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Support;

use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Saves the translated taxonomy terms of the admin. A category and a tag are
 * the same shape — an identity with one name per locale — so they share the
 * write path, the row parsing and the form state.
 */
final class TermWriter
{
    /**
     * Save a term and its translated names.
     *
     * The admin form always submits one row per locale, so a locale the editor
     * removed is a locale the term no longer has: its translation and its slug
     * are removed with it. Data that does not carry a `translations` key at all
     * is a partial update and leaves the existing translations alone.
     *
     * @template TTerm of Category|Tag
     *
     * @param  TTerm  $term
     * @param  array<string, mixed>  $data
     * @return TTerm
     */
    public function save(Category|Tag $term, array $data): Model
    {
        $replace = array_key_exists('translations', $data);

        DB::transaction(function () use ($term, $data, $replace): void {
            $term->save();

            $rows = $this->rows($data);

            foreach ($rows as $row) {
                $term->translations()->updateOrCreate(
                    ['locale' => $row['locale']],
                    ['name' => $row['name']],
                );
            }

            if ($replace) {
                // Deleted per model, so releasing the slug of a removed locale
                // happens the same way it does for a post.
                $term->translations()
                    ->whereNotIn('locale', array_column($rows, 'locale'))
                    ->get()
                    ->each(fn (Model $translation) => $translation->delete());
            }
        });

        return $term->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{locale: string, name: string}>
     */
    public function rows(array $data): array
    {
        $rows = [];

        foreach ((array) ($data['translations'] ?? []) as $item) {
            $item = (array) $item;

            $locale = LocaleRegistry::normalize(Str::lower(trim((string) ($item['locale'] ?? ''))));
            $name = trim((string) ($item['name'] ?? ''));

            if ($locale === '' || $name === '') {
                continue;
            }

            $rows[] = ['locale' => $locale, 'name' => $name];
        }

        return $rows;
    }

    /**
     * One form row per configured locale, followed by any name the term still
     * has in a locale the site no longer serves, so it is not lost on save.
     *
     * @return list<array{locale: string, name: string|null}>
     */
    public function formRows(Category|Tag $term): array
    {
        $term->loadMissing('translations');

        $rows = [];
        $seen = [];

        foreach (LocaleRegistry::fromConfig()->all() as $locale) {
            $seen[] = $locale;
            $rows[] = ['locale' => $locale, 'name' => $term->translation($locale)->name];
        }

        foreach ($term->translations as $translation) {
            if (! in_array($translation->locale, $seen, true)) {
                $rows[] = ['locale' => $translation->locale, 'name' => $translation->name];
            }
        }

        return $rows;
    }
}
