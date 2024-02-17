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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;

class HeaderTest extends AbstractRenderHans
{
    /**
     * Test the rendering of the kreXX header.
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Header::renderHeader
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Search::renderSearch
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Messages::renderMessages
     */
    public function testRenderHeader()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getKrexxCount')
            ->will($this->returnValue(42));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $recursionMock = $this->createMock(Recursion::class);
        // Two times fro msearch and header itself.
        $recursionMock->expects($this->exactly(2))
            ->method('getMarker')
            ->will($this->returnValue('recursion Marker'));
        Krexx::$pool->recursionHandler = $recursionMock;

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('outputMessages')
            ->will($this->returnValue('mess ages'));
        Krexx::$pool->messages = $messageMock;

        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->once())
            ->method('getOfficialEncoding')
            ->will($this->returnValue('encoding'));
        krexx::$pool->chunks = $chunkMock;

        // Run the test.
        $result = $this->renderHans->renderHeader('Headliner', 'CSS Wanne Eickel');
        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString('recursion Marker', $result);
        $this->assertStringContainsString('mess ages', $result);
        $this->assertStringContainsString('encoding', $result);
        $this->assertStringContainsString('Headliner', $result);
        $this->assertStringContainsString('CSS Wanne Eickel', $result);
    }
}
