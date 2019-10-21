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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderSmokyGrey;

class SingleChildTest extends AbstractRenderSmokyGrey
{
    /**
     * Test the additional stuff done by smoky grey.
     *
     * @covers \Brainworxx\Krexx\View\Skins\SmokyGrey\SingleChild::renderSingleChild
     * @covers \Brainworxx\Krexx\View\AbstractRender::encodeJson
     * @covers \Brainworxx\Krexx\View\Skins\SmokyGrey\Help::renderHelp
     */
    public function testRenderSingleChild()
    {
        $this->mockModel(static::GET_CONNECTOR_LANGUAGE, 'Fortran');
        $this->mockModel(static::GET_JSON, ['Friday' =>'the 12\'th']);

        $codeGenMock = $this->createMock(Codegen::class);
        $codeGenMock->expects($this->once())
            ->method('generateSource')
            ->will($this->returnValue('real, intent(in) :: argument1'));
        $codeGenMock->expects($this->once())
            ->method('getAllowCodegen')
            ->will($this->returnValue(true));
        Krexx::$pool->codegenHandler = $codeGenMock;

        $result = $this->renderSmokyGrey->renderSingleChild($this->modelMock);
        $this->assertContains('Fortran', $result);
        // The \\\\ is the escaping of the escaping.
        // Yo dawg, we heard  . . .
        $this->assertContains('{&#34;Friday&#34;:&#34;the 12\\\\u0022th&#34;}', $result);
    }
}