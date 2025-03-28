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
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\Core\Parser\ParsedTemplateInterface;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractFluid::class, 'findCaller')]
#[CoversMethod(AbstractFluid::class, 'resolvePath')]
#[CoversMethod(Fluid::class, 'getPartialPath')]
#[CoversMethod(Fluid::class, 'resolveTemplateName')]
#[CoversMethod(AbstractFluid::class, 'getType')]
#[CoversMethod(AbstractFluid::class, 'resolveLineAndVarName')]
#[CoversMethod(AbstractFluid::class, 'retrieveNameLine')]
#[CoversMethod(AbstractFluid::class, 'checkForComplicatedStuff')]
#[CoversMethod(Fluid::class, 'getLayoutPath')]
#[CoversMethod(Fluid::class, 'getTemplatePath')]
class FluidTest extends AbstractHelper
{
    protected const RENDERING_CONTEXT = 'renderingContext';
    protected const GET_TEMPLATE_PATHS = 'getTemplatePaths';
    protected const VARMANE = 'varname';

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        // Load the fluid language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 'fluid.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();
    }

    /**
     * Test the template part.
     */
    public function testFindCallerTemplate()
    {
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => new \StdClass(), 'type' => 1]];
        $fluid = $this->createInstance($renderingStack, Fluid::class);

        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate1.html');

        $templatePathMock = $this->createMock(TemplatePaths::class);
        $templatePathMock->expects($this->once())
            ->method('getFormat')
            ->willReturn('formatZeh');
        $templatePathMock->expects($this->once())
            ->method('resolveTemplateFileForControllerAndActionAndFormat')
            ->with('SomeController', 'andAction', 'formatZeh')
            ->willReturn($templatePath);

        // Adding stuff to the rednering context mock.
        /** @var \PHPUnit\Framework\MockObject\MockObject $contextMock */
        $contextMock = Krexx::$pool->registry->get(static::RENDERING_CONTEXT);
        $contextMock->expects($this->once())
            ->method('getControllerName')
            ->willReturn('SomeController');
        $contextMock->expects($this->once())
            ->method('getControllerAction')
            ->willReturn('andAction');
        $contextMock->expects($this->exactly(2))
            ->method(static::GET_TEMPLATE_PATHS)
            ->willReturn($templatePathMock);

        $headline = 'Breaking News!';
        $data = new \StdClass();
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate1.html', $result['file']);
        $this->assertEquals('_all', $result[static::VARMANE]);
        $this->assertEquals('Fluid analysis of _all, stdClass', $result['type']);
        $this->assertEquals(2, $result['line']);
        $this->assertNotEmpty($result['date']);
    }

    /**
     * Test the layout part.
     */
    public function testFindCallerLayout()
    {
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate2.html');

        $parsedTemplateMock = $this->createMock(ParsedTemplateInterface::class);
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => $parsedTemplateMock, 'type' => 3]];
        $fluid = $this->createInstance($renderingStack, Fluid::class);
        $parsedTemplateMock->expects($this->once())
            ->method('getLayoutName')
            ->with(Krexx::$pool->registry->get(static::RENDERING_CONTEXT))
            ->willReturn('some filename');

        $templatePathMock = $this->createMock(TemplatePaths::class);
        $templatePathMock->expects($this->once())
            ->method('getLayoutPathAndFilename')
            ->willReturn($templatePath);

        /** @var \PHPUnit\Framework\MockObject\MockObject $contextMock */
        $contextMock = Krexx::$pool->registry->get(static::RENDERING_CONTEXT);
        $contextMock->expects($this->once())
            ->method(static::GET_TEMPLATE_PATHS)
            ->willReturn($templatePathMock);

        $headline = 'H1';
        $data = 'text';
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate2.html', $result['file']);
        $this->assertEquals('text', $result[static::VARMANE]);
        $this->assertEquals('Fluid analysis of text, string', $result['type']);
        $this->assertEquals(2, $result['line']);
        $this->assertNotEmpty($result['date']);
    }

    /**
     * Test the partial part.
     */
    public function testFindCallerPartial()
    {
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate3.html');
        $parsedTemplateMock = $this->createMock(ParsedTemplateInterface::class);
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => $parsedTemplateMock, 'type' => 2]];
        $fluid = $this->createInstance($renderingStack, Fluid::class);

        $parsedTemplateMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('qwer_asdf_23409809afg');

        $resolvedIdentifiers = [];
        $resolvedIdentifiers['partials']['qwer/asdf'] = 'qwer/asdf_23409809afg';
        $templatePathMock = $this->createMock(TemplatePaths::class);
        $this->setValueByReflection('resolvedIdentifiers', $resolvedIdentifiers, $templatePathMock);
        $templatePathMock->expects($this->once())
            ->method('getPartialPathAndFilename')
            ->with('qwer/asdf')
            ->willReturn($templatePath);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contextMock */
        $contextMock = Krexx::$pool->registry->get(static::RENDERING_CONTEXT);
        $contextMock->expects($this->once())
            ->method(static::GET_TEMPLATE_PATHS)
            ->willReturn($templatePathMock);

        // We are going into the complicated stuff here.
        Krexx::$pool->codegenHandler = new Codegen(Krexx::$pool);

        $headline = 'H1';
        $data =  [5];
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate3.html', $result['file']);
        $this->assertEquals('fluidvar', $result[static::VARMANE]);
        $this->assertEquals('Fluid analysis of fluidvar, array', $result['type']);
        $this->assertEquals(4, $result['line']);
        $this->assertNotEmpty($result['date']);

        $this->assertEquals(
            '&amp;lt;f:variable value=&amp;quot;{&#123;some: &#039;array&#039;}}&amp;quot; name=&amp;quot;fluidvar&amp;quot; /&amp;gt; {fluidvar}',
            Krexx::$pool->codegenHandler->generateWrapperLeft() . $result[static::VARMANE] .
            Krexx::$pool->codegenHandler->generateWrapperRight(),
            'Testing the complicated code generation stuff.'
        );
    }

    /**
     * Test what happens when there is an error.
     */
    public function testFindCallerError()
    {
        $fluid = $this->createInstance([], Fluid::class);
        $this->setValueByReflection('error', true, $fluid);

        $result = $fluid->findCaller('bla', 'blub');
        $this->assertEquals('n/a', $result['file']);
        $this->assertEquals('n/a', $result['line']);
        $this->assertEquals('fluidvar', $result[static::VARMANE]);
        $this->assertEquals('Fluid analysis of fluidvar, string', $result['type']);
        $this->assertNotEmpty($result['date']);
    }

    /**
     * Find the caller, when krexx is called twice.
     */
    public function testFindCallerDoubleCall()
    {
        $templatePath = realpath(__DIR__ . '/../../../../../Fixtures/FluidTemplate4.html');
        $parsedTemplateMock = $this->createMock(ParsedTemplateInterface::class);
        $renderingStack = [[AbstractCallerFinderTest::PARSED_TEMPLATE => $parsedTemplateMock, 'type' => 2]];
        $fluid = $this->createInstance($renderingStack, Fluid::class);

        $parsedTemplateMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('qwer_asdf_23409809afg');

        $resolvedIdentifiers = [];
        $resolvedIdentifiers['partials']['qwer/asdf'] = 'qwer/asdf_23409809afg';
        $templatePathMock = $this->createMock(TemplatePaths::class);
        $this->setValueByReflection('resolvedIdentifiers', $resolvedIdentifiers, $templatePathMock);
        $templatePathMock->expects($this->once())
            ->method('getPartialPathAndFilename')
            ->with('qwer/asdf')
            ->willReturn($templatePath);
        /** @var \PHPUnit\Framework\MockObject\MockObject $contextMock */
        $contextMock = Krexx::$pool->registry->get(static::RENDERING_CONTEXT);
        $contextMock->expects($this->once())
            ->method(static::GET_TEMPLATE_PATHS)
            ->willReturn($templatePathMock);

        // We are going into the complicated stuff here.
        Krexx::$pool->codegenHandler = new Codegen(Krexx::$pool);

        $headline = 'H1';
        $data =  [5];
        $result = $fluid->findCaller($headline, $data);

        $this->assertStringContainsString('FluidTemplate4.html', $result['file'], 'Filename contains the fimename');
        $this->assertEquals('fluidvar', $result[static::VARMANE], 'Variable name is not unresolvable');
        $this->assertEquals('Fluid analysis of fluidvar, array', $result['type']);
        $this->assertEquals('n/a', $result['line'], 'Line in the template is unresolvable');
        $this->assertNotEmpty($result['date']);
    }
}
