<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    /**
     * @return list<self>
     */
    public static function publiclyVisible(): array
    {
        return [self::Published, self::Scheduled];
    }
}
