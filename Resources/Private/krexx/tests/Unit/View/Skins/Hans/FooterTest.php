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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\PluginConfiguration;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;
use Brainworxx\Krexx\View\Skins\Hans\ExpandableChild;
use Brainworxx\Krexx\View\Skins\Hans\Footer;
use Brainworxx\Krexx\View\Skins\Hans\PluginList;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Footer::class, 'renderFooter')]
#[CoversMethod(ExpandableChild::class, 'renderExpandableChild')]
#[CoversMethod(Footer::class, 'renderCaller')]
#[CoversMethod(PluginList::class, 'renderPluginList')]
class FooterTest extends AbstractRenderHans
{
    /**
     * Test the rendering of the footer.
     *
     * We test the renderExpandableChild separately to keep this one at least
     * a little bit sane.
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
            ->willReturn('');
        Krexx::$pool->fileService->expects($this->any())
            ->method('fileIsReadable')
            ->willReturn(true);

        // Mock the model for the renderExpandableChild, which we will not test
        // here.
        $model = new Model(Krexx::$pool);

        $configMock1 = $this->createMock(PluginConfiguration::class);
        $configMock1->expects($this->once())
            ->method('getName')
            ->willReturn('Plugin 1');
        $configMock1->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.0.0.');
        $configMock2 = $this->createMock(PluginConfiguration::class);
        $configMock2->expects($this->once())
            ->method('getName')
            ->willReturn('Plugin 2');
        $configMock2->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.0.0.');
        $configMock3 = $this->createMock(PluginConfiguration::class);
        $configMock3->expects($this->once())
            ->method('getName')
            ->willReturn('Plugin 3');
        $configMock3->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.0.0.');

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

    /**
     * Test everything with an empty caller array.
     */
    public function testRenderFooterNoCaller()
    {
        // Mock the caller
        $caller = [];
        Krexx::$pool->fileService->expects($this->any())
            ->method('filterFilePath')
            ->willReturn('');
        Krexx::$pool->fileService->expects($this->any())
            ->method('fileIsReadable')
            ->willReturn(true);

        $model = new Model(Krexx::$pool);
        $result = $this->renderHans->renderFooter($caller, $model);
        $this->assertStringNotContainsString('Called from,', $result, 'We do not have any caller info.');
    }
}
