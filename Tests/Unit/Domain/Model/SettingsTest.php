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

namespace Brainworxx\Includekrexx\Tests\Unit\Domain\Model;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\MetaData;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Settings::class, 'generateContent')]
#[CoversMethod(Settings::class, 'processGroups')]
#[CoversMethod(Settings::class, 'processFeEditing')]
#[CoversMethod(Settings::class, 'prepareFileName')]
#[CoversMethod(Settings::class, 'setFactory')]
#[CoversMethod(Settings::class, '__construct')]
class SettingsTest extends AbstractHelper implements ConstInterface
{
    protected const REVERSE_PROXY = 'reverseProxyIP';

    protected const TYPO3_TEMP = 'typo3temp';

    public function setUp(): void
    {
        parent::setUp();

        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY] = '';
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]);
    }

    /**
     * The things you do, to test some simple getter . . .
     */
    protected function prepareConfigToRun()
    {
        // Short circuit the getting of the system path.
        $pathSite = 'somePath';
        $this->setValueByReflection('varPath', $pathSite, Environment::class);
        $typo3Namespace = '\Brainworxx\\Includekrexx\\Plugins\\Typo3\\';

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock($typo3Namespace, 'is_dir');
        $isDirMock->expects($this->exactly(4))
            ->with(...$this->withConsecutive(
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'log'],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'config']
            ))->willReturn(true);

        // Simulating the package
        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->any())
            ->method('getVersion')
            ->willReturn(AbstractHelper::TYPO3_VERSION);
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/')
            ->expects($this->any())
            ->method('getPackageMetaData')
            ->willReturn($metaData);
    }

    /**
     * There are no getter implemented. Hence, we set a value for each property
     * and then test the ini.
     *
     * There is a point where we needed to stop and we have clearly passed it
     * but let's keep going and see what happens.
     */
    public function testItAll()
    {
        $this->prepareConfigToRun();
        // The TYPOO3 krexx plugin brings two new settings with it.
        $t3configuration = new T3configuration();
        Registration::register($t3configuration);
        Registration::activatePlugin(T3configuration::class);
        Krexx::$pool = null;
        Pool::createPool();

        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(19))
            ->method('evaluateSetting')
            ->willReturn(true);
        Krexx::$pool->config->validation = $validationMock;

        $this->mockBeUser();

        // Fill this one with teir own name as a value, so we can test it.
        $settingsModel = new Settings(
            analyseGetter:  Fallback::SETTING_ANALYSE_GETTER,
            formanalyseGetter: Fallback::RENDER_TYPE_CONFIG_FULL,
            analysePrivate: Fallback::SETTING_ANALYSE_PRIVATE,
            formanalysePrivate: Fallback::RENDER_TYPE_CONFIG_FULL,
            analysePrivateMethods: Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
            formanalysePrivateMethods: Fallback::RENDER_TYPE_CONFIG_FULL,
            analyseProtected: Fallback::SETTING_ANALYSE_PROTECTED,
            formanalyseProtected: Fallback::RENDER_TYPE_CONFIG_FULL,
            analyseProtectedMethods: Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
            formanalyseProtectedMethods: Fallback::RENDER_TYPE_CONFIG_FULL,
            analyseTraversable: Fallback::SETTING_ANALYSE_TRAVERSABLE,
            formanalyseTraversable: Fallback::RENDER_TYPE_CONFIG_FULL,
            arrayCountLimit: Fallback::SETTING_ARRAY_COUNT_LIMIT,
            formarrayCountLimit: Fallback::RENDER_TYPE_CONFIG_FULL,
            debugMethods: Fallback::SETTING_DEBUG_METHODS,
            formdebugMethods: Fallback::RENDER_TYPE_CONFIG_FULL,
            destination: Fallback::SETTING_DESTINATION,
            formdestination: Fallback::RENDER_TYPE_CONFIG_FULL,
            disabled: Fallback::SETTING_DISABLED,
            formdisabled: Fallback::RENDER_TYPE_CONFIG_FULL,
            iprange: Fallback::SETTING_IP_RANGE,
            formiprange: Fallback::RENDER_TYPE_CONFIG_FULL,
            languageKey: Fallback::SETTING_LANGUAGE_KEY,
            formlanguageKey: Fallback::RENDER_TYPE_CONFIG_FULL,
            level: Fallback::SETTING_NESTING_LEVEL,
            formlevel: Fallback::RENDER_TYPE_CONFIG_FULL,
            activateT3FileWriter: static::ACTIVATE_T3_FILE_WRITER,
            loglevelT3FileWriter: static::LOG_LEVEL_T3_FILE_WRITER,
            maxCall: Fallback::SETTING_MAX_CALL,
            formmaxCall: Fallback::RENDER_TYPE_CONFIG_FULL,
            maxfiles: Fallback::SETTING_MAX_FILES,
            formmaxfiles: Fallback::RENDER_TYPE_CONFIG_FULL,
            maxStepNumber: Fallback::SETTING_MAX_STEP_NUMBER,
            formmaxStepNumber: Fallback::RENDER_TYPE_CONFIG_FULL,
            skin: Fallback::SETTING_SKIN,
            formskin: 'blargh', // Invalid value, so we can test the fallback.
        );

        $expectation = [
            Fallback::SECTION_OUTPUT => [
                Fallback::SETTING_DISABLED => Fallback::SETTING_DISABLED,
                Fallback::SETTING_IP_RANGE => Fallback::SETTING_IP_RANGE,
            ],
            Fallback::SECTION_BEHAVIOR => [
                Fallback::SETTING_SKIN => Fallback::SETTING_SKIN,
                Fallback::SETTING_DESTINATION => Fallback::SETTING_DESTINATION,
                Fallback::SETTING_MAX_FILES => Fallback::SETTING_MAX_FILES,
                Fallback::SETTING_LANGUAGE_KEY => Fallback::SETTING_LANGUAGE_KEY,
            ],
            Fallback::SECTION_PRUNE => [
                Fallback::SETTING_MAX_STEP_NUMBER => Fallback::SETTING_MAX_STEP_NUMBER,
                Fallback::SETTING_ARRAY_COUNT_LIMIT => Fallback::SETTING_ARRAY_COUNT_LIMIT,
                Fallback::SETTING_NESTING_LEVEL => Fallback::SETTING_NESTING_LEVEL,
                Fallback::SETTING_MAX_CALL => Fallback::SETTING_MAX_CALL,
            ],
            Fallback::SECTION_PROPERTIES => [
                Fallback::SETTING_ANALYSE_PROTECTED => Fallback::SETTING_ANALYSE_PROTECTED,
                Fallback::SETTING_ANALYSE_PRIVATE => Fallback::SETTING_ANALYSE_PRIVATE,
                Fallback::SETTING_ANALYSE_TRAVERSABLE => Fallback::SETTING_ANALYSE_TRAVERSABLE,
            ],
            Fallback::SECTION_METHODS => [
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS => Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS => Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
                Fallback::SETTING_ANALYSE_GETTER => Fallback::SETTING_ANALYSE_GETTER,
                Fallback::SETTING_DEBUG_METHODS => Fallback::SETTING_DEBUG_METHODS,
            ],
            $t3configuration->getName() => [
                static::ACTIVATE_T3_FILE_WRITER => static::ACTIVATE_T3_FILE_WRITER,
                static::LOG_LEVEL_T3_FILE_WRITER => static::LOG_LEVEL_T3_FILE_WRITER
            ],
            Fallback::SECTION_FE_EDITING => [
                // Skin is missing here, because we gave it an invalid value.
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_PROTECTED => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_PRIVATE => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_TRAVERSABLE => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_NESTING_LEVEL => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_CALL => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_DISABLED => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_GETTER => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_STEP_NUMBER => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ARRAY_COUNT_LIMIT => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_LANGUAGE_KEY => Fallback::RENDER_TYPE_CONFIG_FULL,
                // We also expect these to render as "full", because we mocked the
                // validation class, which validates everything as ok.
                Fallback::SETTING_DEBUG_METHODS => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_DESTINATION => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_FILES => Fallback::RENDER_TYPE_CONFIG_FULL,
            ],
        ];

        $this->assertEquals($expectation, json_decode($settingsModel->generateContent(), true));
    }

    /**
     * Test the factory settings.
     */
    public function testSetFactory()
    {
        $settingsModel = new Settings();
        $settingsModel->setFactory('faqTory');

        $this->assertEquals('faqTory', $this->retrieveValueByReflection('factory', $settingsModel));
    }

    /**
     * Test the ini migration to json.
     */
    public function testPrepareFileName()
    {
        $path = DIRECTORY_SEPARATOR . 'just' . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'Krexx';

        $fileExistsMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Domain\\Model', 'file_exists');
        $fileExistsMock->expects($this->once())
            ->with($path . '.ini')
            ->willReturn(true);
        $unLinkMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Domain\\Model', 'unlink');
        $unLinkMock->expects($this->once())
            ->with($path . '.ini');

        $settingsModel = new Settings();
        $this->assertEquals($path . '.json', $settingsModel->prepareFileName($path));
    }
}
