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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * "Controller" for the timer "actions".
 *
 * @package Brainworxx\Krexx\Controller
 */
class TimerController extends AbstractController
{
    /**
     * Here we save all timekeeping stuff.
     *
     * @var array
     */
    protected static $timekeeping = [];

    /**
     * More timekeeping stuff.
     *
     * @var array
     */
    protected static $counterCache = [];

    /**
     * We simply set the pool. We will not register any shutdown stuff.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
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
        $miniBench = $this->miniBenchTo(static::$timekeeping);
        $this->pool->createClass(DumpController::class)
            ->dumpAction($miniBench, 'kreXX timer');
        // Reset the timer vars.
        static::$timekeeping = [];
        static::$counterCache = [];

        return $this;
    }

    /**
     * The benchmark main function.
     *
     * @param array $timeKeeping
     *   The timekeeping array.
     *
     * @return array
     *   The benchmark array.
     *
     * @see http://php.net/manual/de/function.microtime.php
     * @author gomodo at free dot fr
     */
    protected function miniBenchTo(array $timeKeeping)
    {
        // Get the very first key.
        $start = key($timeKeeping);
        $totalTime = round((end($timeKeeping) - $timeKeeping[$start]) * 1000, 4);
        $result['url'] = $this->getCurrentUrl();
        $result['total_time'] = $totalTime;
        $prevMomentName = $start;
        $prevMomentStart = $timeKeeping[$start];

        foreach ($timeKeeping as $moment => $time) {
            if ($moment !== $start) {
                // Calculate the time.
                $percentageTime = round(((round(($time - $prevMomentStart) * 1000, 4) / $totalTime) * 100), 1);
                $result[$prevMomentName . '->' . $moment] = $percentageTime . '%';
                $prevMomentStart = $time;
                $prevMomentName = $moment;
            }
        }

        return $result;
    }

    /**
     * Return the current URL.
     *
     * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
     * @author Timo Huovinen
     *
     * @return string
     *   The current URL.
     */
    protected function getCurrentUrl()
    {
        $server = $this->pool->getServer();

        // Check if someone has been messing with the $_SERVER, to prevent
        // warnings and notices.
        if (empty($server) === true ||
            empty($server['SERVER_PROTOCOL']) === true ||
            empty($server['SERVER_PORT']) === true ||
            empty($server['SERVER_NAME']) === true ||
            empty($server['REQUEST_URI']) === true
        ) {
            return 'n/a';
        }

        // SSL or no SSL.
        $ssl = (!empty($server['HTTPS']) && $server['HTTPS'] === 'on');

        $protocol = strtolower($server['SERVER_PROTOCOL']);
        $protocol = substr($protocol, 0, strpos($protocol, '/'));
        if ($ssl === true) {
            $protocol .= 's';
        }

        $port = $server['SERVER_PORT'];

        if (($ssl === false && $port === '80') || ($ssl === true && $port === '443')) {
            // Normal combo with port and protocol.
            $port = '';
        } else {
            // We have a special port here.
            $port = ':' . $port;
        }

        if (isset($server['HTTP_HOST']) === true) {
            $host = $server['HTTP_HOST'];
        } else {
            $host = $server['SERVER_NAME'] . $port;
        }

        return $this->pool->encodingService->encodeString($protocol . '://' . $host . $server['REQUEST_URI']);
    }
}
