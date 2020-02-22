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

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;

class SingleChildTest extends AbstractRenderHans
{
    /**
     * Single child rendering testing.
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleChild::renderSingleChild
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleChild::renderExtra
     * @covers \Brainworxx\Krexx\View\AbstractRender::retrieveTypeClasses
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleChild::renderCallable
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleChild::renderSourceButton
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Help::renderHelp
     * @covers \Brainworxx\Krexx\View\AbstractRender::generateDataAttribute
     */
    public function testRenderSingleChild()
    {
        $this->mockModel(static::GET_DATA, 'extra data');
        $this->mockModel(static::GET_IS_CALLBACK, true);
        $this->mockModel(static::GET_NAME, 'my name');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'lefty');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'righty');
        $this->mockModel(static::GET_JSON, ['someKey', 'informative text']);

        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_HAS_EXTRAS)
            ->will($this->returnValue(true));
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->will($this->returnValue('type01 type02'));
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_NORMAL)
            ->will($this->returnValue('just normal'));

        $codeGenMock = $this->createMock(Codegen::class);
        $codeGenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->will($this->returnValue('generated code'));
        $codeGenMock->expects($this->once())
            ->method('getAllowCodegen')
            ->will($this->returnValue(true));
        $codeGenMock->expects($this->once())
            ->method('generateWrapperLeft')
            ->will($this->returnValue(''));
        $codeGenMock->expects($this->once())
            ->method('generateWrapperRight')
            ->will($this->returnValue(''));
        Krexx::$pool->codegenHandler = $codeGenMock;

        $result = $this->renderHans->renderSingleChild($this->modelMock);
        $this->assertContains('extra data', $result);
        $this->assertContains('type01', $result);
        $this->assertContains('type02', $result);
        $this->assertContains('my name', $result);
        $this->assertContains('just normal', $result);
        $this->assertContains('lefty', $result);
        $this->assertContains('righty', $result);
        $this->assertContains('someKey', $result);
        $this->assertContains('informative text', $result);
        $this->assertContains('generated code', $result);
    }
}
