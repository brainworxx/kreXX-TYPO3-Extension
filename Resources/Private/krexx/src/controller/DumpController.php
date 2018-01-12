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

namespace Brainworxx\Krexx\Controller;

/**
 * "Controller" for the dump (aka analysis) "action" ad the timer "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
class DumpController extends AbstractController
{
    /**
     * Dump information about a variable.
     *
     * Here everything starts and ends (well, unless we are only outputting
     * the settings editor).
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $headline
     *   The headline of the markup we want to produce. Only used by the timer.
     *
     * @return $this;
     *   Return $this for chaining.
     */
    public function dumpAction($data, $headline = '')
    {
        if ($this->pool->emergencyHandler->checkMaxCall() === true) {
            // Called too often, we might get into trouble here!
            return $this;
        }

        $this->pool->reset();

        // Find caller.
        $caller = $this->callerFinder->findCaller($headline, $data);

        // We need to get the footer before the generating of the header,
        // because we need to display messages in the header from the configuration.
        $footer = $this->outputFooter($caller);

        // We will only allow code generation, if we were able to determine the varname.
        $this->pool->scope->setScope($caller['varname']);

        // Start the magic.
        $analysis = $this->pool->routing->analysisHub(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setData($data)
                ->setName($caller['varname'])
        );

        // Detect the encoding on the start-chunk-string of the analysis
        // for a complete encoding picture.
        $this->pool->chunks->detectEncoding($analysis);

        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return $this;
        }

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        $this->pool->chunks->addMetadata($caller);

        $this->outputService->addChunkString($this->outputHeader($caller['type']));
        $this->outputService->addChunkString($analysis);
        $this->outputService->addChunkString($footer);

        return $this;
    }

    /**
     * Takes a "moment" for the benchmark test.
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     *
     * @return $this
     *   Return $this for chaining
     */
    public function timerAction($string)
    {
        // Did we use this one before?
        if (isset(static::$counterCache[$string]) === true) {
            // Add another to the counter.
            ++static::$counterCache[$string];
            static::$timekeeping['[' . static::$counterCache[$string] . ']' . $string] = microtime(true);
        } else {
            // First time counter, set it to 1.
            static::$counterCache[$string] = 1;
            static::$timekeeping[$string] = microtime(true);
        }

        return $this;
    }

    /**
     * Outputs the timer
     *
     * @return $this
     *   Return $this for chaining
     */
    public function timerEndAction()
    {
        $this->timerAction('end');
        // And we are done. Feedback to the user.
        $this->dumpAction($this->miniBenchTo(static::$timekeeping), 'kreXX timer');
        // Reset the timer vars.
        static::$timekeeping = array();
        static::$counterCache = array();

        return $this;
    }
}
