<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;

it('normalizes a locale to its primary subtag', function (string $input, string $expected): void {
    expect(LocaleRegistry::normalize($input))->toBe($expected);
})->with([
    ['en', 'en'],
    ['EN', 'en'],
    ['hu-HU', 'hu'],
    ['hu_HU', 'hu'],
    ['  de-DE  ', 'de'],
]);

it('reads the configured locales', function (): void {
    config()->set('basekit-laravel-blog.default_locale', 'hu');
    config()->set('basekit-laravel-blog.supported_locales', ['en', 'hu']);

    $locales = LocaleRegistry::fromConfig();

    expect($locales->default)->toBe('hu')
        ->and($locales->all())->toBe(['en', 'hu'])
        ->and($locales->isSupported('hu-HU'))->toBeTrue()
        ->and($locales->isSupported('de'))->toBeFalse();
});

it('always serves the default locale', function (): void {
    config()->set('basekit-laravel-blog.default_locale', 'hu');
    config()->set('basekit-laravel-blog.supported_locales', ['en']);

    expect(LocaleRegistry::fromConfig()->all())->toBe(['en', 'hu']);
});

it('falls back to the default locale when none is configured', function (): void {
    config()->set('basekit-laravel-blog.default_locale', 'en');
    config()->set('basekit-laravel-blog.supported_locales', []);

    $locales = LocaleRegistry::fromConfig();

    expect($locales->all())->toBe(['en'])
        ->and($locales->isSupported('en'))->toBeTrue();
});

it('normalizes the configured locales', function (): void {
    config()->set('basekit-laravel-blog.default_locale', 'EN');
    config()->set('basekit-laravel-blog.supported_locales', ['EN', 'hu-HU']);

    $locales = LocaleRegistry::fromConfig();

    expect($locales->default)->toBe('en')
        ->and($locales->all())->toBe(['en', 'hu']);
});
