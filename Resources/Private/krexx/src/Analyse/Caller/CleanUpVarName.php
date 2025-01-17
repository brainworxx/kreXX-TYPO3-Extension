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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Caller;

/**
 * When used inline, may have some trailing ')' at the end.
 * Things may get really confusing, if we have a string with '(' somewhere
 * in there. Hence, we need to actually count them, and try to identify any
 * string.
 *
 * @example
 *   krexx($this->parameterizedMethod('()"2'))->whatever();
 *   Should result ignore the whatever at the end.
 *   This only fixes a flaw in the regex.
 */
class CleanUpVarName
{
    /**
     * The "level" of the () nesting.
     *
     * We start with a -1, because we need to stop right before. Every opening
     * bracket has a closing one.
     *
     * @var int
     */
    protected int $level = -1;

    /**
     * What the variable name says.
     *
     * @var bool
     */
    protected bool $singleQuoteInactive = true;

    /**
     * What the variable name says.
     *
     * @var bool
     */
    protected bool $doubleQuoteInactive = true;

    /**
     * Was the last char an escape character?
     * --> \
     *
     * @var bool
     */
    protected bool $lastCharWasEscape = false;

    /**
     * What the method name says.
     *
     * @param string $name
     *   The variable name, before the cleanup.
     *
     * @return string
     *   The variable name, after the cleanup.
     */
    public function cleanup(string $name): string
    {
        // Counting all real round brackets, while ignoring the ones inside strings.
        foreach (str_split($name) as $count => $char) {
            if ($this->isReady($char)) {
                return substr($name, 0, $count);
            }
            $this->adjustActiveQuotes($char);
        }

        return $name;
    }

    /**
     * Are we ready?
     *
     * @param string $char
     *   The current char we are working on.
     *
     * @return bool
     *   Well? Are we?
     */
    protected function isReady(string $char): bool
    {
        $quotesAreInactive = $this->singleQuoteInactive && $this->doubleQuoteInactive;

        if ($char === '(' && $quotesAreInactive) {
            --$this->level;
        } elseif ($char === ')' && $quotesAreInactive) {
            ++$this->level;
        }

        return $quotesAreInactive && $this->level === 0;
    }

    /**
     * Reassign the booleans for the next char.
     *
     * Nice, huh?
     *
     * @param string $char
     *   The current char we are working on.
     */
    protected function adjustActiveQuotes(string $char): void
    {
        if ($this->lastCharWasEscape) {
            $this->lastCharWasEscape = false;
            return;
        }

        $this->singleQuoteInactive = $this->singleQuoteInactive ===
            !($char === '\'' && $this->doubleQuoteInactive);

        $this->doubleQuoteInactive = $this->doubleQuoteInactive ===
            !($char === '"' && $this->singleQuoteInactive);

        $this->lastCharWasEscape = $char === '\\';
    }
}
