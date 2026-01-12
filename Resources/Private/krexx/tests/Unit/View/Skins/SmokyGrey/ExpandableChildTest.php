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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderSmokyGrey;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Skins\SmokyGrey\ConnectorRight;
use Brainworxx\Krexx\View\Skins\SmokyGrey\ExpandableChild;
use Brainworxx\Krexx\View\Skins\SmokyGrey\Help;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ExpandableChild::class, 'renderExpandableChild')]
#[CoversMethod(ExpandableChild::class, 'renderSourceButtonSg')]
#[CoversMethod(ConnectorRight::class, 'renderConnectorRight')]
#[CoversMethod(Help::class, 'renderHelp')]
#[CoversMethod(AbstractRender::class, 'retrieveTypeClasses')]
#[CoversMethod(AbstractRender::class, 'encodeJson')]
class ExpandableChildTest extends AbstractRenderSmokyGrey
{
    /**
     * Test the rendering of an expandable child.
     */
    public function testRenderExpandableChild()
    {
        $this->mockModel(static::GET_NAME, 'Model name');
        $this->mockModel(static::GET_CONNECTOR_LANGUAGE, 'Turbo Pasquale');
        $this->mockModel(static::GET_NORMAL, 'I am not');
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'he who must not be pampered');
        $this->mockModel(static::GET_JSON, ['Voldemort' => 'noNose.']);
        $this->mockModel(static::GET_DOMID, 'passport');
        $this->mockModel(static::RENDER_ME, 'birdnest');

        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn('my type');

        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('generateSource')
            ->with($this->modelMock)
            ->willReturn('some meaningful code');
        $codegenMock->expects($this->once())
            ->method('generateWrapperLeft')
            ->willReturn('');
        $codegenMock->expects($this->once())
            ->method('generateWrapperRight')
            ->willReturn('');
        $codegenMock->expects($this->once())
            ->method('isCodegenAllowed')
            ->willReturn(true);
        Krexx::$pool->codegenHandler = $codegenMock;

        $result = $this->renderSmokyGrey->renderExpandableChild($this->modelMock);
        $this->assertStringContainsString('Model name', $result);
        $this->assertStringContainsString('my', $result);
        $this->assertStringContainsString('type', $result);
        $this->assertStringContainsString('Turbo Pasquale', $result);
        $this->assertStringContainsString('I am not', $result);
        $this->assertStringContainsString('he who must not be pampered', $result);
        $this->assertStringContainsString('noNose.', $result);
        $this->assertStringContainsString('passport', $result);
        $this->assertStringContainsString('birdnest', $result);
    }

    /**
     * Test the rendering of an expandable child, but with a small connector.
     */
    public function testRenderExpandableChildSmallConnector()
    {
        $this->mockModel(static::GET_CONNECTOR_RIGHT, 'xx');
        $this->mockModel(static::GET_RETURN_TYPE, '');
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $result = $this->renderSmokyGrey->renderExpandableChild($this->modelMock);
        $this->assertStringNotContainsString('xx', $result, 'We do not render small connectors.');
    }

    public function testRenderExpandableChildEmergency()
    {
        $modelMock = $this->createMock(Model::class);
        $modelMock->expects($this->never())
            ->method('getName');
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $result = $this->renderSmokyGrey->renderExpandableChild($modelMock);
        $this->assertEquals('', $result, 'An emergency break must not create any output.');
    }
}
