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
    register_shutdown_function(function () {
        die();
    });
}

namespace Brainworxx\Krexx\Analyse\Routing\Process {

    use Brainworxx\Krexx\Analyse\ConstInterface;

    /**
     * Mocking the debug backtrace for the backtrace processor.
     */
    function debug_backtrace()
    {
        $data = 'data';
        $someFile = 'some file';
        return [
            [
                ConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'blargh',
                $data => 'Step 1',
            ],
            [
                ConstInterface::TRACE_FILE => $someFile,
                $data => 'Step 2',
            ],
            [
                ConstInterface::TRACE_FILE => $someFile,
                $data => 'Step 3',
            ],
            [
                ConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'whatever',
                $data => 'Step 4',
            ],
        ];
    }
}

namespace Brainworxx\Krexx\Analyse\Routing\Process {

    use function foo\func;

    /**
     * Mocking the class_exist method for the string processing, to have some
     * control over the file info class
     *
     * @param string $classname
     * @param bool $useAutoloader
     * @return bool
     */
    function class_exists(string $classname, bool $useAutoloader = true, bool $startMock = null): bool
    {
        static $activeMocking = false;

        if ($startMock === true) {
            $activeMocking = true;
            return true;
        }

        if ($startMock === false) {
            $activeMocking = false;
            return true;
        }

        if ($activeMocking) {
            return false;
        }

        return \class_exists($classname, $useAutoloader);
    }

    /**
     * Mocking the get_resource_type function.
     *
     * @param $resource
     * @param null $mockResult
     * @return string
     */
    function get_resource_type($resource, $mockResult = null)
    {
        static $result = '';

        if (!is_null($mockResult)) {
            $result = $mockResult;
        }

        return $result;
    }

    /**
     * Mocking the stream_get_meta_data function.
     *
     * @param $resource
     * @param null $mockResult
     * @return array
     */
    function stream_get_meta_data($resource, $mockResult = null)
    {
        static $result = [];

        if (!is_null($mockResult)) {
            $result = $mockResult;
        }

        return $result;
    }

    /**
     * Mocking the curl_getinfo function.
     *
     * @param $resource
     * @param null $mockResult
     * @return null
     */
    function curl_getinfo($resource, $mockResult = null)
    {
        static $result = null;

        if (!is_null($mockResult)) {
            $result = $mockResult;
        }

        return $result;
    }

    /**
     * Mocking a php version.
     *
     * @param $version1
     * @param $version2
     * @param $operator
     * @param null $mockResult
     * @return boolean
     */
    function version_compare($version1, $version2, $operator, $mockResult = null)
    {
        static $result = false;

        if (!is_null($mockResult)) {
            $result = $mockResult;
        }

        return $result;
    }
}
