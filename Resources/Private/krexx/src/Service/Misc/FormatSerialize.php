<?php

/*
MIT License

Copyright (c) 2021 Jesse Schalken

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Misc;

use Exception;

/**
 * Format a serialized string into a human-readable form.
 *
 * @see https://gist.github.com/jesseschalken/c4abddcb2fd7051389a4
 * @author Jesse Schalken
 *
 * Changes:
 *   - Micro optimizations
 *   - Strong typification
 *   - Code comments
 */
class FormatSerialize
{
    /**
     * The sting we want to format.
     *
     * @var string
     */
    protected $string;

    /**
     * The current offset for parsing.
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * Pretty print this serializes string.
     *
     * @param string $string
     *   The serialized string
     *
     * @return string|null
     *   The pretty printed string.
     *   Or NULL if the pretty print failed.
     */
    public function prettyPrint(string $string): ?string
    {
        $this->string = $string;
        $this->offset = 0;

        try {
            return $this->parse();
        } catch (Exception $exception) {
            // Could not format the string.
            return null;
        }
    }

    /**
     * Read a potion of the original serialized string.
     *
     * @param int $length
     *   The length we want to read.
     *
     * @return string
     *   The requested part of the string.
     */
    protected function read(int $length): string
    {
        $result = substr($this->string, $this->offset, $length);
        $this->offset += $length;

        return $result;
    }

    /**
     * Locate the stop string, and return the requested string.
     *
     * @param string $stopSting
     *   The stop string delimiter.
     *
     * @throws \Exception
     *   Stop the operation if when try to read too far.
     *
     * @return string
     *   The requested part of the string.
     */
    protected function readTo(string $stopSting): string
    {
        $position = strpos($this->string, $stopSting, $this->offset);

        if ($position === false) {
            throw new Exception(__FUNCTION__);
        }

        return $this->read($position - $this->offset);
    }

    /**
     * Make sure that the next part that we want to read contains exactly what
     * we need.
     *
     * @param string $string
     *   The expected string.
     *
     * @throws \Exception
     *   Stop the operation when we get anything else.
     *
     * @return string
     *   The expected string.
     */
    protected function assert(string $string): string
    {
        if ($this->read(strlen($string)) !== $string) {
            throw new Exception(__FUNCTION__);
        }

        return $string;
    }

    /**
     * Parse the array or object part.
     *
     * @param string $string
     *   The string we want to format.
     *
     * @throws \Exception
     *   Stop the operation when we encounter a problem.
     *
     * @return string
     *   The indented code.
     */
    protected function parseArrayOrObject(string $string): string
    {
        $result = $this->assert(':');
        $arrayLength = (int)$this->readTo(':');
        $result .= $arrayLength . $this->assert(':{') . $string;

        for ($i = 0; $i < $arrayLength; $i++) {
            $result .= '    ' . $this->parse($string . '    ') . ' ' .
                $this->parse($string . '    ') . $string;
        }

        return $result . $this->assert('}');
    }

    /**
     * Parse a string part until it's stop char.
     *
     * @throws \Exception
     *   Stop the operation when we encounter a problem.
     *
     * @return string
     *   The parsed string data.
     *
     */
    protected function parseString(): string
    {
        $result = $this->assert(':');
        $length = (int)$this->readTo(':');

        return $result . $length . $this->assert(':"') . $this->read($length) .
            $this->assert('"');
    }

    /**
     * Special handling for a serializable object.
     *
     * @codeCoverageIgnore
     *   According to the documentation, this should handle classes with the
     *   serializable interface. Expect, it doesn't. At least I was unable
     *   to trigger this one.
     *
     * @throws \Exception
     *   Stop the operation when we encounter a problem.
     *
     * @return string
     *   The indented code.
     */
    protected function parseSerializableObject(): string
    {
        $result = $this->parseString() . $this->assert(':');
        $length = (int) $this->readTo(':');

        return $result . $length . $this->assert(':{') . $this->read($length) .
            $this->assert('}');
    }

    /**
     * The magic starts here.
     *
     * @param string $string
     *   The string that needs parsing.
     *
     * @throws \Exception
     *   Stop the operation when we encounter a problem.
     *
     * @return string
     *   The indented code.
     */
    protected function parse(string $string = "\n"): string
    {
        switch ($result = $this->read(1)) {
            case 'N':
                // Null handling.
                $result .= $this->assert(';');
                break;
            case 'o':
                // The 'o' was removed in PHP 7.4
                // @deprecated.
            case 'O':
                // Object handling.
                $result .= $this->parseString() . $this->parseArrayOrObject($string);
                break;
            case 's':
                // String handling.
                $result .= $this->parseString() . $this->assert(';');
                break;
            case 'a':
                // Array handling.
                $result .= $this->parseArrayOrObject($string);
                break;
            case 'C':
                // Serializable object handling.
                $result .= $this->parseSerializableObject();
                break;
            default:
                // Boolean, float, integer.
                $result .= $this->assert(':') . $this->readTo(';') . $this->assert(';');
                break;
        }

        return $result;
    }
}
