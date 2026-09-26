<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Support;

/**
 * The locales the blog knows about, resolved from the package configuration.
 *
 * Locales are compared on their primary subtag so "hu-HU", "hu_HU" and "hu"
 * address the same translation. Nothing falls back implicitly: a request for a
 * locale the blog does not publish is a 404, never the default locale.
 */
final readonly class LocaleRegistry
{
    /**
     * @param  list<string>  $supported
     */
    public function __construct(
        public string $default,
        private array $supported,
    ) {}

    public static function fromConfig(): self
    {
        /** @var list<string> $supported */
        $supported = array_values(array_unique(array_map(
            self::normalize(...),
            array_filter(
                array_map(strval(...), (array) config('basekit-laravel-blog.supported_locales', ['en'])),
                static fn (string $locale): bool => $locale !== '',
            ),
        )));

        $default = self::normalize((string) config('basekit-laravel-blog.default_locale', 'en'));

        if ($supported === []) {
            $supported = [$default];
        }

        if (! in_array($default, $supported, true)) {
            $supported[] = $default;
        }

        return new self($default, $supported);
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->supported;
    }

    public function isSupported(string $locale): bool
    {
        return in_array(self::normalize($locale), $this->supported, true);
    }

    public static function normalize(string $locale): string
    {
        $locale = str_replace('_', '-', trim($locale));

        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }

        return mb_strtolower($locale);
    }
}
