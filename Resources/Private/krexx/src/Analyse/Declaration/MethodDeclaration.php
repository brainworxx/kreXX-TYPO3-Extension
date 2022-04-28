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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Declaration;

use ReflectionClass;
use ReflectionMethod;
use Reflector;
use ReflectionParameter;

class MethodDeclaration extends AbstractDeclaration
{
    /**
     * Get the declaration place of this method.
     *
     * @param \ReflectionMethod $reflection
     *   Reflection of the method we are analysing.
     *
     * @return string
     *   The analysis result.
     */
    public function retrieveDeclaration(Reflector $reflection): string
    {
        $messages = $this->pool->messages;
        $reflectionClass = $reflection->getDeclaringClass();

        if ($reflectionClass->isInternal()) {
            return $messages->getHelp('metaPredeclared');
        }

        $filename = $this->pool->fileService->filterFilePath((string)$reflection->getFileName());
        if (empty($filename)) {
            // Not sure, if this is possible.
            return $this->pool->messages->getHelp('unknownDeclaration');
        }

        // If the filename of the $declaringClass and the $reflectionMethod differ,
        // we are facing a trait here.
        $secondLine = $messages->getHelp('metaInClass') . $reflection->class . "\n";
        if ($reflection->getFileName() !== $reflectionClass->getFileName()) {
            // There is no real clean way to get the name of the trait that we
            // are looking at.
            $traitName = ':: unable to get the trait name ::';
            $trait = $this->retrieveDeclaringReflection($reflection, $reflectionClass);
            if ($trait !== false) {
                $traitName = $trait->getName();
            }

            $secondLine = $messages->getHelp('metaInTrait') . $traitName . "\n";
        }

        return $filename . "\n" . $secondLine . $messages->getHelp('metaInLine') .
            $reflection->getStartLine();
    }

    /**
     * Retrieve the return type by the reflection.
     *
     * @param \Reflector $reflection
     * @return string
     */
    public function retrieveReturnType(Reflector $reflection): string
    {
        $namedType = $reflection->getReturnType();
        if ($namedType === null) {
            // It is not typed.
            return '';
        }

        $nullable = $namedType->allowsNull() ? '?' : '';

        return $nullable . $this->retrieveNamedType($namedType);
    }

    /**
     * Retrieve the parameter type.
     *
     * Depending on the available PHP version, we need to take different measures.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter, what the variable name says.
     *
     * @return string
     *   The parameter type, if available.
     */
    public function retrieveParameterType(ReflectionParameter $reflectionParameter): string
    {
        return $reflectionParameter->hasType() ?
            $this->retrieveNamedType($reflectionParameter->getType()) . ' ' : '';
    }

    /**
     * Retrieve the declaration class reflection from traits.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass $declaringClass
     *   The original declaring class, the one with the traits.
     *
     * @return bool|\ReflectionClass
     *   false = unable to retrieve something.
     *   Otherwise, return a reflection class.
     */
    protected function retrieveDeclaringReflection(ReflectionMethod $reflectionMethod, ReflectionClass $declaringClass)
    {
        // Get a first impression.
        if ($reflectionMethod->getFileName() === $declaringClass->getFileName()) {
            return $declaringClass;
        }

        // Go through the first layer of traits.
        // No need to recheck the availability for traits. This is done above.
        foreach ($declaringClass->getTraits() as $trait) {
            $result = $this->retrieveDeclaringReflection($reflectionMethod, $trait);
            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }
}
