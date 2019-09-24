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

namespace Brainworxx\Includekrexx\Tests\Unit\Bootstrap;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Tests\Helpers\TestTrait;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Includekrexx\Plugins\FluidDataViewer\Configuration as FluidDataConfiguration;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration as AimeosConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BootstrapTest extends UnitTestCase
{
    use TestTrait;

    /**
     * @var \Brainworxx\Includekrexx\Bootstrap\Bootstrap
     */
    protected $bootstrap;

    public function setUp()
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->bootstrap = new Bootstrap();
    }

    /**
     * Testing the early failing of the bootstrapping.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::run
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::loadKrexx
     */
    public function testRunEarlyFail()
    {
        // The kreXX directory is not defined . . .
        $definedMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'defined');
        $definedMock->expects($this->once())
            ->with('KREXX_DIR')
            ->will($this->returnValue(false));

        // And the kreXX bootstrap script is not available.
        $fileExistsMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'file_exists');
        $fileExistsMock->expects($this->once())
            ->with($this->anything())
            ->will($this->returnValue(false));

        // Should lead to an early return.
        // Retrieving a standard class here would cause the test to fail.
        $t3ConfigMock = new \StdClass();
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);

        // Since we as retrieving the extension path, we need to simulate the
        // existing of the includekrexx package.
        $this->simulatePackage(Bootstrap::EXT_KEY, 'some path');

        $this->bootstrap->run();
    }

    /**
     * Testing the failing of the bootstrapping.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::run
     */
    public function testRunFail()
    {
        // Retrieving a standard class here would cause the test to fail.
        $t3ConfigMock = new \StdClass();
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);

        // We simulate a failed autoloading.
        // This normally happens during the update of the extension.
        $classExistsMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'class_exists');
        $classExistsMock->expects($this->once())
            ->with(Registration::class)
            ->will($this->returnValue(false));

        $this->bootstrap->run();
    }

    /**
     * Testing the bootstrapping with a TYPO3 version lower than 8.5.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::run
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::loadKrexx
     */
    public function testRunLowT3Version()
    {
        $definedMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'defined');
        $definedMock->expects($this->once())
            ->will($this->returnValue(true));

        $versionCompMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'version_compare');
        $versionCompMock->expects($this->exactly(2))
            ->will($this->returnValue(false));

        $t3ConfigMock = $this->createMock(T3configuration::class);
        $t3ConfigMock->expects($this->once())
            ->method('exec');

        $fluidConfigMock = $this->createMock(FluidConfiguration::class);
        $fluidConfigMock->expects($this->never())
            ->method('exec');

        $fluidDataConfigMock = $this->createMock(FluidDataConfiguration::class);
        $fluidDataConfigMock->expects($this->never())
            ->method('exec');

        $aimeosConfigMock = $this->createMock(AimeosConfiguration::class);
        $aimeosConfigMock->expects($this->once())
            ->method('exec');

        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);
        $this->injectIntoGeneralUtility(FluidConfiguration::class, $fluidConfigMock);
        $this->injectIntoGeneralUtility(FluidDataConfiguration::class, $fluidDataConfigMock);
        $this->injectIntoGeneralUtility(AimeosConfiguration::class, $aimeosConfigMock);

        $this->bootstrap->run();
    }

    public function testRunHighT3Version()
    {
        $definedMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'defined');
        $definedMock->expects($this->once())
            ->will($this->returnValue(true));

        $versionCompMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Bootstrap\\', 'version_compare');
        $versionCompMock->expects($this->exactly(2))
            ->will($this->returnValue(true));

        $t3ConfigMock = $this->createMock(T3configuration::class);
        $fluidConfigMock = $this->createMock(FluidConfiguration::class);
        $fluidDataConfigMock = $this->createMock(FluidDataConfiguration::class);
        $aimeosConfigMock = $this->createMock(AimeosConfiguration::class);
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);
        $this->injectIntoGeneralUtility(FluidConfiguration::class, $fluidConfigMock);
        $this->injectIntoGeneralUtility(FluidDataConfiguration::class, $fluidDataConfigMock);
        $this->injectIntoGeneralUtility(AimeosConfiguration::class, $aimeosConfigMock);

        // You just have to love these large arrays inside the globals.
        $GLOBALS[$this->bootstrap::TYPO3_CONF_VARS][$this->bootstrap::EXTCONF]
            [$this->bootstrap::ADMIN_PANEL][$this->bootstrap::MODULES][$this->bootstrap::DEBUG]
            [$this->bootstrap::SUBMODULES] = ['module' => Log::class, 'after' => ['log']];

        $arrayReplaceRecursiveMock = $this->getFunctionMock(
            '\\Brainworxx\\Includekrexx\\Bootstrap\\',
            'array_replace_recursive'
        );
        $arrayReplaceRecursiveMock->expects($this->once())
            ->with($this->anything(), [$this->bootstrap::KREXX => ['module' => Log::class, 'after' => ['log']]]);

        $this->bootstrap->run();

        $this->assertEquals(
            [0 => 'Brainworxx\\Includekrexx\\ViewHelpers'],
            $GLOBALS[$this->bootstrap::TYPO3_CONF_VARS][$this->bootstrap::SYS][$this->bootstrap::FLUID][$this->bootstrap::FLUID_NAMESPACE][$this->bootstrap::KREXX],
            'Registering of the krexx fluid namespace'
        );
    }

    /**
     * Test the clear cache, when getting the 'wrong' version number.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::checkVersionNumber
     */
    public function testCheckVersionNumberUpdate()
    {
        $metaMock = $this->createMock(MetaData::class);
        $metaMock->expects($this->exactly(1))
            ->method('getVersion')
            ->will($this->returnValue('1.2.3'));
        $packageMock = $this->simulatePackage($this->bootstrap::EXT_KEY, 'any path');
        $packageMock->expects($this->exactly(1))
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaMock));

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->expects($this->once())
            ->method('flushCachesInGroup')
            ->with('system');

        $this->injectIntoGeneralUtility(CacheManager::class, $cacheManagerMock);

        $this->bootstrap->checkVersionNumber('abcd');
    }

    /**
     * Test the clear cache, when getting the 'right' version number.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::checkVersionNumber
     */
    public function testCheckVersionNumberNormal()
    {
        $metaMock = $this->createMock(MetaData::class);
        $metaMock->expects($this->exactly(1))
            ->method('getVersion')
            ->will($this->returnValue('1.2.3'));
        $packageMock = $this->simulatePackage($this->bootstrap::EXT_KEY, 'any path');
        $packageMock->expects($this->exactly(1))
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaMock));

        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheManagerMock->expects($this->never())
            ->method('flushCachesInGroup')
            ->with('system');

        $this->injectIntoGeneralUtility(CacheManager::class, $cacheManagerMock);

        $this->bootstrap->checkVersionNumber('1.2.3');
    }
}
