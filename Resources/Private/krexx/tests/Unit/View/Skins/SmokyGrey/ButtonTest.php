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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderSmokyGrey;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Skins\SmokyGrey\Button;
use Brainworxx\Krexx\View\Skins\SmokyGrey\Help;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Button::class, 'renderButton')]
#[CoversMethod(Help::class, 'renderHelp')]
#[CoversMethod(AbstractRender::class, 'encodeJson')]
class ButtonTest extends AbstractRenderSmokyGrey
{
    /**
     * Test the rendering of a button. Again we test only the additional stuff.
     */
    public function testRenderButton()
    {
        $this->mockModel(static::GET_JSON, ['buttonJson' => 'isFun']);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_NAME)
            ->willReturn('sayMyName');

        $result = $this->renderSmokyGrey->renderButton($this->modelMock);
        $this->assertStringContainsString('sayMyName', $result);
        $this->assertStringContainsString('buttonJson', $result);
        $this->assertStringContainsString('isFun', $result);
    }

    /**
     * Test the rendering of a button, buth without the json.
     */
    public function testRenderButtonWithoutJson()
    {
        $this->mockModel(static::GET_JSON, []);
        $this->modelMock->expects($this->exactly(2))
            ->method(static::GET_NAME)
            ->willReturn('sayMyName');

        $result = $this->renderSmokyGrey->renderButton($this->modelMock);
        $this->assertStringContainsString('sayMyName', $result);
    }
}
