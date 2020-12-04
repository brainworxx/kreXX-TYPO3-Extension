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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\Hans;

use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;

class FatalMainTest extends AbstractRenderHans
{
    /**
     * Test the rendering of the main part of the error handler
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\FatalMain::renderFatalMain
     */
    public function testRenderFatalMain()
    {
        $errorString = 'Dev oops error';
        $inFile = 'deplyoment.php';
        $line = 456;

        $this->fileServiceMock->expects($this->once())
            ->method('readSourcecode')
            ->with($inFile, $line - 1, $line - 6, $line + 4)
            ->will($this->returnValue('faulty code line'));

        $result = $this->renderHans->renderFatalMain($errorString, $inFile, $line);
        $this->assertStringContainsString($errorString, $result);
        $this->assertStringContainsString($inFile, $result);
        $this->assertStringContainsString((string)$line, $result);
        $this->assertStringContainsString('faulty code line', $result);
    }
}
