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

namespace Brainworxx\Krexx\View\Output;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Check the kind of output.
 */
class CheckOutput
{
    /**
     * Array key in the global $_SERVER array.
     *
     * @var string
     */
    protected const REMOTE_ADDRESS = 'REMOTE_ADDR';

    /**
     * Here we store all relevant data.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Check for an ajax request.
     *
     * Appending stuff after an ajax request will most likely cause a js error.
     * But there are moments when you actually want to do this.
     *
     * @return bool
     *   Well? Is it an ajax request?
     */
    public function isAjax(): bool
    {
        $server = $this->pool->getServer();

        return isset($server['HTTP_X_REQUESTED_WITH']) === true &&
            strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check for a cli request, simple wrapper around php_sapi_name.
     *
     * @return bool
     *   Well? Is it an cli request?
     */
    public function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Test if we did output any HTML so far.
     *
     * @return bool
     *   Well? Is did we output HTML so far?
     */
    public function isOutputHtml(): bool
    {
        // When we have dispatched a PDF or Json, the browser will not be
        // able to render the HTML output correctly.
        foreach (headers_list() as $header) {
            $header = strtolower($header);
            if (
                strpos($header, 'content-type') !== false &&
                strpos($header, 'html') === false
            ) {
                // We do have none html content type.
                return false;
            }
        }

        // Found nothing, must be HTML.
        // And if nothing was sent at this point, it is now HTML.
        return true;
    }

    /**
     * Checks if the current client ip is allowed.
     *
     * @author Chin Leung
     * @see https://stackoverflow.com/questions/35559119/php-ip-address-whitelist-with-wildcards
     *
     * @param string $whitelist
     *   The ip whitelist.
     *
     * @return bool
     *   Whether the current client ip is allowed or not.
     */
    public function isAllowedIp(string $whitelist): bool
    {
        $server = $this->pool->getServer();
        $remote = isset($server[static::REMOTE_ADDRESS]) === true ? (string) $server[static::REMOTE_ADDRESS] : null;
        $ipList = array_map('trim', explode(',', $whitelist));
        if (
            // There is no IP on the shell.
            $this->isCli() === true
            // Or we allow everyone.
            || $whitelist === '*'
            // Or the IPs are matching.
            || in_array($remote, $ipList, true) === true
        ) {
            return true;
        }

        if ($remote === null) {
            // Messed up server array.
            return false;
        }

        return $this->checkWildcards($ipList, $remote);
    }

    /**
     * Check the wildcards.
     *
     * @param string[] $ipList
     *   The list of allowed ips
     * @param string $remote
     *   The remote address, according to the server array.
     * @return bool
     */
    protected function checkWildcards(array $ipList, string $remote): bool
    {
        foreach ($ipList as $ip) {
            $wildcardPos = strpos($ip, '*');
            // Check if the ip has a wildcard.
            if ($wildcardPos !== false && substr($remote, 0, $wildcardPos) . '*' === $ip) {
                return true;
            }
        }

        return false;
    }
}
