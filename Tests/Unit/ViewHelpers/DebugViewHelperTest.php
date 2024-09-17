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

namespace Brainworxx\Includekrexx\Tests\Unit\ViewHelpers;

use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

class DebugViewHelperTest extends AbstractHelper
{
    /**
     * Testing the initializing of our single argument.
     *
     * @covers \Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper::initializeArguments
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
     *
     * @covers \Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper::render
     * @covers \Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper::analysis
     */
    public function testRender()
    {
        $debugViewHelper = new DebugViewHelper();
        // Inject the view and the rendering context.
        $view = $this->createMock(StandaloneView::class);
        $variableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $variableContainer->expects($this->once())
            ->method('getView')
            ->will($this->returnValue($view));
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->expects($this->once())
            ->method('getViewHelperVariableContainer')
            ->will($this->returnValue($variableContainer));
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
            ->will($this->returnValue(true));
        Krexx::$pool->config = $configMock;

        $debugViewHelper->render();
    }
}
