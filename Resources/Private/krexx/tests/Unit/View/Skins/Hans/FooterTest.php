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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;

class FooterTest extends AbstractRenderHans
{
    /**
     * Test the rendering of the footer.
     *
     * We test the renderExpandableChild separately to keep this one al least
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
        Krexx::$pool->fileService->expects($this->once())
            ->method('readFile')
            ->will($this->returnValue(''));
        Krexx::$pool->fileService->expects($this->any())
            ->method('filterFilePath')
            ->will($this->returnValue(''));
        Krexx::$pool->fileService->expects($this->exactly(2))
            ->method('fileIsReadable')
            ->will($this->returnValue(true));

        // Mock the model for the renderExpandableChild, which we will not test
        // here.
        $model = new Model(Krexx::$pool);

        // Mock the plugin list.
        $pluginList = [
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::PLUGIN_NAME => 'Plugin 1',
                SettingsGetter::PLUGIN_VERSION => '1.0.0.',
            ],
            [
                SettingsGetter::IS_ACTIVE => false,
                SettingsGetter::PLUGIN_NAME => 'Plugin 2',
                SettingsGetter::PLUGIN_VERSION => '2.0.0.',
            ],
            [
                SettingsGetter::IS_ACTIVE => true,
                SettingsGetter::PLUGIN_NAME => 'Plugin 3',
                SettingsGetter::PLUGIN_VERSION => '3.0.0.',
            ]
        ];
        $this->setValueByReflection('plugins', $pluginList, SettingsGetter::class);

        $result = $this->renderHans->renderFooter($caller, $model);
        $this->assertContains('Plugin 1', $result);
        $this->assertContains('1.0.0.', $result);
        $this->assertContains('Plugin 2', $result);
        $this->assertContains('2.0.0.', $result);
        $this->assertContains('Plugin 3', $result);
        $this->assertContains('3.0.0.', $result);
        $this->assertContains('kisactive', $result);
        $this->assertContains('kisinactive', $result);
        $this->assertContains('active', $result);
        $this->assertContains('inactive', $result);
        $this->assertContains('filename', $result);
        $this->assertContains('line 123', $result);
        $this->assertContains('yesteryear', $result);
    }
}
