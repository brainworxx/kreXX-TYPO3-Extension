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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\FlexFormParser;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Scalar\String\Xml;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Includekrexx\Plugins\Typo3\Rewrites\CheckOutput as T3CheckOutput;
use Brainworxx\Krexx\View\Output\CheckOutput;
use Krexx;

class ConfigurationTest extends AbstractTest implements ConstInterface
{

    const REVERSE_PROXY = 'reverseProxyIP';
    protected const TYPO3_TEMP = 'typo3temp';

    /**
     * Do we have to reset the reverse proxy?
     *
     * @var bool
     */
    protected $unsetReverseProxy = false;

    /**
     * {@inheritDoc}
     */
    protected function krexxDown()
    {
        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]);
        parent::krexxDown();
    }

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

        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY])) {
            return;
        }

        if (isset($GLOBALS[static::TYPO3_CONF_VARS]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS] = [];
        }
        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS] = [];
        }
        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY] = '';
        }

        $this->unsetReverseProxy = true;
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
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::registerFileWriterSettings
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::registerFileWriter
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::createFileWriterValidator
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Configuration::generateTempPaths
     */
    public function testExec()
    {
        $log = 'log';

        // Short circuit the getting of the system path.
        $pathSite = 'somePath';
        $this->setValueByReflection('varPath', $pathSite, Environment::class);

        $typo3Namespace = '\Brainworxx\\Includekrexx\\Plugins\\Typo3\\';

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock($typo3Namespace, 'is_dir');
        $isDirMock->expects($this->exactly(8))
            ->withConsecutive(
                [$pathSite . DIRECTORY_SEPARATOR .  static::TX_INCLUDEKREXX],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . $log],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'config']
            )
            ->will($this->returnValue(true));

        // Simulating the package
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');

        $arrayReplaceRecursiveMock = $this->getFunctionMock(
            $typo3Namespace,
            'array_replace_recursive'
        );
        $arrayReplaceRecursiveMock->expects($this->any())
            ->with($this->anything(), [Configuration::KREXX => ['module' => Log::class, 'before' => [$log]]]);
        // You just have to love these large arrays inside the globals.
        $GLOBALS[Configuration::TYPO3_CONF_VARS][Configuration::EXTCONF]
            [Configuration::ADMIN_PANEL][Configuration::MODULES][Configuration::DEBUG]
            [Configuration::SUBMODULES] = ['module' => Log::class, 'after' => [$log]];

        $this->configuration->exec();

        $this->assertEquals(
            [
                ProcessObject::class . Configuration::START_PROCESS => [DirtyModels::class => DirtyModels::class],
                Objects::class . Configuration::START_EVENT => [QueryDebugger::class => QueryDebugger::class],
                Xml::class . Configuration::END_EVENT => [FlexFormParser::class => FlexFormParser::class]
            ],
            SettingsGetter::getEventList()
        );

        $this->assertEquals(
            [CheckOutput::class => T3CheckOutput::class],
            SettingsGetter::getRewriteList(),
            'Test the rewrite.'
        );

        $rootpath = $pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR;
        $this->assertEquals(
            $rootpath . 'config' . DIRECTORY_SEPARATOR . 'Krexx.',
            SettingsGetter::getConfigFile(),
            'Test the new location of the configuration file.'
        );
        $this->assertEquals(
            $rootpath . 'chunks' . DIRECTORY_SEPARATOR,
            SettingsGetter::getChunkFolder(),
            'Test the new location of the chunk folder.'
        );
        $this->assertEquals(
            $rootpath . $log . DIRECTORY_SEPARATOR,
            SettingsGetter::getLogFolder(),
            'Test the new location of the log folder.'
        );

        $toString = '__toString';
        $this->assertEquals(
            [
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

        $this->assertEquals(
            [ExtFilePath::class, LllString::class],
            SettingsGetter::getAdditionalScalarString(),
            'Both scalar analyser are registered.'
        );

        // We create a new pool and test, if our new settings are available.
        Krexx::$pool = null;
        Pool::createPool();
        $this->assertFalse(
            Krexx::$pool->config->getSetting(static::ACTIVATE_T3_FILE_WRITER),
            'Default value is false.'
        );
        $this->assertEquals(
            LogLevel::ERROR,
            Krexx::$pool->config->getSetting(static::LOG_LEVEL_T3_FILE_WRITER),
            'Default value is the error log level.'
        );
        $this->assertTrue(
            empty($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION]),
            'File writer was not registered, because it is deactivated.'
        );

        // Test again with an activated file writer.
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        ConfigSupplier::$overwriteValues[static::ACTIVATE_T3_FILE_WRITER] = true;
        $this->configuration->exec();
        $this->assertFalse(
            empty($GLOBALS[static::TYPO3_CONF_VARS][static::LOG][static::WRITER_CONFIGURATION]),
            'File writer was registered.'
        );

        // Test evaluation with a wrong value
        $result = Krexx::$pool->config->validation->evaluateSetting(
            $this->configuration->getName(),
            static::LOG_LEVEL_T3_FILE_WRITER,
            'wrong value'
        );
        $this->assertFalse($result, 'This is literally a wrong value.');

        $result = Krexx::$pool->config->validation->evaluateSetting(
            $this->configuration->getName(),
            static::LOG_LEVEL_T3_FILE_WRITER,
            LogLevel::ERROR
        );
        $this->assertTrue($result, 'Error log level.');
    }
}
