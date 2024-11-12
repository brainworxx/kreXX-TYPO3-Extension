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

namespace Brainworxx\Includekrexx\Tests\Unit\Bootstrap;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration as AimeosConfiguration;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class BootstrapTest extends AbstractHelper
{
    const BOOTSTRAP_NAMESPACE = '\\Brainworxx\\Includekrexx\\Bootstrap\\';
    const DEFINED = 'defined';

    /**
     * @var \Brainworxx\Includekrexx\Bootstrap\Bootstrap
     */
    protected $bootstrap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootstrap = new Bootstrap();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS[$this->bootstrap::TYPO3_CONF_VARS][$this->bootstrap::SYS][$this->bootstrap::FLUID][$this->bootstrap::FLUID_NAMESPACE][$this->bootstrap::KREXX]);
        parent::tearDown();
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
        $definedMock = $this->getFunctionMock(static::BOOTSTRAP_NAMESPACE, static::DEFINED);
        $definedMock->expects($this->once())
            ->with('KREXX_DIR')
            ->willReturn(false);

        // And the kreXX bootstrap script is not available.
        $fileExistsMock = $this->getFunctionMock(static::BOOTSTRAP_NAMESPACE, 'file_exists');
        $fileExistsMock->expects($this->once())
            ->with($this->anything())
            ->willReturn(true);

        // Should lead to an early return.
        // Retrieving a standard class here would cause the test to fail.
        $t3ConfigMock = $this->createMock(T3configuration::class);
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);

        // Since we as retrieving the extension path, we need to simulate the
        // existing of the includekrexx package.
        $packageManagerMock = $this->createMock(UnitTestPackageManager::class);
        $packageManagerMock->expects($this->any())
            ->method('isPackageActive')
            ->willReturn(true);
        $this->setValueByReflection('packageManager', $packageManagerMock, ExtensionManagementUtility::class);

        $packageMock = $this->createMock(Package::class);
        $packageMock->expects($this->any())
            ->method('getPackagePath')
            ->willReturn('meh!');

        $packageManagerMock->expects($this->any())
            ->method('getPackage')
            ->willReturn($packageMock);

        $this->bootstrap->run();
    }

    /**
     * We test the historical inclusion of kreXX, which is now the autoloader
     * provided by kreXX.
     *
     * We expect that there will be no exception when doing it.
     *
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::run
     * @covers \Brainworxx\Includekrexx\Bootstrap\Bootstrap::loadKrexx
     */
    public function testRunInlcudeKrexx()
    {
        // The kreXX directory is not defined . . .
        $definedMock = $this->getFunctionMock(static::BOOTSTRAP_NAMESPACE, static::DEFINED);
        $definedMock->expects($this->once())
            ->with('KREXX_DIR')
            ->willReturn(false);

        // Should lead to an early return.
        // Retrieving a standard class here would cause the test to fail.
        $t3ConfigMock = new \StdClass();
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);

        // Since we as retrieving the extension path, we need to simulate the
        // existing of the includekrexx package.
        $this->simulatePackage(Bootstrap::EXT_KEY, Bootstrap::EXT_KEY . '/');

        $this->bootstrap->run();
    }

    public function testRunNormal()
    {
        $definedMock = $this->getFunctionMock(static::BOOTSTRAP_NAMESPACE, static::DEFINED);
        $definedMock->expects($this->once())
            ->willReturn(true);

        $t3ConfigMock = $this->createMock(T3configuration::class);
        $fluidConfigMock = $this->createMock(FluidConfiguration::class);
        $aimeosConfigMock = $this->createMock(AimeosConfiguration::class);
        $this->injectIntoGeneralUtility(T3configuration::class, $t3ConfigMock);
        $this->injectIntoGeneralUtility(FluidConfiguration::class, $fluidConfigMock);
        $this->injectIntoGeneralUtility(AimeosConfiguration::class, $aimeosConfigMock);
        $this->simulatePackage('aimeos', 'whatever');

        $this->bootstrap->run();

        $this->assertEquals(
            [0 => 'Brainworxx\\Includekrexx\\ViewHelpers'],
            $GLOBALS[$this->bootstrap::TYPO3_CONF_VARS][$this->bootstrap::SYS][$this->bootstrap::FLUID][$this->bootstrap::FLUID_NAMESPACE][$this->bootstrap::KREXX],
            'Registering of the krexx fluid namespace'
        );
    }
}
