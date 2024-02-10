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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Controller;

use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Tests\Helpers\ModuleTemplateFactory;
use Brainworxx\Krexx\Krexx;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;

class AbstractControllerTest extends AbstractHelper
{
    /**
     * Test the creation of the pool and its assigning to the class.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::__construct
     */
    public function testConstruct()
    {
        $configMock = $this->createMock(Configuration::class);
        $formConfigMock = $this->createMock(FormConfiguration::class);
        $settings = $this->createMock(Settings::class);
        $pageRenderer = $this->createMock(PageRenderer::class);

        $indexController = new IndexController($configMock, $formConfigMock, $settings, $pageRenderer);

        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $indexController));
        $this->assertSame($configMock, $this->retrieveValueByReflection('configuration', $indexController));
        $this->assertSame($formConfigMock, $this->retrieveValueByReflection('formConfiguration', $indexController));
        $this->assertSame($settings, $this->retrieveValueByReflection('settingsModel', $indexController));
        $this->assertSame($pageRenderer, $this->retrieveValueByReflection('pageRenderer', $indexController));
    }

    /**
     * Test if the initialize action can produce the module template
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::initializeAction
     */
    public function testInitializeAction()
    {
        $configMock = $this->createMock(Configuration::class);
        $formConfigMock = $this->createMock(FormConfiguration::class);
        $settings = $this->createMock(Settings::class);
        $pageRenderer = $this->createMock(PageRenderer::class);

        $indexController = new IndexController($configMock, $formConfigMock, $settings, $pageRenderer);

        $mtMock = $this->createMock(\stdClass::class);
        if (method_exists($indexController, 'injectObjectManager')) {
            // TYPO3 11 style
            $objectManagerMock = $this->createMock(ObjectManager::class);
            $objectManagerMock->expects($this->once())
                ->method('get')
                ->with(ModuleTemplate::class)
                ->will($this->returnValue($mtMock));
            $this->setValueByReflection('objectManager', $objectManagerMock, $indexController);
        } else {
            // TYPO3 12 style.
            // We are using the ModuleTemplateFactory.
            $mtFactoryMock = $this->createMock(ModuleTemplateFactory::class);
            $mtFactoryMock->expects($this->once())
                ->method('create')
                ->will($this->returnValue($mtMock));
            $this->injectIntoGeneralUtility(\TYPO3\CMS\Backend\Template\ModuleTemplateFactory::class , $mtFactoryMock);
            $requestMock = $this->createMock(RequestInterface::class);
            $this->setValueByReflection('request', $requestMock, $indexController);
        }

        $indexController->initializeAction();
    }

    /**
     * Test the injection of the live preset.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::injectLivePreset
     */
    public function testInjectLivePreset()
    {
        $configMock = $this->createMock(Configuration::class);
        $formConfigMock = $this->createMock(FormConfiguration::class);
        $settings = $this->createMock(Settings::class);
        $pageRenderer = $this->createMock(PageRenderer::class);

        $indexController = new IndexController($configMock, $formConfigMock, $settings, $pageRenderer);

        $preset = $this->createMock(LivePreset::class);
        $indexController->injectLivePreset($preset);
        $this->assertSame($preset, $this->retrieveValueByReflection('livePreset', $indexController));
    }
}