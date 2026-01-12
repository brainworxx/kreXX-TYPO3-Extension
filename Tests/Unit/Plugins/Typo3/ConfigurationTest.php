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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Modules\Log14;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\DirtyModels;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\FlexFormParser;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\ExtFilePath;
use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Scalar\String\Xml;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Analyse\Objects as FluidObjects;
use Brainworxx\Krexx\Controller\BacktraceController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\EditSettingsController;
use Brainworxx\Krexx\Controller\ExceptionController;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Includekrexx\Plugins\Typo3\Rewrites\CheckOutput as T3CheckOutput;
use Brainworxx\Krexx\View\Output\CheckOutput;
use Krexx;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Configuration::class, 'exec')]
#[CoversMethod(Configuration::class, 'registerBlacklisting')]
#[CoversMethod(Configuration::class, 'createWorkingDirectories')]
#[CoversMethod(Configuration::class, 'registerVersionDependantStuff')]
#[CoversMethod(Configuration::class, 'registerFileWriterSettings')]
#[CoversMethod(Configuration::class, 'registerFileWriter')]
#[CoversMethod(Configuration::class, 'createFileWriterValidator')]
#[CoversMethod(Configuration::class, 'generateTempPaths')]
#[CoversMethod(Configuration::class, 'getVersion')]
#[CoversMethod(Configuration::class, 'getName')]
class ConfigurationTest extends AbstractHelper implements ConstInterface
{

    protected const REVERSE_PROXY = 'reverseProxyIP';
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
    protected function tearDown(): void
    {
        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]);
        parent::tearDown();
    }

    /**
     * @var \Brainworxx\Includekrexx\Plugins\Typo3\Configuration
     */
    protected $configuration;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
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
     */
    public function testGetName()
    {
        $this->assertNotEmpty($this->configuration->getName());
    }

    /**
     * Test the getting of the version, which is the same as the extension.
     */
    public function testGetVersion()
    {
        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->once())
            ->method('getVersion')
            ->willReturn(AbstractHelper::TYPO3_VERSION);
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->willReturn($metaData);

        $this->assertEquals(AbstractHelper::TYPO3_VERSION, $this->configuration->getVersion());
    }

    /**
     * Test the adjustments done by the TYPO3 plugin.
     */
    public function testExec()
    {
        $log = 'log';

        // Short circuit the getting of the system path.
        $pathSite = 'somePath';
        $this->setValueByReflection('varPath', $pathSite, Environment::class);

        $typo3Namespace = '\\Brainworxx\\Includekrexx\\Plugins\\Typo3\\';

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock($typo3Namespace, 'is_dir');
        $isDirMock->expects($this->exactly(8))
            ->with(...$this->withConsecutive(
                [$pathSite . DIRECTORY_SEPARATOR .  static::TX_INCLUDEKREXX],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . $log],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'config'],
                // Run a second time
                [$pathSite . DIRECTORY_SEPARATOR .  static::TX_INCLUDEKREXX],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . $log],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'config']
            ))->willReturn(false);

        $mkDir = $this->getFunctionMock('\\TYPO3\\CMS\\Core\\Utility\\', 'mkDir');
        $mkDir->expects($this->any())->willReturn(false);
        $pathinfo = $this->getFunctionMock('\\TYPO3\\CMS\\Core\\Utility\\', 'pathinfo');
        $pathinfo->expects($this->any())->willReturn(['dirname' => '', 'basename' => false]);

        // Simulating the package
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');

        $arrayReplaceRecursiveMock = $this->getFunctionMock(
            $typo3Namespace,
            'array_replace_recursive'
        );

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 13) {
            $logClass = Log14::class;
        } else {
            $logClass = Log::class;
        }

        $arrayReplaceRecursiveMock->expects($this->any())
            ->with($this->anything(), [Configuration::KREXX => ['module' => $logClass, 'before' => [$log]]]);
        // You just have to love these large arrays inside the globals.
        $GLOBALS[Configuration::TYPO3_CONF_VARS][Configuration::EXTCONF]
            [Configuration::ADMIN_PANEL][Configuration::MODULES][Configuration::DEBUG]
            [Configuration::SUBMODULES] = ['module' => $logClass, 'after' => [$log]];

        $this->configuration->exec();

        $this->assertEquals(
            [
                ProcessObject::class . Configuration::START_PROCESS => [DirtyModels::class => DirtyModels::class],
                Objects::class . Configuration::START_EVENT => [QueryDebugger::class => QueryDebugger::class],
                Xml::class . Configuration::END_EVENT => [FlexFormParser::class => FlexFormParser::class],
                EditSettingsController::class . '::outputCssAndJs' => [InlineJsCssDispatcher::class => InlineJsCssDispatcher::class],
                ExceptionController::class . '::outputCssAndJs' => [InlineJsCssDispatcher::class => InlineJsCssDispatcher::class],
                BacktraceController::class . '::outputCssAndJs' => [InlineJsCssDispatcher::class => InlineJsCssDispatcher::class],
                DumpController::class . '::outputCssAndJs' => [InlineJsCssDispatcher::class => InlineJsCssDispatcher::class],
                FluidObjects::class . '::callMe::start' => [QueryDebugger::class => QueryDebugger::class],
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

        if (class_exists(ObjectManager::class)) {
            $this->assertEmpty(
                SettingsGetter::getNewFallbackValues(),
                'An ObjectManager means TYPO3 10 or 11.'
            );
        } else {
            $this->assertEquals(Configuration::VALUE_BROWSER_IMMEDIATELY,
                SettingsGetter::getNewFallbackValues()[Configuration::SETTING_DESTINATION],
                'Test if we registerd the new standard output method.'
            );
        }

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
