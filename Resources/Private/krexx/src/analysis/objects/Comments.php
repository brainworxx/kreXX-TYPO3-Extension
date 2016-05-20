<?php
/**
 * @file
 *   Comments analysis of object methods for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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


namespace Brainworxx\Krexx\Analysis\Objects;

/**
 * Class Comments
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Comments
{

    /**
     * Gets comments from the reflection.
     *
     * Inherited comments are resolved by recursion of this function.
     *
     * @param string $originalComment
     *   The original comment, so far. We use this function recursively,
     *   new comments are added until all of them are resolved.
     * @param \ReflectionClass $reflection
     *   The reflection class of the object we want to analyse.
     * @param string $methodName
     *   The name of the method from which we ant to get the comment.
     *
     * @return string
     *   The generated markup.
     */
    public static function getParentalComment($originalComment, \ReflectionClass $reflection, $methodName)
    {
        if (stripos($originalComment, '{@inheritdoc}') !== false) {
            // now we need to get the parentclass and the comment
            // from the parent function
            /* @var \ReflectionClass $parentClass */
            $parentClass = $reflection->getParentClass();
            if (!is_object($parentClass)) {
                // we've gone too far
                // maybe a trait?
                return self::getTraitComment($originalComment, $reflection, $methodName);
            }

            try {
                $parentMethod = $parentClass->getMethod($methodName);
                $parentComment = self::prettifyComment($parentMethod->getDocComment());
            } catch (\ReflectionException $e) {
                // Looks like we are trying to inherit from a not existing method
                // maybe a trait?
                return self::getTraitComment($originalComment, $reflection, $methodName);
            }
            // Replace it.
            $originalComment = str_ireplace('{@inheritdoc}', $parentComment, $originalComment);
            // and search for further parental comments . . .
            return Comments::getParentalComment($originalComment, $parentClass, $methodName);
        } else {
            // We don't need to do anything with it.
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
     * @param \ReflectionClass $reflection
     *   A reflection of the object we are currently analysing.
     * @param string $methodName
     *   The name of the method from which we ant to get the comment.
     *
     * @return string
     *   The generated markup.
     */
    public static function getInterfaceComment($originalComment, \ReflectionClass $reflection, $methodName)
    {
        if (stripos($originalComment, '{@inheritdoc}') !== false) {
            $interfaceArray = $reflection->getInterfaces();
            foreach ($interfaceArray as $interface) {
                if (stripos($originalComment, '{@inheritdoc}') !== false) {
                    try {
                        $interfaceMethod = $interface->getMethod($methodName);
                        if (!is_object($interfaceMethod)) {
                            // We've gone too far.
                            // We should tell the user, that we could not resolve
                            // the inherited comment.
                            $originalComment = str_ireplace(
                                '{@inheritdoc}',
                                ' ***could not resolve inherited comment*** ',
                                $originalComment
                            );
                        } else {
                            $interfacecomment = self::prettifyComment($interfaceMethod->getDocComment());
                            // Replace it.
                            $originalComment = str_ireplace('{@inheritdoc}', $interfacecomment, $originalComment);
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
            // We are still here ?!? Return the original comment.
            return $originalComment;
        } else {
            return $originalComment;
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
     *   The generated markup.
     */
    public static function getTraitComment($originalComment, \ReflectionClass $reflection, $methodName)
    {
        if (stripos($originalComment, '{@inheritdoc}') !== false) {
            // We need to check if we can get traits here.
            if (method_exists($reflection, 'getTraits')) {
                // Get the traits from this class.
                $traitArray = $reflection->getTraits();
                // Get the traits from the parent traits.
                foreach ($traitArray as $trait) {
                    $parentTraits = $trait->getTraits();
                    // Merge them into our trait array to get al parents.
                    $traitArray = array_merge($traitArray, $parentTraits);
                }
                // Now we should have an array with reflections of all
                // traits in the class we are currently looking at.
                foreach ($traitArray as $trait) {
                    try {
                        $traitMethod = $trait->getMethod($methodName);
                        if (!is_object($traitMethod)) {
                            // We've gone too far.
                            // We should tell the user, that we could not resolve
                            // the inherited comment.
                            $originalComment = str_ireplace(
                                '{@inheritdoc}',
                                ' ***could not resolve inherited comment*** ',
                                $originalComment
                            );
                        } else {
                            $traitComment = self::prettifyComment($traitMethod->getDocComment());
                            // Replace it.
                            $originalComment = str_ireplace('{@inheritdoc}', $traitComment, $originalComment);
                        }
                    } catch (\ReflectionException $e) {
                        // Method not found.
                        // We should try the next trait.
                    }
                }
                // Return what we could resolve so far.
                return $originalComment;
            } else {
                // Wrong PHP version. Traits are not available.
                // Maybe there is something in the interface?
                return $originalComment;
            }
        } else {
            return $originalComment;
        }
    }

    /**
     * Removes the comment-chars from the comment string.
     *
     * @param string $comment
     *   The original comment from the reflection
     *   (or interface) in case if an inherited comment.
     *
     * @return string
     *   The better readable comment
     */
    public static function prettifyComment($comment)
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
}
