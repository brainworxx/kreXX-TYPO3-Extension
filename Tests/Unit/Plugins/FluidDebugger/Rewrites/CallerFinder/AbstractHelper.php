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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger\Rewrites\CallerFinder;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper as AbstractKrexxTest;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

abstract class AbstractHelper extends AbstractKrexxTest
{
    /**
     * Create a functioning fluid instance with the provided rendering stack.
     *
     * @param array $renderingStack
     * @param string $classname
     * @return \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid
     */
    protected function createInstance(array $renderingStack, $classname)
    {
        // Mock the view
        $view = $this->createMock(StandaloneView::class);
        $renderingStackRefMock = $this->createMock(\ReflectionProperty::class);
        // Mock the property reflection of the rendering context.
        $renderingStackRefMock->expects($this->once())
            ->method('setAccessible')
            ->with(true);
        $renderingStackRefMock->expects($this->once())
            ->method('getValue')
            ->willReturn($renderingStack);
        // Mock the reflection of the view
        $viewReflection = $this->createMock(\ReflectionClass::class);
        $viewReflection->expects($this->once())
            ->method('hasProperty')
            ->with('renderingStack')
            ->willReturn(true);
        $viewReflection->expects($this->once())
            ->method('getProperty')
            ->with('renderingStack')
            ->willReturn($renderingStackRefMock);

        // Mock the rendering context
        $renderingContext = $this->createMock(RenderingContext::class);

        Krexx::$pool->registry->set('view', $view);
        Krexx::$pool->registry->set('viewReflection', $viewReflection);
        Krexx::$pool->registry->set('renderingContext', $renderingContext);

        return new $classname(Krexx::$pool);
    }
}