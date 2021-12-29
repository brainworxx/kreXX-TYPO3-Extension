<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Comment;

use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionClass;
use Reflector;

/**
 * Abstract class for the comment analysis.
 */
abstract class AbstractComment
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Pattern for the finding of inherited comments.
     *
     * @var string[]
     */
    protected $inheritdocPattern = [
        '{@inheritDoc}',
        '{@inheritdoc}',
        '@inheritDoc',
        '@inheritdoc'
    ];

    /**
     * Inject the pool
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * We get the comment.
     *
     * @param \Reflector $reflection
     *   An already existing reflection of the method or function.
     * @param \ReflectionClass|null $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return string
     *   The prettified comment.
     */
    abstract public function getComment(Reflector $reflection, ReflectionClass $reflectionClass = null): string;

    /**
     * Removes the comment-chars from the comment string.
     *
     * @param string|bool $comment
     *   The original comment from code.
     *
     * @return string
     *   The better readable comment
     */
    protected function prettifyComment($comment): string
    {
        if ($comment === false) {
            return '';
        }

        // We split our comment into single lines and remove the unwanted
        // comment chars with the array_map callback.
        // We skip lines with /** and */
        $result = [];
        foreach (array_slice(explode("\n", $comment), 1, -1) as $commentLine) {
            // Remove comment-chars and trim the whitespace.
            $result[] = trim($commentLine, "* \t\n\r\0\x0B");
        }

        // Sadly, we must not escape this here, or glue it with <br /> for a
        // direct display. The thing is, we may resolve several @inheritdoc
        // marks. The escaping and nlbr() will be done when everything is
        // stitched together.
        return implode(PHP_EOL, $result);
    }

    /**
     * We replace the @ inheritdoc in the comment.
     *
     * The inheritdoc may be mistyped. We will replace the following:
     * - inheritdoc
     * - @inheritdoc
     * - {inheritdoc}
     * - {@inheritdoc}
     *
     * @param string $originalComment
     *   The original comment, featuring the inheritance documentor.
     * @param string $comment
     *   The string to replace the inheritance documentor.
     *
     * @return string
     *   The comment, where the inheritdoc doc comment was replaced.
     */
    protected function replaceInheritComment(string $originalComment, string $comment): string
    {
        foreach ($this->inheritdocPattern as $pattern) {
            // Replace the first we find. There may be others in there,
            // and we must not replace them with themselves, causing
            // the comment to repeat itself.
            if (strpos($originalComment, $pattern) !== false) {
                // Found one, and end the foreach.
                $originalComment = str_replace($pattern, $comment, $originalComment);
                break;
            }
        }

        return $originalComment;
    }

    /**
     * Checks if we have resolved everything.
     *
     * @param string $comment
     *   The comment that we check for {@ inheritdoc}
     *
     * @return bool
     *   true = found them all
     *   false = we need to look further
     */
    protected function checkComment(string $comment): bool
    {
        return (stripos($comment, 'inheritdoc') === false);
    }
}
