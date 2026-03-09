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

namespace Brainworxx\Includekrexx\Tests\Unit\ViewHelpers;

use Brainworxx\Includekrexx\Tests\Helpers\ModuleTemplate;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3Fluid\Fluid\View\ViewInterface;

#[CoversMethod(DebugViewHelper::class, 'render')]
#[CoversMethod(DebugViewHelper::class, 'analysis')]
#[CoversMethod(DebugViewHelper::class, 'initializeArguments')]
class DebugViewHelperTest extends AbstractHelper
{
    /**
     * Testing the initializing of our single argument.
     */
    public function testInitializeArguments()
    {
        $debugViewHelper = new DebugViewHelper();
        $debugViewHelper->initializeArguments();

        $this->assertArrayHasKey(
            'value',
            $this->retrieveValueByReflection('argumentDefinitions', $debugViewHelper)
        );
    }

    /**
     * Test the rendering of the debug ViewHelper.
     */
    public function testRender()
    {
        $debugViewHelper = new DebugViewHelper();
        // Inject the view and the rendering context.
        // Mock the view
        if (class_exists(StandaloneView::class)) {
            $view = $this->createMock(StandaloneView::class);
        } else {
            $view = $this->createMock(ViewInterface::class);
        }
        $variableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $variableContainer->expects($this->once())
            ->method('getView')
            ->willReturn($view);
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->expects($this->once())
            ->method('getViewHelperVariableContainer')
            ->willReturn($variableContainer);
        $debugViewHelper->setRenderingContext($renderingContext);

        // Inject the children closure.
        $closure = function () {
            return new \StdClass();
        };
        $debugViewHelper->setRenderChildrenClosure($closure);

        // Inject the arguments.
        $debugViewHelper->setArguments(['value' => 'some text']);

        // Stop the analysis in it's tracks.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(2))
            ->method('getSetting')
            ->willReturn(true);
        Krexx::$pool->config = $configMock;

        $debugViewHelper->render();

        // Analyse nothing at all.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(1))
            ->method('getSetting')
            ->willReturn(true);
        Krexx::$pool->config = $configMock;
        $debugViewHelper = new DebugViewHelper();
        if (class_exists(StandaloneView::class)) {
            $view = $this->createMock(StandaloneView::class);
        } else {
            $view = $this->createMock(ViewInterface::class);
        }
        $variableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $variableContainer->expects($this->once())
            ->method('getView')
            ->willReturn($view);
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->expects($this->any())
            ->method('getViewHelperVariableContainer')
            ->willReturn($variableContainer);
        $debugViewHelper->setRenderingContext($renderingContext);
        $reflection = new \ReflectionClass(ViewHelperNode::class);
        $viewHelperNode = $reflection->newInstanceWithoutConstructor();
        $debugViewHelper->setViewHelperNode($viewHelperNode);

        $debugViewHelper->render();

        // Analyse something that jumps at the debuggers face like an alien facehugger!
        // I mean, we throw an expected exception in the renderChildrenClosure.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(1))
            ->method('getSetting')
            ->willReturn(true);
        Krexx::$pool->config = $configMock;
        $debugViewHelper = new DebugViewHelper();
        if (class_exists(StandaloneView::class)) {
            $view = $this->createMock(StandaloneView::class);
        } else {
            $view = $this->createMock(ViewInterface::class);
        }
        $variableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $variableContainer->expects($this->once())
            ->method('getView')
            ->willReturn($view);
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->expects($this->any())
            ->method('getViewHelperVariableContainer')
            ->willReturn($variableContainer);
        $debugViewHelper->setRenderingContext($renderingContext);
        $debugViewHelper->setRenderChildrenClosure(function () {
            throw new \RuntimeException('This is an expected exception.');
        });
        $debugViewHelper->render();
    }
}
