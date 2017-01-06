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

namespace Brainworxx\Krexx\Service\Flow;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Emergency break handler for large output (runtime and memory usage).
 *
 * @package Brainworxx\Krexx\Service\Flow
 */
class Emergency
{
    /**
     * Counts how often kreXX was called.
     *
     * @var int
     */
    protected $krexxCount = 0;

    /**
     * Unix timestamp, used to determine if we need to do an emergency break.
     *
     * @var int
     */
    protected $timer = 0;

    /**
     * Stores if the emergency break is enabled.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Has this one failed before?
     *
     * @var bool
     */
    protected static $allIsOk = true;

    /**
     * Maximum runtime from the config, cached.
     *
     * @var int
     */
    protected $maxRuntime = 0;

    /**
     * The server memory limit, coming from the php.ini.
     *
     * @var int
     */
    protected $serverMemoryLimit = 0;

    /**
     * Cached configuration of the minimum leftover memory (MB).
     *
     * @var int
     */
    protected $minMemoryLeft = 0;

    /**
     * The level inside the object/array hierarchy we are in.
     *
     * @var int
     */
    protected $nestingLevel = 0;

    /**
     * The pool, where we store the classes we need.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Get some system and config data during construct.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        // Cache the server memory limit.
        $limit = strtoupper(ini_get('memory_limit'));
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            if ($matches[2] === 'M') {
                // Megabyte.
                $this->serverMemoryLimit = $matches[1] * 1024 * 1024;
            } elseif ($matches[2] === 'K') {
                // Kilobyte.
                $this->serverMemoryLimit = $matches[1] * 1024;
            }
        }

        // Cache some settings.
        $this->maxRuntime = (int) $pool->config->getSetting('maxRuntime');
        $this->minMemoryLeft = (int) $pool->config->getSetting('memoryLeft');
    }

    /**
     * Setter for the enabling of the break.
     *
     * @param $bool
     *  Whether it is enabled, or not.
     */
    public function setEnable($bool)
    {
        $this->enabled = $bool;
    }

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   TRUE = all is OK.
     *   FALSE = we have a problem.
     */
    public function checkEmergencyBreak()
    {
        if (!$this->enabled) {
            // Tell them, everything is OK!
            return true;
        }

        if (self::$allIsOk === false) {
            // This has failed before!
            // No need to check again!
            return false;
        }

        // Check Runtime.
        if ($this->timer + $this->maxRuntime <= time()) {
            // This is taking longer than expected.
            $this->pool->messages->addMessage('Emergency break due to extensive run time!');
            \Krexx::editSettings();
            \Krexx::disable();
            self::$allIsOk = false;
            return false;
        }

        // Still here ? Commence with the memory check.
        // We will only check, if we were able to determine a memory limit
        // in the first place.
        if ($this->serverMemoryLimit > 2) {
            $usage = memory_get_usage();
            $left = $this->serverMemoryLimit - $usage;
            // Is more left than is configured?
            if ($left < $this->minMemoryLeft * 1024 * 1024) {
                $this->pool->messages->addMessage('Emergency break due to extensive memory usage!');
                // Show settings to give the dev to repair the situation.
                \Krexx::editSettings();
                \Krexx::disable();
                self::$allIsOk = false;
                return false;
            }
        }

        // Still here? Everything must be good  :-)
        return true;
    }

    /**
     * Going up one level in the object/array hierarchy.
     */
    public function upOneNestingLevel()
    {
        $this->nestingLevel++;
    }

    /**
     * Going down one level in the object/array hierarchy.
     */
    public function downOneNestingLevel()
    {
        $this->nestingLevel--;
    }

    /**
     * Checks our current nesting level
     *
     * @return bool
     *   TRUE if we are too deep.
     */
    public function checkNesting()
    {
        return ($this->nestingLevel > (int)$this->pool->config->getSetting('level'));
    }

    /**
     * Getter for the nesting level.
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }

    /**
     * Resets the timer.
     *
     * When a certain time has passed, kreXX will use an emergency break to
     * prevent too large output (or no output at all (WSOD)).
     */
    public function resetTimer()
    {
        if (empty($this->timer)) {
            $this->timer = time();
        }
    }

    /**
     * Finds out, if krexx was called too often, to prevent large output.
     *
     * @return bool
     *   Whether kreXX was called too often or not.
     */
    public function checkMaxCall()
    {
        $result = false;
        $maxCall = (int)$this->pool->config->getSetting('maxCall');
        if ($this->krexxCount >= $maxCall) {
            // Called too often, we might get into trouble here!
            $result = true;
        }
        // Give feedback if this is our last call.
        if ($this->krexxCount === $maxCall - 1) {
            $this->pool->messages->addMessage($this->pool->messages->getHelp('maxCallReached'), 'critical');
        }
        $this->krexxCount++;
        return $result;
    }

    /**
     * Getter for the krexxCount.
     *
     * @return int
     *   How often kreXX was called.
     */
    public function getKrexxCount()
    {
        return $this->krexxCount;
    }
}
