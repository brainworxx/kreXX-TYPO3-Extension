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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

use phpmock\phpunit\PHPMock;

define('KREXX_TEST_IN_PROGRESS', true);

// Make sure, that we are able to mock the living hell out of this baby.
$analyseRoutingProcess = '\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\';
$serviceFlow = '\\Brainworxx\\Krexx\\Service\\Flow\\';
$serviceMisc = '\\Brainworxx\\Krexx\\Service\\Misc\\';
$viewOutput = '\\Brainworxx\\Krexx\\View\\Output\\';
$view = '\\Brainworxx\\Krexx\\View\\';
$callbackScalar = '\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Scalar\\';
$caller = '\\Brainworxx\\Krexx\\Analyse\\Caller';

PHPMock::defineFunctionMock($analyseRoutingProcess, 'class_exists');
PHPMock::defineFunctionMock($serviceFlow, 'ini_get');
PHPMock::defineFunctionMock($serviceFlow, 'time');
PHPMock::defineFunctionMock($serviceFlow, 'memory_get_usage');
PHPMock::defineFunctionMock($serviceFlow, 'php_sapi_name');
PHPMock::defineFunctionMock($serviceMisc, 'file_put_contents');
PHPMock::defineFunctionMock($serviceMisc, 'unlink');
PHPMock::defineFunctionMock($serviceMisc, 'is_file');
PHPMock::defineFunctionMock($serviceMisc, 'is_readable');
PHPMock::defineFunctionMock($serviceMisc, 'filemtime');
PHPMock::defineFunctionMock($serviceMisc, 'mb_strlen');
PHPMock::defineFunctionMock($serviceMisc, 'glob');
PHPMock::defineFunctionMock($serviceMisc, 'time');
PHPMock::defineFunctionMock($callbackScalar, 'class_exists');
PHPMock::defineFunctionMock($callbackScalar, 'is_file');
PHPMock::defineFunctionMock($callbackScalar, 'function_exists');
PHPMock::defineFunctionMock($viewOutput, 'register_shutdown_function');
PHPMock::defineFunctionMock($viewOutput, 'microtime');
PHPMock::defineFunctionMock($viewOutput, 'glob');
PHPMock::defineFunctionMock($view, 'php_sapi_name');
PHPMock::defineFunctionMock($view, 'defined');
PHPMock::defineFunctionMock($caller, 'time');

// Register a shutdown method to die, so we get no output on the shell.
register_shutdown_function(function () {
    die();
});
