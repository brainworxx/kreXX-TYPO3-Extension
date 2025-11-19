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

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\Fluid;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractFluid::class, '__construct')]
#[CoversMethod(AbstractFluid::class, 'assignParsedTemplateRenderType')]
class AbstractCallerFinderTest extends AbstractHelper
{
    public const PARSED_TEMPLATE = 'parsedTemplate';
    protected const HAS_PROPERTY = 'hasProperty';
    protected const RENDERING_STACK = 'renderingStack';
    protected const VIEW_REFLECTION = 'viewReflection';
    protected const RENDERING_CONTEXT = 'renderingContext';
    protected const ERROR = 'error';

    /**
     * Test the retrieval of all necessary objects from the ViewHelper.
     */
    public function testConstructNormal()
    {
        $renderingStack = [[static::PARSED_TEMPLATE => new \StdClass(), 'type' => 5]];

        // Mock the view
        $viewMock = $this->createMock(StandaloneView::class);
        $renderingStackRefMock = $this->createMock(\ReflectionProperty::class);
        // Mock the property reflection of the rendering context.
        $renderingStackRefMock->expects($this->any())
            ->method('setAccessible')
            ->with(true);
        $renderingStackRefMock->expects($this->once())
            ->method('getValue')
            ->willReturn($renderingStack);
        // Mock the reflection of the view
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->expects($this->once())
            ->method(static::HAS_PROPERTY)
            ->with(static::RENDERING_STACK)
            ->willReturn(true);
        $reflectionMock->expects($this->once())
            ->method('getProperty')
            ->with(static::RENDERING_STACK)
            ->willReturn($renderingStackRefMock);

        // Mock the rendering context
        $contextMock = $this->createMock(RenderingContext::class);

        Krexx::$pool->registry->set('view', $viewMock);
        Krexx::$pool->registry->set(static::VIEW_REFLECTION, $reflectionMock);
        Krexx::$pool->registry->set(static::RENDERING_CONTEXT, $contextMock);

        $newFluid = new Fluid(Krexx::$pool);

        // Check the injections from above.
        $this->assertEquals('fluidvar', $this->retrieveValueByReflection('varname', $newFluid));
        $this->assertSame($viewMock, $this->retrieveValueByReflection('view', $newFluid));
        $this->assertSame($reflectionMock, $this->retrieveValueByReflection(static::VIEW_REFLECTION, $newFluid));
        $this->assertSame($contextMock, $this->retrieveValueByReflection(static::RENDERING_CONTEXT, $newFluid));
        $this->assertSame(
            $this->retrieveValueByReflection(static::PARSED_TEMPLATE, $newFluid),
            $renderingStack[0][static::PARSED_TEMPLATE]
        );
        $this->assertEquals(5, $this->retrieveValueByReflection('renderingType', $newFluid));
        $this->assertFalse($this->retrieveValueByReflection(static::ERROR, $newFluid));
    }

    /**
     * Test the error handling during construct.
     */
    public function testConstructError()
    {
        $viewMock = 'do not look at me';
        $contextMock = $this->createMock(RenderingContextInterface::class);
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->expects($this->once())
            ->method(static::HAS_PROPERTY)
            ->with(static::RENDERING_STACK)
            ->willReturn(true);
        $reflectionMock->expects($this->once())
            ->method('getProperty')
            ->with(static::RENDERING_STACK)
            ->will($this->throwException(new \ReflectionException()));

        Krexx::$pool->registry->set('view', $viewMock);
        Krexx::$pool->registry->set(static::VIEW_REFLECTION, $reflectionMock);
        Krexx::$pool->registry->set(static::RENDERING_CONTEXT, $contextMock);

        $newFluid = new Fluid(Krexx::$pool);
        $this->assertTrue($this->retrieveValueByReflection(static::ERROR, $newFluid));

        // And now without the property.
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->expects($this->once())
            ->method(static::HAS_PROPERTY)
            ->with(static::RENDERING_STACK)
            ->willReturn(false);
        Krexx::$pool->registry->set(static::VIEW_REFLECTION, $reflectionMock);

        $newFluid = new Fluid(Krexx::$pool);
        $this->assertTrue($this->retrieveValueByReflection(static::ERROR, $newFluid));
    }
}
