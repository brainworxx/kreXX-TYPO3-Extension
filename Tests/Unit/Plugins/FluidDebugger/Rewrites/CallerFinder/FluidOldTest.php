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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Parser\ParsedTemplateInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;

class FluidOldTest extends AbstractTest
{

    const SET_ACCESSIBLE = 'setAccessible';
    const VIEW_REFLECTION = 'viewReflection';
    const INVOKE = 'invoke';
    const GET_METHOD = 'getMethod';
    const VARNAME = 'varname';
    const FLUID_TEMPLATE = 'FluidTemplate3.html';

    /**
     * Create a functioning fluid instance with the provided rendering stack.
     *
     * @param array $renderingStack
     * @return \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld
     */
    protected function createSpecialInstance(array $renderingStack, $identifyerCache)
    {
        // Mock the view
        $view = $this->createMock(StandaloneView::class);
        $renderingStackRefMock = $this->createMock(\ReflectionProperty::class);
        // Mock the property reflection of the rendering context.
        $renderingStackRefMock->expects($this->once())
            ->method(static::SET_ACCESSIBLE)
            ->with(true);
        $renderingStackRefMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($renderingStack));
        // Mock the reflection of the view
        $viewReflection = $this->createMock(\ReflectionClass::class);
        $viewReflection->expects($this->exactly(1))
            ->method('hasProperty')
            ->will($this->returnValue(true));

        $viewReflection->expects($this->exactly(2))
            ->method('getProperty')
            ->withConsecutive(
                ['renderingStack'],
                ['partialIdentifierCache']
            )
            ->will(
                $this->returnValueMap([
                    ['renderingStack', $renderingStackRefMock],
                    ['partialIdentifierCache', $identifyerCache]
                ])
            );

        // Mock the rendering context
        $renderingContext = $this->createMock(RenderingContext::class);

        Krexx::$pool->registry->set('view', $view);
        Krexx::$pool->registry->set(static::VIEW_REFLECTION, $viewReflection);
        Krexx::$pool->registry->set('renderingContext', $renderingContext);

        return new FluidOld(Krexx::$pool);
    }

    /**
     * Test the template part.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::findCaller
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld::getTemplatePath
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::getType
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::resolveVarname
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::checkForComplicatedStuff
     */
    public function testFindCallerTemplate()
    {
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => new \StdClass(), 'type' => 1]];
        $fluid = $this->createInstance($renderingStack, FluidOld::class);
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate1.html');

        /** @var \PHPUnit\Framework\MockObject\MockObject $reflectionMock */
        $reflectionMock = Krexx::$pool->registry->get(static::VIEW_REFLECTION);
        $reflectionMock->expects($this->once())
            ->method('hasMethod')
            ->with('getTemplatePathAndFilename')
            ->will($this->returnValue(true));

        $methodReflection = $this->createMock(\ReflectionMethod::class);
        $methodReflection->expects($this->once())
            ->method(static::SET_ACCESSIBLE)
            ->with(true);
        $methodReflection->expects($this->once())
            ->method(static::INVOKE)
            ->with(Krexx::$pool->registry->get('view'))
            ->will($this->returnValue($templatePath));

        $reflectionMock->expects($this->once())
            ->method(static::GET_METHOD)
            ->will($this->returnValue($methodReflection));

        $headline = 'Breaking News!';
        $data = new \StdClass();
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate1.html', $result['file']);
        $this->assertEquals('_all', $result[static::VARNAME]);
        $this->assertEquals('Fluid analysis of _all, stdClass', $result['type']);
        $this->assertNotEmpty($result['date']);
    }

    /**
     * Test the layout part.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::findCaller
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld::getLayoutPath
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::getType
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::resolveVarname
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::checkForComplicatedStuff
     */
    public function testFindCallerLayout()
    {
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate2.html');
        $parsedTemplateMock = $this->createMock(ParsedTemplateInterface::class);

        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => $parsedTemplateMock, 'type' => 3]];
        $fluid = $this->createInstance($renderingStack, FluidOld::class);

        $parsedTemplateMock->expects($this->once())
            ->method('getLayoutName')
            ->with(Krexx::$pool->registry->get('renderingContext'))
            ->will($this->returnValue('Fixtures/FluidTemplate2'));

        $methodReflectionMock = $this->createMock(\ReflectionMethod::class);
        $methodReflectionMock->expects($this->once())
            ->method(static::SET_ACCESSIBLE)
            ->with(true);
        $methodReflectionMock->expects($this->once())
            ->method(static::INVOKE)
            ->with(Krexx::$pool->registry->get('view'), 'Fixtures/FluidTemplate2')
            ->will($this->returnValue($templatePath));

        /** @var \PHPUnit\Framework\MockObject\MockObject $viewReflection */
        $viewReflection = Krexx::$pool->registry->get(static::VIEW_REFLECTION);
        $viewReflection->expects($this->once())
            ->method('hasMethod')
            ->with('getLayoutPathAndFilename')
            ->will($this->returnValue(true));
        $viewReflection->expects($this->once())
            ->method(static::GET_METHOD)
            ->with('getLayoutPathAndFilename')
            ->will($this->returnValue($methodReflectionMock));

        $headline = 'H1';
        $data = 'text';
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate2.html', $result['file']);
        $this->assertEquals('text', $result[static::VARNAME]);
        $this->assertEquals('Fluid analysis of text, string', $result['type']);
        $this->assertNotEmpty($result['date']);
    }

    /**
     * Test the partial part.
     *
     * Nice, huh?
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::findCaller
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld::getPartialPath
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::getType
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::resolveVarname
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\AbstractFluid::checkForComplicatedStuff
     */
    public function testFindCallerPartial()
    {
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate3.html');
        $parsedTemplateMock = $this->createMock(ParsedTemplateInterface::class);
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => $parsedTemplateMock, 'type' => 2]];
        $propertyReflection = $this->createMock(\ReflectionProperty::class);

        $fluid = $this->createSpecialInstance($renderingStack, $propertyReflection);


        // The unit tests also add a hash value after their compiled classes.
        // We abuse this hash here to simulate the same behavior in fluid.
        $identifier = explode('_', get_class($parsedTemplateMock));
        $hash = $identifier[count($identifier) -1];
        $partialIdentifierCache = [
            static::FLUID_TEMPLATE => 'abcd' . $hash
        ];
        $propertyReflection->expects($this->once())
            ->method(static::SET_ACCESSIBLE)
            ->with(true);
        $propertyReflection->expects($this->once())
            ->method('getValue')
            ->with(Krexx::$pool->registry->get('view'))
            ->will($this->returnValue($partialIdentifierCache));

        $gppafMock = $this->createMock(\ReflectionMethod::class);
        $gppafMock->expects($this->once())
            ->method(static::SET_ACCESSIBLE)
            ->with(true);
        $gppafMock->expects($this->once())
            ->method(static::INVOKE)
            ->with(Krexx::$pool->registry->get('view'), static::FLUID_TEMPLATE)
            ->will($this->returnValue($templatePath));

        /** @var \PHPUnit\Framework\MockObject\MockObject $viewReflection */
        $viewReflection = Krexx::$pool->registry->get(static::VIEW_REFLECTION);
        $viewReflection->expects($this->once())
            ->method(static::GET_METHOD)
            ->with('getPartialPathAndFilename')
            ->will($this->returnValue($gppafMock));

        // We are going into the complicated stuff here.
        Krexx::$pool->codegenHandler = new Codegen(Krexx::$pool);
        $headline = 'H1';
        $data =  [5];
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString(static::FLUID_TEMPLATE, $result['file']);
        $this->assertEquals($result[static::VARNAME], 'fluidvar');
        $this->assertEquals($result['type'], 'Fluid analysis of fluidvar, array');
        $this->assertNotEmpty($result['date']);
    }
}
