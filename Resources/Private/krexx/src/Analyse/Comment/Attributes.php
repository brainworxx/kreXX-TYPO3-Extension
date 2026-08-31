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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

use Reflector;
use Throwable;
use UnitEnum;

/**
 * Retrieve the attributes of a class and flatten them into a string.
 *
 * And yes, we handle attributes as comments.
 */
class Attributes
{
    /**
     * Returns the flattened attributes of a class.
     *
     * This method is used to get a string representation of the attributes
     * of a class, which might be useful for debugging.
     *
     * @param Reflector $reflection
     *   The reflection object of the class.
     *
     * @return string
     *   A string containing the flattened attributes.
     */
    public function getAttributes(Reflector $reflection): string
    {
        try {
            $attributes = $reflection->getAttributes();
        } catch (Throwable) {
        }

        // Wrong PHP version, or no attributes available.
        if (empty($attributes)) {
            return '';
        }

        // We have attributes, so we can flatten them.
        $result = [];
        /** @var \ReflectionAttribute $attribute */
        foreach ($attributes as $attribute) {
            // Get the name of the attribute class.
            try {
                $name = $attribute->getName();
                $arguments = $attribute->getArguments();
            } catch (Throwable) {
                // In case of an error, we skip this attribute.
                continue;
            }

            if (empty($arguments)) {
                // If there are no arguments, we can just return the name.
                $result[] = '#[' . $name . ']';
                continue;
            }
            // Get the arguments of the attribute.
            $flattenedArguments = '';

            foreach ($arguments as $argument) {
                $flattenedArguments .= $this->flattenArgument(parameter: $argument, indention: 4, useIndention: true);
            }
            // Combine the name and arguments into a single string.
            $result[] = '#[' . $name . '(' . $flattenedArguments . PHP_EOL . ')]';
        }

        // Join all attribute strings with a comma and a space.
        return implode(separator: PHP_EOL, array: $result);
    }

    /**
     * We flatten and prettify the arguments of an attribute.
     *
     * @param int|float|string|bool|UnitEnum|null|object $parameter
     *   The parameter to be flattened.
     * @param int $indention
     *   The current indentation level.
     * @param bool $useIndention
     *   Whether to use indentation for the result.
     *
     * @return string
     */
    protected function flattenArgument(mixed $parameter, int $indention, bool $useIndention): string
    {
        $result = $useIndention ? $this->indent(indention: $indention) : '';
        switch (true) {
            case $parameter === null:
                $result .= 'NULL,';
                break;
            case is_string(value: $parameter):
                $result .= '\'' . $parameter . '\',';
                break;
            case is_numeric(value: $parameter):
                $result .= $parameter . ',';
                break;
            case $parameter === true:
                $result .= 'TRUE,';
                break;
            case $parameter === false:
                $result .= 'FALSE,';
                break;
            case is_array(value: $parameter):
                $result .= $this->handleArray(parameter: $parameter, indention: $indention) . ',';
                break;
            case $parameter instanceof UnitEnum:
                $result .= $parameter::class . '::' . $parameter->name;
                break;
            case is_object(value: $parameter):
                // If the parameter is an object, we return its class name.
                $result .= $parameter::class . '::class,';
        }

        return $result;
    }

    /**
     * Handles the array parameter and formats it with indentation.
     *
     * @param array $parameter
     *   The array parameter to be formatted.
     * @param int $indention
     *   The current indentation level.
     *
     * @return string
     *   A string representation of the formatted array.
     */
    protected function handleArray(array $parameter, int $indention): string
    {
        if ($parameter === []) {
            // If the array is empty, we return an empty array representation.
            return str_repeat(string: ' ', times: $indention - 4) . '[]';
        }
        $result = str_repeat(string: ' ', times: $indention) . '[';
        $indention += 4;
        foreach ($parameter as $key => $value) {
            // Add '' around the key if it is a string.
            $key = is_int(value: $key) ? $key : '\'' . $key . '\'';
            $result .= $this->indent(indention: $indention) . $key . ' => ' .
                $this->flattenArgument(parameter: $value, indention: $indention, useIndention: false);
        }

        return trim(string: $result, characters: ', ') . $this->indent(indention: $indention - 4) . ']';
    }

    /**
     * Returns a new line with the specified indentation.
     *
     * @param int $indention
     *   The number of spaces to indent.
     *
     * @return string
     *   A string containing the new line with the specified indentation.
     */
    protected function indent(int $indention): string
    {
        return PHP_EOL . str_repeat(string: ' ', times: $indention);
    }
}
