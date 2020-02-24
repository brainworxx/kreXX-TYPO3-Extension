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

namespace Brainworxx\Includekrexx\Tests\Unit\Collectors;

use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Service\Config\Config;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class ConfigurationTest extends AbstractTest
{
    /**
     * The the assigning of data to the view.
     *
     * @covers \Brainworxx\Includekrexx\Collectors\Configuration::assignData
     * @covers \Brainworxx\Includekrexx\Collectors\Configuration::retrieveConfiguration
     * @covers \Brainworxx\Includekrexx\Collectors\Configuration::retrieveDropDowns
     */
    public function testAssignData()
    {
        // No access.
        $configuration = new Configuration();
        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->never())
            ->method('assign');
        $configuration->assignData($viewMock);

        // Normal access.
        $configuration = new Configuration();
        $this->setValueByReflection('hasAccess', true, $configuration);
        // Point the ini reader to the fixture.
        $this->setValueByReflection(
            'directories',
            [Config::CONFIG_FOLDER =>__DIR__ . '/../../Fixtures/Config.ini'],
            \Krexx::$pool->config
        );

        // 'Mock' the backend users uc values.
        $this->setValueByReflection('userUc', [Config::SETTING_MAX_FILES => '1000'], $configuration);

        // Mock the view.
        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->exactly(2))
            ->method('assign')
            ->withConsecutive(
                [
                    'config',
                    $this->callback(function ($config) {
                        // @see config.ini in the fixtures.
                        return $config[Config::SETTING_SKIN][Configuration::SETTINGS_VALUE] === 'hans' &&
                            $config[Config::SETTING_SKIN][Configuration::SETTINGS_USE_FACTORY_SETTINGS] === false &&
                            $config[Config::SETTING_IP_RANGE][Configuration::SETTINGS_VALUE] === 'testing . . .' &&
                            $config[Config::SETTING_MAX_FILES][Configuration::SETTINGS_VALUE] === '1000' &&
                            $config[Config::SETTING_MAX_FILES][Configuration::SETTINGS_USE_FACTORY_SETTINGS] === true;
                    })
                ],
                [
                    'dropdown',
                    $this->callback(function ($dropdown) {
                        // Test the important array keys.
                        return isset($dropdown['skins']) && isset($dropdown['destination']) && isset($dropdown['bool']);
                    })
                ]
            );
        $configuration->assignData($viewMock);
    }
}
