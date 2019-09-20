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

use phpmock\phpunit\PHPMock;

define('KREXX_TEST_IN_PROGRESS', true);

// Make sure, that we are able to mock the living hell out of this baby.
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Factory\\', 'is_writable');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'ini_get');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'time');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'memory_get_usage');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'file_put_contents');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'unlink');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'is_file');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'is_readable');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'filemtime');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'mb_strlen');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'glob');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'time');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'register_shutdown_function');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'microtime');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'glob');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\View', 'php_sapi_name');
PHPMock::defineFunctionMock('\\Brainworxx\\Krexx\\View', 'defined');

// Register a shutdown method to die, so we get no output on the shell.
register_shutdown_function(function () {
    die();
});
