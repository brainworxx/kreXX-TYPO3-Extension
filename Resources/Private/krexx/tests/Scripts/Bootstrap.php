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

namespace Brainworxx\Krexx\Analyse\Caller {

    use Brainworxx\Krexx\Analyse\ConstInterface;

    /**
     * Mocking the debug backtrace in the CallerFinder.
     */
    function debug_backtrace($options, $limit, $mockData = null)
    {
        static $returnValue = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [
                ConstInterface::TRACE_FUNCTION => 'krexx',
                ConstInterface::TRACE_CLASS => 'MockClass',
                ConstInterface::TRACE_FILE => 'mockfile.php',
                ConstInterface::TRACE_LINE => 999
            ]
        ];
        // Update the return data.
        if (is_array($mockData)) {
            $returnValue = $mockData;
        }

        return $returnValue;
    }
}


namespace Brainworxx\Krexx\Service\Config {

    /**
     * Mocking the sapi name, to do something else in a different namespace.
     *
     * @param null|string $what
     *   The return value. kreXX only checks for cli, btw.
     *
     * @return string
     *   The mocked value, to coax kreXX into fileoutput.
     */
    function php_sapi_name($what = null)
    {
        static $result = 'whatever';

        if (!empty($what)) {
            $result = $what;
        }

        return $result;
    }
}

namespace {
   // Register a shutdown method to die, so we get no output on the shell.
    register_shutdown_function (function(){
        die();
    });
}
