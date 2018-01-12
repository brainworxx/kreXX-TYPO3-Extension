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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Comment;

/**
 * We get the comment of a method and try to resolve the inheritdoc stuff.
 *
 * @package Brainworxx\Krexx\Analyse\Comment
 */
class Methods extends AbstractComment
{

    /**
     * The name of the method we are analysing.
     *
     * @var string
     */
    protected $methodName;

    /**
     * Get the method comment and resolve the inheritdoc.
     *
     * Simple wrapper around the getMethodComment() to make sure
     * we only escape it once!
     *
     * @param \Reflector $reflectionMethod
     *   An already existing reflection of the method.
     * @param \ReflectionClass $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return string
     *   The prettified and escaped comment.
     */
    public function getComment(\Reflector $reflectionMethod, \ReflectionClass $reflectionClass = null)
    {
        // Do some static caching. The comment will not change during a run.
        static $cache = array();
        /** @var \ReflectionMethod $reflectionMethod */
        $this->methodName = $reflectionMethod->getName();
        $cachingKey = $reflectionClass->getName() . '::' . $this->methodName;

        if (isset($cache[$cachingKey]) === true) {
            return $cache[$cachingKey];
        }

        // Cache not found. We need to generate this one.
        $cache[$cachingKey] = $this->pool->encodingService->encodeString(
            $this->getMethodComment($reflectionMethod, $reflectionClass)
        );
        return $cache[$cachingKey];
    }

    /**
     * Get the method comment and resolve the inheritdoc.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   An already existing reflection of the method.
     * @param \ReflectionClass $reflectionClass
     *   An already existing reflection of the original class.
     *
     * @return string
     *   The prettified comment.
     */
    protected function getMethodComment(\ReflectionMethod $reflectionMethod, \ReflectionClass $reflectionClass)
    {
        // Get a first impression.
        $comment = $this->prettifyComment($reflectionMethod->getDocComment());

        if ($this->checkComment($comment) === true) {
            // Found it!
            return trim($comment);
        }

        // Check for interfaces.
        $comment = $this->getInterfaceComment($comment, $reflectionClass);

        if ($this->checkComment($comment) === true) {
            // Found it!
            return trim($comment);
        }

        // Check for traits.
        $comment = $this->getTraitComment($comment, $reflectionClass);

        if ($this->checkComment($comment) === true) {
            // Found it!
            return trim($comment);
        }

        // Nothing on this level, we need to take a look at the parent.
        $parentReflection = $reflectionClass->getParentClass();
        if ($parentReflection === true &&
            $parentReflection->hasMethod($this->methodName) === true
        ) {
            // Going deeper into the rabid hole!
            $comment = trim(
                $this->getMethodComment(
                    $parentReflection->getMethod($this->methodName),
                    $parentReflection
                )
            );
        }

        // Still here? Tell the dev that we could not resolve the comment.
        return trim($this->replaceInheritComment($comment, $this->pool->messages->getHelp('commentResolvingFail')));
    }

    /**
     * Gets the comment from all added traits.
     *
     * Iterated through an array of traits, to see
     * if we can resolve the inherited comment. Traits
     * are only supported since PHP 5.4, so we need to
     * check if they are available.
     *
     * @param string $originalComment
     *   The original comment, so far.
     * @param \ReflectionClass $reflection
     *   A reflection of the object we are currently analysing.
     *
     * @return string
     *   The comment from one of the trait.
     */
    protected function getTraitComment($originalComment, \ReflectionClass $reflection)
    {
        // We need to check if we can get traits here.
        if (method_exists($reflection, 'getTraits') === true) {
            // Get the traits from this class.
            // Now we should have an array with reflections of all
            // traits in the class we are currently looking at.
            foreach ($reflection->getTraits() as $trait) {
                if ($this->checkComment($originalComment) === true) {
                    // Looks like we've resolved them all.
                    return $originalComment;
                }

                // We need to look further!
                if ($trait->hasMethod($this->methodName) === true) {
                    $traitComment = $this->prettifyComment(
                        $trait->getMethod($this->methodName)->getDocComment()
                    );
                    // Replace it.
                    $originalComment = $this->replaceInheritComment($originalComment, $traitComment);
                }
            }

            // Return what we could resolve so far.
            return $originalComment;
        }

        // Wrong PHP version. Traits are not available.
        return $originalComment;
    }

    /**
     * Gets the comment from all implemented interfaces.
     *
     * Iterated through an array of interfaces, to see
     * if we can resolve the inherited comment.
     *
     * @param string $originalComment
     *   The original comment, so far.
     * @param \ReflectionClass $reflectionClass
     *   A reflection of the object we are currently analysing.
     *
     * @return string
     *   The comment from one of the interfaces.
     */
    protected function getInterfaceComment($originalComment, \ReflectionClass $reflectionClass)
    {
        foreach ($reflectionClass->getInterfaces() as $interface) {
            if ($this->checkComment($originalComment) === true) {
                // Looks like we've resolved them all.
                return $originalComment;
            }

            // We need to look further.
            if ($interface->hasMethod($this->methodName) === true) {
                $interfaceComment = $this->prettifyComment($interface->getMethod($this->methodName)->getDocComment());
                // Replace it.
                $originalComment = $this->replaceInheritComment($originalComment, $interfaceComment);
            }
        }

        // Return what we could resolve so far.
        return $originalComment;
    }
}
