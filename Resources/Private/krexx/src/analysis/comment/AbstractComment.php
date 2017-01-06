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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Abstract class for the comment analysis.
 *
 * @package Brainworxx\Krexx\Analyse
 */
abstract class AbstractComment
{

    /**
     * @var Pool
     */
    protected $pool;

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
     * @param $reflection
     *   A already existing reflection of the method or property.
     * @param \ReflectionClass $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return @return string
     *   The prettified comment.
     */
    abstract public function getComment($reflection, \ReflectionClass $reflectionClass = null);

    /**
     * Removes the comment-chars from the comment string.
     *
     * @param string $comment
     *   The original comment from code.
     *
     * @return string
     *   The better readable comment
     */
    protected function prettifyComment($comment)
    {
        // We split our comment into single lines and remove the unwanted
        // comment chars with the array_map callback.
        $commentArray = explode("\n", $comment);
        $result = array();
        foreach ($commentArray as $commentLine) {
            // We skip lines with /** and */
            if ((strpos($commentLine, '/**') === false) && (strpos($commentLine, '*/') === false)) {
                // Remove comment-chars, but we need to leave the whitespace intact.
                $commentLine = trim($commentLine);
                if (strpos($commentLine, '*') === 0) {
                    // Remove the * by char position.
                    $result[] = substr($commentLine, 1);
                } else {
                    // We are missing the *, so we just add the line.
                    $result[] = $commentLine;
                }
            }
        }

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
     * @param $originalComment
     * @param $comment
     */
    protected function replaceInheritComment($originalComment, $comment)
    {
        $search = array(
            '{@inheritdoc}',
            '{inheritdoc}',
            '@inheritdoc',
            'inheritdoc'
        );
        return str_ireplace($search, $comment, $originalComment);
    }

    /**
     * Checks if we have resolved everything.
     *
     * @param string $comment
     *   The comment that we check for {@ inheritdoc}
     *
     * @return bool
     *   Do we need to check further?
     */
    protected function checkComment($comment)
    {
        if (stripos($comment, 'inheritdoc') === false) {
            // Not found means we have done our job.
            return true;
        } else {
            // We need to go deeper into the rabbit hole.
            return false;
        }
    }
}
