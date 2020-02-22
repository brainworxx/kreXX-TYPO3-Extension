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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;

class SingleEditableChildTest extends AbstractRenderHans
{
    /**
     * Test the rendering of a editable input field.
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild::renderSingleEditableChild
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild::renderSpecificEditableElement
     */
    public function testRenderSingleEditableChildInput()
    {
        $this->mockModel(static::GET_DOMID, 'nullachtwhatever');
        $this->mockModel(static::GET_NAME, 'myinputvalue');
        $this->mockModel(static::GET_DATA, 'myData');
        $this->mockModel(static::GET_NORMAL, 'myNormal');

        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->will($this->returnValue('Input'));

        // A single input field mus not ask for a skin list.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('getSkinList');
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertContains('nullachtwhatever', $result);
        $this->assertContains('myinputvalue', $result);
        $this->assertContains('myData', $result);
        $this->assertContains('myNormal', $result);
        $this->assertContains('<input', $result);
    }

    /**
     * Test the rendering of a editable dropdown field., the skin list
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild::renderSingleEditableChild
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild::renderSpecificEditableElement
     * @covers \Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild::renderSelectOptions
     */
    public function testRenderSingleEditableChildSelect()
    {
        $selectedSkin = 'selectedSkin';
        $this->mockModel(static::GET_DATA, 'more data');
        $this->mockModel(static::GET_NORMAL, 'not normal');

        $this->modelMock->expects($this->exactly(3))
            ->method(static::GET_NAME)
            ->will($this->returnValue($selectedSkin));
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_DOMID)
            ->will($this->returnValue(Fallback::SETTING_SKIN));
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_TYPE)
            ->will($this->returnValue(Fallback::RENDER_TYPE_SELECT));

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSkinList')
            ->will($this->returnValue([
                $selectedSkin,
                'Herbert'
            ]));
        Krexx::$pool->config = $configMock;

        $result = $this->renderHans->renderSingleEditableChild($this->modelMock);
        $this->assertContains(Fallback::SETTING_SKIN, $result);
        $this->assertContains($selectedSkin, $result);
        $this->assertContains('Herbert', $result);
        $this->assertContains('more data', $result);
        $this->assertContains('not normal', $result);
        $this->assertContains('selected="selected"', $result);
    }
}
