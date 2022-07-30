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

namespace Brainworxx\Krexx\Service\Config\From;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Get the configuration from the cookies.
 */
class Cookie
{
    /**
     * Our security handler.
     *
     * @var \Brainworxx\Krexx\Service\Config\Validation
     */
    protected $validation;

    /**
     * Here we cache our cookie settings.
     *
     * @var array
     */
    public $settings = [];

    /**
     * Inject the pool, and get a first impression of the cookies.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->validation = $pool->config->validation;
        $cookies = $pool->getGlobals('_COOKIE');

        if (isset($cookies['KrexxDebugSettings']) === true) {
            // We have local settings.
            $settings = json_decode($cookies['KrexxDebugSettings'], true);
            if (is_array($settings) === true) {
                $this->settings = $settings;
            }
        }
    }

    /**
     * Returns settings from the local cookies.
     *
     * @param string $group
     *   The name of the group inside the cookie.
     * @param string $name
     *   The name of the value.
     *
     * @return string|null
     *   The value.
     */
    public function getConfigFromCookies(string $group, string $name)
    {
        // Do we have a value in the cookies?
        if (
            isset($this->settings[$name]) === true &&
            $this->validation->evaluateSetting($group, $name, $this->settings[$name]) === true
        ) {
            // We escape them, just in case.
            return htmlspecialchars($this->settings[$name]);
        }

        // Still here?
        return null;
    }
}
