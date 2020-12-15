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

class RecursionTest extends AbstractRenderHans
{
    /**
     * Test the rendering of a recursion.
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Recursion::renderRecursion
     * @covers \Brainworxx\Krexx\View\Skins\Hans\ConnectorLeft::renderConnectorLeft
     * @covers \Brainworxx\Krexx\View\Skins\Hans\ConnectorRight::renderConnectorRight
     * @covers \Brainworxx\Krexx\View\AbstractRender::generateDataAttribute
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Help::renderHelp
     * @covers \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     * @covers \Brainworxx\Krexx\View\AbstractRender::encodeJson
     */
    public function testRenderRecursion()
    {
        // Prepare the model
        $this->mockModel(static::GET_NAME, 'some name');
        $this->mockModel(static::GET_DOMID, 'the DOM ID');
        $this->mockModel(static::GET_NORMAL, 'normal stuff');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'connector left');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'connector right');
        $this->mockModel(static::GET_JSON, ['Jason', 'and the testonauts']);

        // Run the test.
        $result = $this->renderHans->renderRecursion($this->modelMock);
        $this->assertStringContainsString('some name', $result);
        $this->assertStringContainsString('the DOM ID', $result);
        $this->assertStringContainsString('normal stuff', $result);
        $this->assertStringContainsString('connector left', $result);
        $this->assertStringContainsString('connector right', $result);
        $this->assertStringContainsString('Jason', $result);
        $this->assertStringContainsString('and the testonauts', $result);
    }
}
