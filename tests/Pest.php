<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelBlog\Tests\FilamentTestCase;
use BasekitLaravel\BasekitLaravelBlog\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');
uses(FilamentTestCase::class)->in(__DIR__.'/Filament');
