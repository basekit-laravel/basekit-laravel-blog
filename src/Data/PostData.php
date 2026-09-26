<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Data;

use BackedEnum;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * The validated input of a post write: the author, one or more locales and the
 * taxonomy assignments.
 *
 * Translations are keyed by locale so callers can build the payload from a form
 * or an API request without knowing about the table layout. The DTO is the only
 * thing the actions accept, which keeps validation at the edge and the write
 * path free of raw input.
 */
final readonly class PostData
{
    /**
     * @param  array<string, PostTranslationData>  $translations
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     */
    public function __construct(
        public array $translations,
        public ?int $authorId,
        public array $categoryIds,
        public array $tagIds,
        /**
         * When true the payload is the complete set of locales of the post and
         * locales left out of it are removed. Editors that see every locale at
         * once, such as the Filament form, ask for this; a partial API update
         * leaves the locales it does not mention alone.
         */
        public bool $replaceTranslations = false,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public static function fromInput(array $input): self
    {
        return self::fromValidated(self::validate($input));
    }

    /**
     * The validated payload, for callers that want to report what failed
     * against their own fields instead of catching an exception.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<array-key, mixed>
     *
     * @throws ValidationException
     */
    public static function validate(array $input): array
    {
        return Validator::make(self::normalizedInput($input), self::rules())->validate();
    }

    /**
     * The input in the shape the rules below expect it, for callers that build
     * their own validator around them.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<array-key, mixed>
     */
    public static function normalizedInput(array $input): array
    {
        return self::normalize($input);
    }

    /**
     * @param  array<array-key, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = [];

        foreach ((array) ($validated['translations'] ?? []) as $locale => $attributes) {
            $translations[LocaleRegistry::normalize((string) $locale)] = PostTranslationData::fromArray(
                (array) $attributes,
                (string) $locale,
            );
        }

        return new self(
            translations: $translations,
            authorId: isset($validated['author_id']) ? (int) $validated['author_id'] : null,
            categoryIds: array_values(array_map(intval(...), (array) ($validated['category_ids'] ?? []))),
            tagIds: array_values(array_map(intval(...), (array) ($validated['tag_ids'] ?? []))),
            replaceTranslations: (bool) ($validated['replace_translations'] ?? false),
        );
    }

    /**
     * Form and console callers may hand over backed enums, for example a status
     * selected in Filament, where the string rules below expect scalars.
     *
     * @param  array<array-key, mixed>  $input
     * @return array<array-key, mixed>
     */
    private static function normalize(array $input): array
    {
        foreach ($input as $key => $value) {
            if ($value instanceof BackedEnum) {
                $input[$key] = $value->value;

                continue;
            }

            if (is_array($value)) {
                $input[$key] = self::normalize($value);
            }
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'author_id' => ['nullable', 'integer', 'min:1'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*' => ['array'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.excerpt' => ['nullable', 'string', 'max:2000'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.status' => ['required', 'string', 'in:draft,scheduled,published'],
            'translations.*.published_at' => [
                'nullable',
                'date',
                'required_if:translations.*.status,scheduled',
                'required_if:translations.*.status,published',
            ],
            'translations.*.is_featured' => ['nullable', 'boolean'],
            'translations.*.featured_image' => ['nullable', 'string', 'max:2048'],
            'translations.*.image_alt' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:1000'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'replace_translations' => ['sometimes', 'boolean'],
        ];
    }
}
