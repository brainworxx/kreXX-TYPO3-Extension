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

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Finder for the script part that has actually called kreXX.
 * Used for the PHP part.
 */
class CallerFinder extends AbstractCaller implements BacktraceConstInterface, CallbackConstInterface
{
    /**
     * Pattern used to find the kreXX call in the backtrace.
     *
     * Can be overwritten by extending classes.
     *
     * @var string
     */
    protected const CLASS_PATTERN = Krexx::class;

    /**
     * Pattern used to find the kreXX call in the backtrace.
     *
     * Can be overwritten by extending classes.
     *
     * @var string
     */
    protected const FUNCTION_PATTERN = 'krexx';


    /**
     * Injects the pool, sets the callPattern to search for.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
         parent::__construct($pool);

        // Setting the search pattern.
        $this->callPattern = [
            'krexx',
            'krexxlog',
            'krexx::open',
            'Krexx',
            'Krexxlog',
            'Krexx::open',
            'Krexx::log',
            'krexx::log',
        ];
        $this->pattern = static::FUNCTION_PATTERN;
    }

    /**
     * {@inheritdoc}
     */
    public function findCaller(string $headline, $data): array
    {
        $backtrace = array_reverse(debug_backtrace(0, 5));

        // Going from the first call of the first line, up through the first debug call.
        foreach ($backtrace as $caller) {
            if ($this->identifyCaller($caller)) {
                break;
            }
        }

        $varname = empty($headline) ?
            $this->getVarName($caller[static::TRACE_FILE], $caller[static::TRACE_LINE]) :
            $headline;

        // We will not keep the whole backtrace im memory. We only return what we
        // actually need.
        return [
            static::TRACE_FILE => $caller[static::TRACE_FILE],
            static::TRACE_LINE => (int)$caller[static::TRACE_LINE],
            static::TRACE_VARNAME => $varname,
            static::TRACE_TYPE => $this->getType($headline, $varname, $data),
            static::TRACE_DATE => date(static::TIME_FORMAT, time()),
            static::TRACE_URL => $this->getCurrentUrl(),
        ];
    }

    /**
     * If clauses camped together in a method, trying to identify the current caller.
     *
     * @param array $caller
     *   A backtrace step.
     *
     * @return bool
     *   Well, is this our caller?
     */
    protected function identifyCaller(array $caller): bool
    {
        return (
                // Check for a function trace.
                isset($caller[static::TRACE_FUNCTION]) &&
                strpos(strtolower($caller[static::TRACE_FUNCTION]), static::FUNCTION_PATTERN) === 0
            ) ||
            (
                // Check for a class trace.
                isset($caller[static::TRACE_CLASS]) &&
                $caller[static::TRACE_CLASS] === static::CLASS_PATTERN
            );
    }

    /**
     * Tries to extract the name of the variable which we try to analyse.
     *
     * @param string $file
     *   Path to the sourcecode file.
     * @param int $line
     *   The line from where kreXX was called.
     *
     * @return string
     *   The name of the variable.
     */
    protected function getVarName(string $file, int $line): string
    {
        // Set a fallback value.
        $varname = static::UNKNOWN_VALUE;

        // Retrieve the call from the sourcecode file.
        if (!$this->pool->fileService->fileIsReadable($file)) {
            return $varname;
        }

        $commendLine = $this->pool->fileService->readFile($file, --$line, $line);

        return $this->removeKrexxPartFromCommand($commendLine);
    }

    /**
     * Remove the kreXX part from the command to get the variable.
     *
     * @param string $command
     *   The possible command
     *
     * @return string
     *   The variable, or fallback to '. . .'
     */
    protected function removeKrexxPartFromCommand(string $command): string
    {
        foreach ($this->callPattern as $funcname) {
            // This little baby tries to resolve everything inside the
            // brackets of the kreXX call.
            preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', $command, $name);
            if (isset($name[1])) {
                return $this->pool
                    ->encodingService
                    ->encodeString(
                        $this->pool->createClass(CleanUpVarName::class)
                            ->cleanup(trim($name[1], " \t\n\r\0\x0B'\""))
                    );
            }
        }

        return static::UNKNOWN_VALUE;
    }
}
