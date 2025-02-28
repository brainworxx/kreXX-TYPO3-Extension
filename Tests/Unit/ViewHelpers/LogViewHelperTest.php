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

namespace Brainworxx\Includekrexx\Tests\Unit\ViewHelpers;

use Brainworxx\Includekrexx\ViewHelpers\LogViewHelper;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(LogViewHelper::class, 'analysis')]
class LogViewHelperTest extends AbstractHelper
{
    /**
     * Test the logging part of the log ViewHelper.
     */
    public function testRender()
    {
        $logViewHelper = new LogViewHelper();
        // Inject the view and the rendering context.
        $view = $this->createMock(StandaloneView::class);
        $variableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $variableContainer->expects($this->once())
            ->method('getView')
            ->willReturn($view);
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $renderingContext->expects($this->once())
            ->method('getViewHelperVariableContainer')
            ->willReturn($variableContainer);
        $logViewHelper->setRenderingContext($renderingContext);

        // Inject the children closure.
        $closure = function () {
            return new \StdClass();
        };
        $logViewHelper->setRenderChildrenClosure($closure);

        // Inject the arguments.
        $logViewHelper->setArguments(['value' => null]);

        // Stop the analysis in it's tracks.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(1))
            ->method('getSetting')
            ->willReturn(true);
        Krexx::$pool->config = $configMock;

        // Make sure we are actually trying to log.
        $settingsDestination = $this->createMock(Model::class);
        $settingsDestination->expects($this->once())
            ->method('setSource')
            ->with('Forced logging')
            ->willReturn($settingsDestination);
        $settingsDestination->expects($this->once())
            ->method('setValue')
            ->with(Fallback::VALUE_FILE);
        $settingsAjax = $this->createMock(Model::class);
        $settingsAjax->expects($this->once())
            ->method('setSource')
            ->with('Forced logging')
            ->willReturn($settingsAjax);
        $settingsAjax->expects($this->once())
            ->method('setValue')
            ->with(false);
        $configMock->settings = [
            Fallback::SETTING_DESTINATION => $settingsDestination,
            Fallback::SETTING_DETECT_AJAX => $settingsAjax
        ];

        $logViewHelper->render();
    }
}
