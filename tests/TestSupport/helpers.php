<?php

declare(strict_types=1);
use TailwindMerge\TailwindMerge;

/*
 * The Basekit Blocks `frame` component depends on a global `twMerge()` helper
 * that is normally provided by the consuming application. In standalone package
 * tests we define a guarded fallback so block-rendering tests run without the
 * host app, delegating to the tailwind-merge engine.
 */

if (! function_exists('twMerge')) {
    function twMerge(...$classLists): string
    {
        return TailwindMerge::instance()->merge($classLists);
    }
}
