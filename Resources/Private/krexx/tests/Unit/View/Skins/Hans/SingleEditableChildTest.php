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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Skins\Hans\Help;
use Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(SingleEditableChild::class, 'renderSingleEditableChild')]
#[CoversMethod(SingleEditableChild::class, 'renderSpecificEditableElement')]
#[CoversMethod(SingleEditableChild::class, 'renderSelectOptions')]
#[CoversMethod(AbstractRender::class, 'encodeJson')]
#[CoversMethod(Help::class, 'renderHelp')]
class SingleEditableChildTest extends AbstractRenderHans
{
    /**
     * Test the rendering of a editable input field.
     */
    public function testRenderSingleEditableChildInput()
    {
        $this->mockModel(static::GET_DOMID, 'nullachtwhatever');
        $this->mockModel(static::GET_NAME, 'myinputvalue');
        $this->mockModel(static::GET_DATA, 'myData');
        $this->mockModel(static::GET_NORMAL, 'myNormal');

        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn('Input');

        // A single input field mus not ask for a skin list.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('getSkinList');
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertStringContainsString('nullachtwhatever', $result);
        $this->assertStringContainsString('myinputvalue', $result);
        $this->assertStringContainsString('myData', $result);
        $this->assertStringContainsString('myNormal', $result);
        $this->assertStringContainsString('<input', $result);
    }

    /**
     * Test the rendering of an editable dropdown field, the skin list
     */
    public function testRenderSingleEditableChildSelectSkin()
    {
        $selectedSkin = 'selectedSkin';
        $this->mockModel(static::GET_DATA, 'more data');
        $this->mockModel(static::GET_NORMAL, 'not normal');

        $this->modelMock->expects($this->exactly(3))
            ->method(static::GET_NAME)
            ->willReturn($selectedSkin);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_DOMID)
            ->willReturn(Fallback::SETTING_SKIN);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn(Fallback::RENDER_TYPE_SELECT);

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSkinList')
            ->willReturn([
                $selectedSkin => $selectedSkin,
                'Herbert' => 'Herbert'
            ]);
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertStringContainsString(Fallback::SETTING_SKIN, $result);
        $this->assertStringContainsString($selectedSkin, $result);
        $this->assertStringContainsString('Herbert', $result);
        $this->assertStringContainsString('more data', $result);
        $this->assertStringContainsString('not normal', $result);
        $this->assertStringContainsString('selected="selected"', $result);
    }

    /**
     * Test the rendering of a simple boolean.
     */
    public function testRenderSingleEditableChildSelectBool()
    {
        $this->mockModel(static::GET_DATA, 'some data');
        $this->mockModel(static::GET_NORMAL, 'totally normal');
        $this->modelMock->expects($this->exactly(3))
            ->method(static::GET_DOMID)
            ->willReturn('barf!');
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn(Fallback::RENDER_TYPE_SELECT);

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertStringContainsString('truetruefalsefalse', $result);
    }

    /**
     * Test the rendering of the language dropdown
     */
    public function testRenderSingleEditableChildSelectLang()
    {
        $this->mockModel(static::GET_DATA, 'some data');
        $this->mockModel(static::GET_NORMAL, 'totally normal');
        $this->modelMock->expects($this->exactly(3))
            ->method(static::GET_DOMID)
            ->willReturn(Fallback::SETTING_LANGUAGE_KEY);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->willReturn(Fallback::RENDER_TYPE_SELECT);

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertStringContainsString('EnglishenDeutschde', $result);
    }
}
