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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;

class AbstractControllerTest extends AbstractTest
{
    /**
     * Test the creation of the pool and its assigning to the class.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::__construct
     */
    public function testConstruct()
    {
        $indexController = new IndexController();
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $indexController));
    }

    /**
     * Test the injection of the configuration.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::injectConfiguration
     */
    public function testInjectConfiguration()
    {
        $configMock = $this->createMock(Configuration::class);
        $indexController = new IndexController();
        $indexController->injectConfiguration($configMock);
        $this->assertSame($configMock, $this->retrieveValueByReflection('configuration', $indexController));
    }

    /**
     * Test the injection of the from configuration.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::injectFormConfiguration
     */
    public function testInjectFormConfiguration()
    {
        $formConfigMock = $this->createMock(FormConfiguration::class);
        $indexController = new IndexController();
        $indexController->injectFormConfiguration($formConfigMock);
        $this->assertSame($formConfigMock, $this->retrieveValueByReflection('formConfiguration', $indexController));
    }

    /**
     * Test  the injection of the settings model.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::injectSettings
     */
    public function testInjectSettings()
    {
        $settings = $this->createMock(Settings::class);
        $indexController = new IndexController();
        $indexController->injectSettingsModel($settings);
        $this->assertSame($settings, $this->retrieveValueByReflection('settingsModel', $indexController));
    }

    /**
     * Test the injection of the live preset.
     *
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::injectLivePreset
     */
    public function testInjectLivePreset()
    {
        $preset = $this->createMock(LivePreset::class);
        $indexController = new IndexController();
        $indexController->injectLivePreset($preset);
        $this->assertSame($preset, $this->retrieveValueByReflection('livePreset', $indexController));
    }
}