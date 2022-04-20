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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Collectors;

use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;

class FormConfigurationTest extends AbstractTest
{
    /**
     * The the assigning of data to the view.
     *
     * @covers \Brainworxx\Includekrexx\Collectors\FormConfiguration::assignData
     * @covers \Brainworxx\Includekrexx\Collectors\FormConfiguration::generateSingleSetting
     * @covers \Brainworxx\Includekrexx\Collectors\FormConfiguration::convertKrexxFeSetting
     * @covers \Brainworxx\Includekrexx\Collectors\FormConfiguration::generateDropdown
     */
    public function testAssignData()
    {
        // No access.
        $configuration = new FormConfiguration();
        $viewMock = $this->createMock(AbstractTemplateView::class);
        $viewMock->expects($this->never())
            ->method('assign');
        $configuration->assignData($viewMock);

        // Normal access.
        $configuration = new FormConfiguration();
        $this->setValueByReflection('hasAccess', true, $configuration);
        // Point the ini reader to the fixture.
        $this->setValueByReflection(
            'directories',
            ['config' => __DIR__ . '/../../Fixtures/Config.'],
            \Krexx::$pool->config
        );

        $viewMock = $this->createMock(AbstractTemplateView::class);
        $viewMock->expects($this->once())
            ->method('assign')
            ->with(
                'formConfig',
                $this->callback(function ($config) {
                    // @see config.ini in the fixtures.
                    return
                        $config[Config::SETTING_SKIN]['value'] === Fallback::RENDER_TYPE_CONFIG_NONE &&
                        $config[Config::SETTING_SKIN]['options'] === [
                            Fallback::RENDER_TYPE_CONFIG_FULL => Fallback::RENDER_TYPE_CONFIG_FULL,
                            Fallback::RENDER_TYPE_CONFIG_DISPLAY => Fallback::RENDER_TYPE_CONFIG_DISPLAY,
                            Fallback::RENDER_TYPE_CONFIG_NONE => Fallback::RENDER_TYPE_CONFIG_NONE,
                        ];
                })
            );
        $configuration->assignData($viewMock);
    }
}