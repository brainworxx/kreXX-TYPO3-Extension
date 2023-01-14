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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\PluginConfiguration;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;

class FooterTest extends AbstractRenderHans
{
    /**
     * Test the rendering of the footer.
     *
     * We test the renderExpandableChild separately to keep this one at least
     * a little bit sane.
     *
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Footer::renderFooter
     * @covers \Brainworxx\Krexx\View\Skins\Hans\ExpandableChild::renderExpandableChild
     * @covers \Brainworxx\Krexx\View\Skins\Hans\Footer::renderCaller
     * @covers \Brainworxx\Krexx\View\Skins\Hans\PluginList::renderPluginList
     */
    public function testRenderFooter()
    {
        // Mock the caller
        $caller = [
            $this->renderHans::TRACE_FILE => 'filename',
            $this->renderHans::TRACE_LINE => 'line 123',
            $this->renderHans::TRACE_DATE => 'yesteryear',
            $this->renderHans::TRACE_URL => 'https://www.google.biz',
        ];
        Krexx::$pool->fileService->expects($this->any())
            ->method('filterFilePath')
            ->will($this->returnValue(''));
        Krexx::$pool->fileService->expects($this->any())
            ->method('fileIsReadable')
            ->will($this->returnValue(true));

        // Mock the model for the renderExpandableChild, which we will not test
        // here.
        $model = new Model(Krexx::$pool);

        $configMock1 = $this->createMock(PluginConfiguration::class);
        $configMock1->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Plugin 1'));
        $configMock1->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0.'));
        $configMock2 = $this->createMock(PluginConfiguration::class);
        $configMock2->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Plugin 2'));
        $configMock2->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('2.0.0.'));
        $configMock3 = $this->createMock(PluginConfiguration::class);
        $configMock3->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Plugin 3'));
        $configMock3->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('3.0.0.'));

        // Mock the plugin list.
        $pluginList = [
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::CONFIG_CLASS => $configMock1
            ],
            [
                SettingsGetter::IS_ACTIVE => false,
                SettingsGetter::CONFIG_CLASS => $configMock2
            ],
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::CONFIG_CLASS => $configMock3
            ]
        ];
        $this->setValueByReflection('plugins', $pluginList, SettingsGetter::class);

        $result = $this->renderHans->renderFooter($caller, $model);
        $this->assertStringContainsString('Plugin 1', $result);
        $this->assertStringContainsString('1.0.0.', $result);
        $this->assertStringContainsString('Plugin 2', $result);
        $this->assertStringContainsString('2.0.0.', $result);
        $this->assertStringContainsString('Plugin 3', $result);
        $this->assertStringContainsString('3.0.0.', $result);
        $this->assertStringContainsString('kisactive', $result);
        $this->assertStringContainsString('kisinactive', $result);
        $this->assertStringContainsString('active', $result);
        $this->assertStringContainsString('inactive', $result);
        $this->assertStringContainsString('filename', $result);
        $this->assertStringContainsString('line 123', $result);
        $this->assertStringContainsString('yesteryear', $result);
    }
}
