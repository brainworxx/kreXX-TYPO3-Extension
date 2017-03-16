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

namespace Brainworxx\Krexx\Service\View;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Help texts.
 *
 * @package Brainworxx\Krexx\Service\View
 */
class Help
{

    // A simple array to hold the values.
    // There should not be any string collisions.
    protected $helpArray = array();

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool a,d reads the language file.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $file = $pool->krexxDir . 'resources' . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'Help.ini';
        $fileService = $pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');
        $this->helpArray = (array)parse_ini_string(
            $fileService->getFileContents($file)
        );
    }

    /**
     * Returns the help text when found, otherwise returns an empty string.
     *
     * @param string $what
     *   The help ID from the array above.
     *
     * @return string
     *   The help text.
     */
    public function getHelp($what)
    {
        $result = '';
        if (isset($this->helpArray[$what])) {
            $result = $this->helpArray[$what];
        }
        return $result;
    }
}
