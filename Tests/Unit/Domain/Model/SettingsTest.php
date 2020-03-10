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

namespace Brainworxx\Includekrexx\Tests\Unit\Domain\Model;

use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Validation;

class SettingsTest extends AbstractTest
{

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
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setFactory
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
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setIprange
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setLevel
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxCall
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxfiles
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxRuntime
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMaxStepNumber
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setMemoryLeft
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setSkin
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::setUseScopeAnalysis
     *
     * There is a point where we needed to stop and we have clearly passed it
     * but let's keep going and see what happens.
     */
    public function testItAll()
    {
        $settingsModel = new Settings();
        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(21))
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
                $settingsModel->{'setForm' . $settingName}(Fallback::RENDER_TYPE_INI_FULL);
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
            Fallback::SECTION_FE_EDITING => [
                // Skin is missing here, because we gave it an invalid value.
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_PROTECTED => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_PRIVATE => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_SCALAR => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_TRAVERSABLE => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_NESTING_LEVEL => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_MAX_CALL => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_DISABLED => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_DETECT_AJAX => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ANALYSE_GETTER => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_MEMORY_LEFT => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_MAX_RUNTIME => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_USE_SCOPE_ANALYSIS => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_MAX_STEP_NUMBER => Fallback::RENDER_TYPE_INI_FULL,
                Fallback::SETTING_ARRAY_COUNT_LIMIT => Fallback::RENDER_TYPE_INI_FULL,
            ],
        ];

        $this->assertEquals($expectation, parse_ini_string($settingsModel->generateIniContent(), true));
    }
}
