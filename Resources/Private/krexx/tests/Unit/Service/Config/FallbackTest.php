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

namespace Brainworxx\Krexx\Tests\Unit\Service\Config;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Plugin\NewSetting;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\View\Skins\RenderHans;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Fallback::class, 'generateFeConfigFallback')]
#[CoversMethod(Fallback::class, '__construct')]
#[CoversMethod(Fallback::class, 'returnBoolSelectFalse')]
#[CoversMethod(Fallback::class, 'returnBoolSelectTrue')]
#[CoversMethod(Fallback::class, 'returnDebugMethods')]
#[CoversMethod(Fallback::class, 'returnDestination')]
#[CoversMethod(Fallback::class, 'returnInput')]
#[CoversMethod(Fallback::class, 'returnIpRange')]
#[CoversMethod(Fallback::class, 'returnMaxFiles')]
#[CoversMethod(Fallback::class, 'returnMaxRuntime')]
#[CoversMethod(Fallback::class, 'returnSkin')]
#[CoversMethod(Fallback::class, 'generateSkinConfiguration')]
#[CoversMethod(Fallback::class, 'generateConfigFallback')]
#[CoversMethod(Fallback::class, 'generatePluginConfig')]
#[CoversMethod(Fallback::class, 'returnLanguages')]
class FallbackTest extends AbstractHelper
{
    /**
     * Test the construct of an abstract class. Sounds about right.
     */
    public function testConstruct()
    {
        Registration::registerAdditionalskin('Unit Test Skin', 'UnitRenderer', '/dev/null');
        $config = new Config(Krexx::$pool);

        // Test the setting of the pool
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $config));

        // Test the reading of the skin values.
        $expectedSkinConfig = [
            'smokygrey' => [
                $config::SKIN_CLASS => RenderSmokyGrey::class,
                $config::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/smokygrey/'
            ],
            'hans' => [
                $config::SKIN_CLASS => RenderHans::class,
                $config::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/hans/'
            ],
            'Unit Test Skin' => [
                $config::SKIN_CLASS => 'UnitRenderer',
                $config::SKIN_DIRECTORY => '/dev/null'
            ]
        ];
        $this->assertSame($expectedSkinConfig, $this->retrieveValueByReflection('skinConfiguration', $config));
    }

    /**
     * Test the assigning of custom settings
     */
    public function testConstructWithCustomSettings()
    {
        $settingName = 'customName';
        $sectionName = 'customSection';

        $customSetting = new NewSetting();
        $customSetting->setName($settingName)
            ->setValidation('evalBool')
            ->setSection($sectionName)
            ->setRenderType(NewSetting::RENDER_TYPE_SELECT)
            ->setIsEditable(true)
            ->setDefaultValue('true')
            ->setIsFeProtected(false);

        Registration::addNewSettings($customSetting);
        $config = new Config(Krexx::$pool);

        $this->assertSame(true, $config->getSetting($settingName), 'Test the internal getter.');

        $settingsModel = $config->settings[$settingName];

        $this->assertSame($sectionName, $settingsModel->getSection());
        $this->assertSame(true, $settingsModel->getValue());
        $this->assertSame(NewSetting::RENDER_TYPE_SELECT, $settingsModel->getType());
        $this->assertSame(true, $settingsModel->isEditable());
        $this->assertSame('Factory settings', $settingsModel->getSource());
    }
}
