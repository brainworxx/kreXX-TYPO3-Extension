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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Skins\Hans;

/**
 * Renderer for a single line source code.
 */
trait BacktraceSourceLine
{
    /**
     * @var string[]
     */
    private $markerBacktraceSourceLine = [
        '{className}',
        '{lineNo}',
        '{sourceCode}',
    ];

    /**
     * {@inheritdoc}
     */
    public function renderBacktraceSourceLine(string $className, int $lineNo, string $sourceCode): string
    {
        return str_replace(
            $this->markerBacktraceSourceLine,
            [
                $className,
                $lineNo,
                $sourceCode,
            ],
            $this->fileCache[static::FILE_BACKTRACE_SOURCELINE]
        );
    }

    /**
     * Getter of the backtrace source line for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerBacktraceSourceLine(): array
    {
        return $this->markerBacktraceSourceLine;
    }
}
