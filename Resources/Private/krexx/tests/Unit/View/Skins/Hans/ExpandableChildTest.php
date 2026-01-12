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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Skins\Hans\ConnectorRight;
use Brainworxx\Krexx\View\Skins\Hans\ExpandableChild;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ExpandableChild::class, 'renderExpandableChild')]
#[CoversMethod(ExpandableChild::class, 'renderSourceButtonWithStop')]
#[CoversMethod(ExpandableChild::class, 'renderNest')]
#[CoversMethod(ExpandableChild::class, 'renderExtra')]
#[CoversMethod(AbstractRender::class, 'retrieveTypeClasses')]
#[CoversMethod(AbstractRender::class, 'encodeJson')]
#[CoversMethod(ConnectorRight::class, 'renderConnectorRight')]
class ExpandableChildTest extends AbstractRenderHans
{
    /**
     * Test the rendering of an expandable child.
     *
     * On hindsight, these names are just silly. Then again, we do have a skin
     * with the name 'Hans'.
     */
    public function testRenderExpandableChild()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $this->mockModel(static::GET_NAME, 'another name');
        $this->mockModel(static::GET_NORMAL, 'not normal');
        $this->mockModel(static::GET_CONNECTOR_LEFT, 'some conn');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'any conn');
        $this->mockModel(static::RENDER_ME, 'model html');
        $this->mockModel(static::GET_DOMID, 'x12345');
        $this->mockModel(static::GET_HAS_EXTRAS, true);
        $this->mockModel(static::GET_DATA, 'eXXtra');

        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn('Stringh In-Tee-Ger');

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->willReturn('generated source');
        $codegenMock->expects($this->once())
            ->method('isCodegenAllowed')
            ->willReturn(true);
        Krexx::$pool->codegenHandler = $codegenMock;

        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->once())
            ->method('chunkMe')
            ->with($this->anything())
            ->willReturnArgument(0);
        Krexx::$pool->chunks = $chunkMock;

        $result = $this->renderHans->renderExpandableChild($this->modelMock, true);
        $this->assertStringContainsString('Stringh', $result);
        $this->assertStringContainsString('In-Tee-Ger', $result);
        $this->assertStringContainsString('another name', $result);
        $this->assertStringContainsString('not normal', $result);
        $this->assertStringContainsString('some conn', $result);
        $this->assertStringContainsString('any conn', $result);
        $this->assertStringContainsString('generated source', $result);
        // Stuff from the nest.
        $this->assertStringContainsString('model html', $result);
        $this->assertStringContainsString('x12345', $result);
        $this->assertStringNotContainsString('khidden', $result);
    }

    /**
     * Test everything with an active emergency break.
     */
    public function testRenderExpandableChildEmergency()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $model = new Model(Krexx::$pool);

        $result = $this->renderHans->renderExpandableChild($model, true);
        $this->assertEquals('', $result, 'There should be nothing in there.');
    }

    /**
     * Test the rendering without any connectors on the right.
     */
    public function testRenderExpandableChildNoConnector()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $this->mockModel(static::GET_CONNECTOR_RIGHT, '');
        $this->mockModel(static::GET_RETURN_TYPE, '');
        $result = $this->renderHans->renderExpandableChild($this->modelMock, true);

        $this->assertStringContainsString(
            'data-codewrapperRight=""',
            $result,
            'No connector for you!'
        );
    }

    /**
     * Test it with a source code button and expanded.
     */
    public function testRenderExpandableChildCollapsed()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $model = new Model(Krexx::$pool);

        $result = $this->renderHans->renderExpandableChild($model);
        $this->assertStringContainsString('khidden', $result, 'The collapsed CSS class.');
    }
}
