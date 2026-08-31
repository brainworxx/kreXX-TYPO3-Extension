<?php

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Comment;

use ReflectionClass;
use Reflector;

/**
 * Get the comment from the class, function and property.
 */
class Comment extends AbstractComment
{
    /**
     * Get the prettified comment.
     *
     * @param \ReflectionClass $reflection
     *   The actual reflection class.
     * @param \ReflectionClass|null $reflectionClass
     *   Not used.
     *
     * @return string
     *   The comment.
     */
    public function getComment(Reflector $reflection, ?ReflectionClass $reflectionClass = null): string
    {
        if (property_exists($reflection, 'isUndeclared') && $reflection->isUndeclared !== null) {
            return '';
        }

        return $this->pool->encodingService->encodeString(
            data: $this->prettifyComment(comment: $reflection->getDocComment())
        );
    }
}
