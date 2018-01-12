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

namespace Brainworxx\Krexx\Analyse\Caller;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Finder for the script part that has actually called kreXX.
 * Used for the PHP part.
 *
 * @package Brainworxx\Krexx\Analyse\Caller
 */
class CallerFinder extends AbstractCaller
{

    /**
     * Injects the pool, sets the callPattern to search for.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
         parent::__construct($pool);

        // Setting the search pattern.
        $this->callPattern = array(
            'krexx',
            'krexx::open',
            'krexx::' . $this->pool->config->getDevHandler(),
            'Krexx::open',
            'Krexx::' . $this->pool->config->getDevHandler()
        );
        $this->pattern = 'krexx';
    }

    /**
     * {@inheritdoc}
     */
    public function findCaller($headline, $data)
    {
        $backtrace = debug_backtrace();
        $pattern = strtolower($this->pattern);

        // Going from the first call of the first line up
        // through the first debug call.
        // Using a foreach is definitely faster, but then we
        // would have trouble using our pattern.
        while ($caller = array_pop($backtrace)) {
            if (isset($caller['function']) && strtolower($caller['function']) === $pattern) {
                break;
            }

            if (isset($caller['class']) && strtolower($caller['class']) === $pattern) {
                break;
            }
        }

        $varname = $this->getVarName($caller['file'], $caller['line']);

        // We will not keep the whole backtrace im memory. We only return what we
        // actually need.
        return array(
            'file' => htmlspecialchars($this->pool->fileService->filterFilePath($caller['file'])),
            'line' => (int)$caller['line'],
            'varname' => $varname,
            'type' => $this->getType($headline, $varname, $data),
        );
    }

    /**
     * Tries to extract the name of the variable which we try to analyse.
     *
     * @param string $file
     *   Path to the sourcecode file.
     * @param string $line
     *   The line from where kreXX was called.
     *
     * @return string
     *   The name of the variable.
     */
    protected function getVarName($file, $line)
    {
        // Retrieve the call from the sourcecode file.
        if ($this->pool->fileService->fileIsReadable($file) === false) {
            return '. . .';
        }

        $line--;

        // Now that we have the line where it was called, we must check if
        // we have several commands in there.
        $possibleCommands = explode(';', $this->pool->fileService->readFile($file, $line, $line));
        // Now we must weed out the none krexx commands.
        foreach ($possibleCommands as $key => $command) {
            if (strpos(strtolower($command), strtolower($this->pattern)) === false) {
                unset($possibleCommands[$key]);
            }
        }

        // I have no idea how to determine the actual call of krexx if we
        // are dealing with several calls per line.
        if (count($possibleCommands) === 1) {
            // Now that we have our actual call, we must remove the krexx-part
            // from it.
            foreach ($this->callPattern as $funcname) {
                // This little baby tries to resolve everything inside the
                // brackets of the kreXX call.
                preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', reset($possibleCommands), $name);
                if (isset($name[1]) === true) {
                    $varname = $this->pool->encodingService->encodeString(trim($name[1], " \t\n\r\0\x0B'\""));
                    break;
                }
            }
        }

        // Check if we have a value.
        if (empty($varname) === true) {
            $varname = '. . .';
        }

        return $varname;
    }
}
