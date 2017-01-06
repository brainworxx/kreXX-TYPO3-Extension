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

/**
 * We get the comment of a method and try to resolve the inheritdoc stuff.
 *
 * @package Brainworxx\Krexx\Analyse
 */
class Methods extends AbstractComment
{
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
    public function getComment($reflectionMethod, \ReflectionClass $reflectionClass = null)
    {
        // Get a first impression.
        $comment = $this->prettifyComment($reflectionMethod->getDocComment());

        if ($this->checkComment($comment)) {
            // Found it!
            return $this->pool->encodeString(trim($comment));
        } else {
            // Check for interfaces.
            $comment = $this->getInterfaceComment($comment, $reflectionClass, $reflectionMethod->name);
        }

        if ($this->checkComment($comment)) {
            // Found it!
            return $this->pool->encodeString(trim($comment));
        } else {
            // Check for traits.
            $comment = $this->getTraitComment($comment, $reflectionClass, $reflectionMethod->name);
        }

        if ($this->checkComment($comment)) {
            // Found it!
            return $this->pool->encodeString(trim($comment));
        } else {
            // Nothing on this level, we need to take a look at the parent.
            try {
                $parentReflection = $reflectionClass->getParentClass();
                if (is_object($parentReflection)) {
                    $parentMethod = $parentReflection->getMethod($reflectionMethod->name);
                    if (is_object($parentMethod)) {
                        // Going deeper into the rabid hole!
                        $comment = trim($this->getComment($parentMethod, $parentReflection));
                    }
                }
            } catch (\ReflectionException $e) {
                // Too deep, comment not found :-(
            }

            // Still here? Tell the dev that we could not resolve the comment.
            $comment = $this->replaceInheritComment($comment, '::could not resolve {@inheritdoc} comment::');
            return $this->pool->encodeString(trim($comment));
        }
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
     * @param string $methodName
     *   The name of the method from which we ant to get the comment.
     *
     * @return string
     *   The comment from one of the trait.
     */
    protected function getTraitComment($originalComment, \ReflectionClass $reflection, $methodName)
    {
        // We need to check if we can get traits here.
        if (method_exists($reflection, 'getTraits')) {
            // Get the traits from this class.
            $traitArray = $reflection->getTraits();
            // Now we should have an array with reflections of all
            // traits in the class we are currently looking at.
            foreach ($traitArray as $trait) {
                if (!$this->checkComment($originalComment)) {
                    try {
                        $traitMethod = $trait->getMethod($methodName);
                        if (is_object($traitMethod)) {
                            // We've gone too far.
                            // We should check the next trait.
                        } else {
                            $traitComment = $this->prettifyComment($traitMethod->getDocComment());
                            // Replace it.
                            $originalComment = $this->replaceInheritComment($originalComment, $traitComment);
                        }
                    } catch (\ReflectionException $e) {
                        // Method not found.
                        // We should try the next trait.
                    }
                } else {
                    // Looks like we've resolved them all.
                    return $originalComment;
                }
            }
            // Return what we could resolve so far.
            return $originalComment;
        } else {
            // Wrong PHP version. Traits are not available.
            return $originalComment;
        }
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
     * @param string $methodName
     *   The name of the method from which we ant to get the comment.
     *
     * @return string
     *   The comment from one of the interfaces.
     */
    protected function getInterfaceComment($originalComment, \ReflectionClass $reflectionClass, $methodName)
    {

        $interfaceArray = $reflectionClass->getInterfaces();
        foreach ($interfaceArray as $interface) {
            if (!$this->checkComment($originalComment)) {
                try {
                    $interfaceMethod = $interface->getMethod($methodName);
                    if (!is_object($interfaceMethod)) {
                        // We've gone too far.
                        // We should try the next interface.
                    } else {
                        $interfaceComment = $this->prettifyComment($interfaceMethod->getDocComment());
                        // Replace it.
                        $originalComment = $this->replaceInheritComment($originalComment, $interfaceComment);
                    }
                } catch (\ReflectionException $e) {
                    // Method not found.
                    // We should try the next interface.
                }
            } else {
                // Looks like we've resolved them all.
                return $originalComment;
            }
        }
        // Return what we could resolve so far.
        return $originalComment;

    }
}
