<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/*
|--------------------------------------------------------------------------
| Rector
|--------------------------------------------------------------------------
|
| Refactoring is opt-in: `composer refactor-dry` shows the diff, `composer
| refactor` applies it. The sets below are the ones that are safe for a
| distributed package — they never rename a public API member and never
| change runtime behaviour. Pint remains the formatter of record, so the
| coding style set is deliberately left out.
|
*/

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/database',
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
    );
