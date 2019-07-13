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

namespace {
    define('KREXX_TEST_IN_PROGRESS', true);
}

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

namespace Brainworxx\Krexx\Controller {

    /**
     * Short circuting the krexx command in the fatal error handler.
     */
    function krexx()
    {
        // Do nothing.
    }

    /**
     * Mocking the microtime in the time controller.
     *
     * @return int
     */
    function microtime($get_as_float = null, $mockResult = null)
    {
        static $startMock = false;

        if ($mockResult !== null) {
            $startMock = $mockResult;
        }

        if ($startMock === true) {
            return 3000;
        }

        return \microtime($get_as_float);
    }

    /**
     * Mocking the setting of an exception handler.
     *
     * @param array $callback
     *
     * @return array
     */
    function set_exception_handler(array $callback): array
    {
        static $lastCallback = [];

        if (!empty($callback)) {
            $lastCallback = $callback;
        }

        return $lastCallback;
    }

    /**
     * Mocking the restoring of an exception handler.
     *
     * @return int
     *   Count of it's call.
     */
    function restore_exception_handler(): int
    {
        static $counter = 0;

        ++$counter;

        return $counter;
    }
}

namespace Brainworxx\Krexx\Service\Config {

    /**
     * Mocking the max executon time settings for the validation class.
     *
     * @param string $what
     * @return string
     */
    function ini_get(string $what): string
    {
        if ($what === 'max_execution_time') {
            return '123';
        }

        return \ini_get($what);
    }
}

namespace Brainworxx\Krexx\Service\Factory {


    /**
     * Mocking the is_writable method, to simulate inaccessible folders.
     *
     * @param string $what
     * @param bool|null $overwriteResult
     * @return bool
     */
    function is_writable(string $what = '', bool $overwriteResult = null): bool
    {
        static $result = [];

        // Reset the result.
        if (empty($what)) {
            $result = [];
            return true;
        }

        // Overwrite the return value.
        if (is_bool($overwriteResult)) {
            $result[$what] = $overwriteResult;
            return $result[$what];
        }

        // Use the overwrite value once
        if (isset($result[$what])) {
            return $result[$what];
        }

        return \is_writable($what);
    }
}

namespace Brainworxx\Krexx\Service\Flow {

    /**
     * Mocking the server php memory in the emergency helper
     *
     * @param string $what
     *   The value we want to retrieve.
     * @param bool|null $start
     *   Start or end the mocking process.
     * @param string $value
     *   The desired return value.
     *
     * @return string
     *   The retrieved value.
     */
    function ini_get(string $what, bool $start = null, string $value = ''): string
    {
        static $result = null;

        if ($start === true) {
            $result = $value;
        }

        if ($start === false) {
            $result = null;
        }

        if (empty($result)) {
            return \ini_get($what);
        } else {
            return $result;
        }
    }

    /**
     * Mocking the current memory limit.
     *
     * @param bool|null $start
     *   Start or end the mocking process.
     * @param int $value
     *   The desired return value.
     *
     * @return int
     *   The retrieved value.
     */
    function memory_get_usage(bool $start = null, int $value = 0): int
    {
        static $result = null;

        if ($start === true) {
            $result = $value;
        }

        if ($start === false) {
            $result = null;
        }

        if (empty($result)) {
            return \memory_get_usage();
        } else {
            return $result;
        }
    }

    /**
     * Mocking the time function for the emergency break.
     *
     * @param bool|null $start
     *   Start or end the mocking process.
     * @param int $value
     *   The desired return value.
     *
     * @return int
     *   The retrieved value.
     */
    function time(bool $start = null, int $value = 0): int
    {
        static $result = null;

        if ($start === true) {
            $result = $value;
        }

        if ($start === false) {
            $result = null;
        }

        if (empty($result)) {
            return \time();
        } else {
            return $result;
        }
    }
}
namespace Brainworxx\Krexx\Service\Misc {

    /**
     * Mocking the file_put_contents for the file service test.
     *
     * @param $filename
     * @param $data
     * @param int $flags
     * @param null $context
     * @param bool $startMock
     *
     * @return int|bool
     */
    function file_put_contents(string $filename, string $data, int $flags = 0, $context = null, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
            return false;
        }

        if ($mockingInProgress === true) {
            return 42;
        }

        return \file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * Mocking the file unlinking. We also store called parameters and return
     * them when we are done mocking.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return array|bool
     */
    function unlink(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;
        // Remembering the parameters right here.
        static $parameters = [];

        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
                return true;
            }

            if ($startMock === false) {
                return $parameters;
            }
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \unlink($filename);
    }

    /**
     * Simply mocking the chmod function.
     *
     * @param string $filename
     * @param $mode
     * @param bool|null $startMock
     *
     * @return array|bool
     */
    function chmod(string $filename, $mode, bool $startMock = null)
    {
        static $mockingInProgress = false;
        // Remembering the parameters right here.
        static $parameters = [];

        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
            }

            if ($startMock === false) {
                return $parameters;
            }

            return true;
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \chmod($filename, $mode);
    }

    /**
     * Mocking the realpath.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return bool|string
     */
    function realpath(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return $filename;
        }

        return \realpath($filename);
    }

    /**
     * Mocking the is_file.
     *
     * @param string $filename
     * @param bool|null $startMock
     * @return bool|string
     */
    function is_file(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return true;
        }

        return \is_file($filename);
    }

    /**
     * Mocking the ir_readable function.
     *
     * @param string $filename
     * @param bool|null $startMock
     * @return bool|array
     */
    function is_readable(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;
        static $parameters = [];


        if ($startMock !== null) {
            $mockingInProgress = $startMock;

            if ($startMock === true) {
                $parameters = [];
                return true;
            }

            if ($startMock === false) {
                return $parameters;
            }
        }

        if ($mockingInProgress === true) {
            $parameters[] = $filename;
            return true;
        }

        return \is_readable($filename);
    }

    /**
     * Mocking the file time.
     *
     * @param string $filename
     * @param bool|null $startMock
     *
     * @return bool|false|int
     */
    function filemtime(string $filename, bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return 42;
        }

        return \filemtime($filename);
    }

    /**
     * Mocking the time.
     *
     * Take that, time!
     *
     * @param bool|null $startMock
     *
     * @return int
     */
    function time(bool $startMock = null)
    {
        static $mockingInProgress = false;

        if ($startMock !== null) {
            $mockingInProgress = $startMock;
        }

        if ($mockingInProgress === true) {
            return 41;
        }

        return \time();
    }
}