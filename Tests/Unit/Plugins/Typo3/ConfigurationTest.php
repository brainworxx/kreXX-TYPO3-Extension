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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
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
    protected function krexxUp()
    {
        parent::krexxUp();
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
            ->will($this->returnValue(AbstractTest::TYPO3_VERSION));
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaData));

        $this->assertEquals(AbstractTest::TYPO3_VERSION, $this->configuration->getVersion());
    }

    /**
     * Test the adjustments done by the TYPO3 plugin.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::exec
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::createWorkingDirectories
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::registerVersionDependantStuff
     */
    public function testExec()
    {
        // Short circuit the getting of the system path.
        $pathSite = PATH_site;
        $typo3Namespace = '\Brainworxx\\Includekrexx\\Plugins\\Typo3\\';

        $versionMock = $this->getFunctionMock($typo3Namespace, 'version_compare');
        $versionMock->expects($this->exactly(2))
            ->withConsecutive(
                [Bootstrap::getTypo3Version(), '8.3', '>'],
                [Bootstrap::getTypo3Version(), '9.5', '>=']
            )->will($this->returnValue(true));

        $classExistsMock = $this->getFunctionMock($typo3Namespace, 'class_exists');
        $classExistsMock->expects($this->once())
            ->with(Environment::class)
            ->will($this->returnValue(false));

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock($typo3Namespace, 'is_dir');
        $isDirMock->expects($this->exactly(4))
            ->withConsecutive(
                [$pathSite . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx'],
                [$pathSite . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'log'],
                [$pathSite . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'config']
            )
            ->will($this->returnValue(true));

        // Simulating the package
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');

        $arrayReplaceRecursiveMock = $this->getFunctionMock(
            $typo3Namespace,
            'array_replace_recursive'
        );
        $arrayReplaceRecursiveMock->expects($this->once())
            ->with($this->anything(), [Configuration::KREXX => ['module' => Log::class, 'before' => ['log']]]);
        // You just have to love these large arrays inside the globals.
        $GLOBALS[Configuration::TYPO3_CONF_VARS][Configuration::EXTCONF]
            [Configuration::ADMIN_PANEL][Configuration::MODULES][Configuration::DEBUG]
            [Configuration::SUBMODULES] = ['module' => Log::class, 'after' => ['log']];

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
            'some' . DIRECTORY_SEPARATOR . 'path' . DIRECTORY_SEPARATOR . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Krexx.ini',
            SettingsGetter::getConfigFile(),
            'Test the new location of the configuration file.'
        );
        $this->assertEquals(
            'some' . DIRECTORY_SEPARATOR . 'path' . DIRECTORY_SEPARATOR . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'chunks' . DIRECTORY_SEPARATOR,
            SettingsGetter::getChunkFolder(),
            'Test the new location of the chunk folder.'
        );
        $this->assertEquals(
            'some' . DIRECTORY_SEPARATOR . 'path' . DIRECTORY_SEPARATOR . 'typo3temp' . DIRECTORY_SEPARATOR . 'tx_includekrexx' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR,
            SettingsGetter::getLogFolder(),
            'Test the new location of the log folder.'
        );

        $toString = '__toString';
        $this->assertEquals(
            [
                'TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper' => [$toString],
                'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper' => [$toString],
                'TYPO3\CMS\Extbase\Persistence\RepositoryInterface' => ['removeAll'],
                'TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy' => [$toString],
                'TYPO3\CMS\Core\Database\Query\QueryBuilder' => [$toString],
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