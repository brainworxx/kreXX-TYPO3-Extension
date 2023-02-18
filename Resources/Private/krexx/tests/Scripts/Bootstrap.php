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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

define('KREXX_TEST_IN_PROGRESS', true);

// Make sure, that we are able to mock the living hell out of this baby.
$analyseRoutingProcess = '\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\';
$serviceFlow = '\\Brainworxx\\Krexx\\Service\\Flow\\';
$serviceMisc = '\\Brainworxx\\Krexx\\Service\\Misc\\';
$viewOutput = '\\Brainworxx\\Krexx\\View\\Output\\';
$view = '\\Brainworxx\\Krexx\\View\\';
$callbackScalar = '\\Brainworxx\\Krexx\\Analyse\\Scalar\\String\\';
$caller = '\\Brainworxx\\Krexx\\Analyse\\Caller';

AbstractTest::defineFunctionMock($analyseRoutingProcess, 'class_exists');
AbstractTest::defineFunctionMock($analyseRoutingProcess, 'is_object');
AbstractTest::defineFunctionMock($serviceFlow, 'ini_get');
AbstractTest::defineFunctionMock($serviceFlow, 'time');
AbstractTest::defineFunctionMock($serviceFlow, 'memory_get_usage');
AbstractTest::defineFunctionMock($serviceFlow, 'php_sapi_name');
AbstractTest::defineFunctionMock($serviceMisc, 'file_put_contents');
AbstractTest::defineFunctionMock($serviceMisc, 'unlink');
AbstractTest::defineFunctionMock($serviceMisc, 'is_file');
AbstractTest::defineFunctionMock($serviceMisc, 'is_readable');
AbstractTest::defineFunctionMock($serviceMisc, 'filemtime');
AbstractTest::defineFunctionMock($serviceMisc, 'mb_strlen');
AbstractTest::defineFunctionMock($serviceMisc, 'glob');
AbstractTest::defineFunctionMock($serviceMisc, 'time');
AbstractTest::defineFunctionMock($callbackScalar, 'class_exists');
AbstractTest::defineFunctionMock($callbackScalar, 'is_file');
AbstractTest::defineFunctionMock($callbackScalar, 'function_exists');
AbstractTest::defineFunctionMock($callbackScalar, 'realpath');
AbstractTest::defineFunctionMock($viewOutput, 'register_shutdown_function');
AbstractTest::defineFunctionMock($viewOutput, 'microtime');
AbstractTest::defineFunctionMock($viewOutput, 'glob');
AbstractTest::defineFunctionMock($view, 'php_sapi_name');
AbstractTest::defineFunctionMock($view, 'defined');
AbstractTest::defineFunctionMock($caller, 'time');

// Register a shutdown method to die, so we get no output on the shell.
register_shutdown_function(function () {
    die();
});
