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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Includekrexx\Tests\Unit\Bootstrap\BootstrapTest;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Includekrexx\Plugins\Typo3\Rewrites\CheckOutput as T3CheckOutput;
use Brainworxx\Krexx\View\Output\CheckOutput;

class ConfigurationTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Includekrexx\Plugins\Typo3\Configuration
     */
    protected $configuration;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->configuration = new Configuration();
    }

    /**
     * Simple string contains assertion.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::getName
     */
    public function testGetName()
    {
        $this->assertNotEmpty($this->configuration->getName());
    }

    /**
     * Test the getting of the version, which is the same as the extension.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::getVersion
     */
    public function testGetVersion()
    {
        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue(BootstrapTest::TYPO3_VERSION));
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaData));

        $this->assertEquals(BootstrapTest::TYPO3_VERSION, $this->configuration->getVersion());
    }

    /**
     * Test the adjustments done by the TYPO3 plugin.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::exec
     */
    public function testExec()
    {
        // Short circuit the getting of the system path.
        $pathSite = PATH_site;

        $versionMock = $this->getFunctionMock('\Brainworxx\\Includekrexx\\Plugins\\Typo3\\', 'version_compare');
        $versionMock->expects($this->once())
            ->with(BootstrapTest::TYPO3_VERSION, '8.3', '>')
            ->will($this->returnValue(true));

        $classExistsMock = $this->getFunctionMock('\Brainworxx\\Includekrexx\\Plugins\\Typo3\\', 'class_exists');
        $classExistsMock->expects($this->once())
            ->with(Environment::class)
            ->will($this->returnValue(false));

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock('\Brainworxx\\Includekrexx\\Plugins\\Typo3\\', 'is_dir');
        $isDirMock->expects($this->exactly(4))
            ->withConsecutive(
                [$pathSite . 'typo3temp/tx_includekrexx'],
                [$pathSite . 'typo3temp/tx_includekrexx/log'],
                [$pathSite . 'typo3temp/tx_includekrexx/chunks'],
                [$pathSite . 'typo3temp/tx_includekrexx/config']
            )
            ->will($this->returnValue(true));

        // Simulationg the package
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');

        $this->configuration->exec();

        $this->assertEquals(
            [
                ProcessObject::class . Configuration::START_PROCESS => [DirtyModels::class => DirtyModels::class],
                Objects::class . Configuration::START_EVENT => [QueryDebugger::class => QueryDebugger::class]
            ],
            SettingsGetter::getEventList()
        );

        $this->assertEquals(
            [CheckOutput::class => T3CheckOutput::class],
            SettingsGetter::getRewriteList(),
            'Test the rewrite.'
        );
        $this->assertEquals(
            'some/path/typo3temp/tx_includekrexx/config/Krexx.ini',
            SettingsGetter::getConfigFile(),
            'Test the new location of the configuration file.'
        );
        $this->assertEquals(
            'some/path/typo3temp/tx_includekrexx/chunks/',
            SettingsGetter::getChunkFolder(),
            'Test the new location of the chunk folder.'
        );
        $this->assertEquals(
            'some/path/typo3temp/tx_includekrexx/log/',
            SettingsGetter::getLogFolder(),
            'Test the new location of the log folder.'
        );
        $this->assertEquals(
            [
                'TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper' => ['__toString'],
                'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper' => ['__toString'],
                'TYPO3\CMS\Extbase\Persistence\RepositoryInterface' => ['removeAll'],
                'TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy' => ['__toString'],
                'TYPO3\CMS\Core\Database\Query\QueryBuilder' => ['__toString'],
            ],
            SettingsGetter::getBlacklistDebugMethods(),
            'What the method name says.'
        );
        $this->assertEquals(
            ['what/ever/Resources/Private/Language/t3.kreXX.ini'],
            SettingsGetter::getAdditionalHelpFiles(),
            'Something about help files.'
        );
    }
}