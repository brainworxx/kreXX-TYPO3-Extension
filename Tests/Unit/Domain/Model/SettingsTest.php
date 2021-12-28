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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Includekrexx\Plugins\Typo3\Configuration as T3configuration;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\MetaData;

class SettingsTest extends AbstractTest implements ConstInterface
{

    const REVERSE_PROXY = 'reverseProxyIP';

    protected const TYPO3_TEMP = 'typo3temp';

    public function krexxUp()
    {
        parent::krexxUp();

        if (isset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]) === false) {
            $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY] = '';
        }
    }

    public function krexxDown()
    {
        parent::krexxDown();

        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY]);
    }

    /**
     * The things you do, to test some simple getter . . .
     */
    protected function prepareConfigToRun()
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
        $classExistsMock->expects($this->exactly(1))
            ->with(Environment::class)
            ->will($this->returnValue(false));

        // Mock the is_dir method. We will not create any files.
        $isDirMock = $this->getFunctionMock($typo3Namespace, 'is_dir');
        $isDirMock->expects($this->exactly(4))
            ->withConsecutive(
                [$pathSite . static::TYPO3_TEMP  . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX],
                [$pathSite . static::TYPO3_TEMP . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'log'],
                [$pathSite . static::TYPO3_TEMP . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'chunks'],
                [$pathSite . static::TYPO3_TEMP . DIRECTORY_SEPARATOR . static::TX_INCLUDEKREXX . DIRECTORY_SEPARATOR . 'config']
            )
            ->will($this->returnValue(true));

        // Simulating the package
        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue(AbstractTest::TYPO3_VERSION));
        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/')
            ->expects($this->once())
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaData));
    }

    /**
     * There are no getter implemented. Hence, we set a value for each property
     * and then test the ini.
     *
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::generateIniContent
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::processGroups
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::processFeEditing
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalyseGetter
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalysePrivate
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalysePrivateMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalyseProtected
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalyseProtectedMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalyseTraversable
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setArrayCountLimit
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setDebugMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setDestination
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setDetectAjax
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setAnalyseScalar
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setDisabled
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalyseGetter
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalysePrivate
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalysePrivateMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalyseProtected
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalyseProtectedMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalyseTraversable
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormarrayCountLimit
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormdebugMethods
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormdestination
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormdetectAjax
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormdisabled
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormiprange
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormlevel
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormmaxCall
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormmaxfiles
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormmaxRuntime
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormmaxStepNumber
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormmemoryLeft
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormskin
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormuseScopeAnalysis
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormanalyseScalar
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setIprange
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setLevel
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxCall
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxfiles
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxRuntime
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxStepNumber
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMemoryLeft
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setSkin
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setUseScopeAnalysis
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setActivateT3FileWriter
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setLoglevelT3FileWriter
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormactivateT3FileWriter
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFormloglevelT3FileWriter
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

        $settingsModel = new Settings();
        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(23))
            ->method('evaluateSetting')
            ->will($this->returnValue(true));
        Krexx::$pool->config->validation = $validationMock;

        $this->mockBeUser();

        // Fill this one, according to the fallback settings.
        foreach (Krexx::$pool->config->configFallback as $settings) {
            foreach ($settings as $settingName) {
                $settingsModel->{'set' . $settingName}($settingName);
            }
        }
        foreach (Krexx::$pool->config->feConfigFallback as $settingName => $settings) {
            if ($settingName === 'devHandle') {
                // This one is not part of the settings file.
                continue;
            }
            if ($settingName === 'skin') {
                // We let this one fail on purpose.
                $settingsModel->{'setForm' . $settingName}('blargh');
            } else {
                $settingsModel->{'setForm' . $settingName}(Fallback::RENDER_TYPE_CONFIG_FULL);
            }
        }

        $expectation = [
            Fallback::SECTION_OUTPUT => [
                Fallback::SETTING_DISABLED => Fallback::SETTING_DISABLED,
                Fallback::SETTING_IP_RANGE => Fallback::SETTING_IP_RANGE,
                Fallback::SETTING_DETECT_AJAX => Fallback::SETTING_DETECT_AJAX,
            ],
            Fallback::SECTION_BEHAVIOR => [
                Fallback::SETTING_SKIN => Fallback::SETTING_SKIN,
                Fallback::SETTING_DESTINATION => Fallback::SETTING_DESTINATION,
                Fallback::SETTING_MAX_FILES => Fallback::SETTING_MAX_FILES,
                Fallback::SETTING_USE_SCOPE_ANALYSIS => Fallback::SETTING_USE_SCOPE_ANALYSIS,
            ],
            Fallback::SECTION_PRUNE => [
                Fallback::SETTING_MAX_STEP_NUMBER => Fallback::SETTING_MAX_STEP_NUMBER,
                Fallback::SETTING_ARRAY_COUNT_LIMIT => Fallback::SETTING_ARRAY_COUNT_LIMIT,
                Fallback::SETTING_NESTING_LEVEL => Fallback::SETTING_NESTING_LEVEL,
            ],
            Fallback::SECTION_PROPERTIES => [
                Fallback::SETTING_ANALYSE_PROTECTED => Fallback::SETTING_ANALYSE_PROTECTED,
                Fallback::SETTING_ANALYSE_PRIVATE => Fallback::SETTING_ANALYSE_PRIVATE,
                Fallback::SETTING_ANALYSE_SCALAR => Fallback::SETTING_ANALYSE_SCALAR,
                Fallback::SETTING_ANALYSE_TRAVERSABLE => Fallback::SETTING_ANALYSE_TRAVERSABLE,
            ],
            Fallback::SECTION_METHODS => [
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS => Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS => Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
                Fallback::SETTING_ANALYSE_GETTER => Fallback::SETTING_ANALYSE_GETTER,
                Fallback::SETTING_DEBUG_METHODS => Fallback::SETTING_DEBUG_METHODS,
            ],
            Fallback::SECTION_EMERGENCY => [
                Fallback::SETTING_MAX_CALL => Fallback::SETTING_MAX_CALL,
                Fallback::SETTING_MAX_RUNTIME => Fallback::SETTING_MAX_RUNTIME,
                Fallback::SETTING_MEMORY_LEFT => Fallback::SETTING_MEMORY_LEFT,
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
                Fallback::SETTING_ANALYSE_SCALAR => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_TRAVERSABLE => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_NESTING_LEVEL => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_CALL => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_DISABLED => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_DETECT_AJAX => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ANALYSE_GETTER => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MEMORY_LEFT => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_RUNTIME => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_USE_SCOPE_ANALYSIS => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_MAX_STEP_NUMBER => Fallback::RENDER_TYPE_CONFIG_FULL,
                Fallback::SETTING_ARRAY_COUNT_LIMIT => Fallback::RENDER_TYPE_CONFIG_FULL,
            ],
        ];

        $this->assertEquals($expectation, parse_ini_string($settingsModel->generateIniContent(), true));
    }

    /**
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFactory
     */
    public function testSetFactory()
    {
        $settingsModel = new Settings();
        $settingsModel->setFactory('faqTory');

        $this->assertEquals('faqTory', $this->retrieveValueByReflection('factory', $settingsModel));
    }
}
