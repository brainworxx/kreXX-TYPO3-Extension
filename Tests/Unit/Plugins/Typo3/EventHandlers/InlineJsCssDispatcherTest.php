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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\EventHandlers;

use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use TYPO3\CMS\Core\Page\AssetCollector;

class InlineJsCssDispatcherTest extends AbstractHelper implements ConfigConstInterface
{
    /**
     * Test the assigning of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher::__construct
     */
    public function testConstuct()
    {
        $debugMethod = new InlineJsCssDispatcher(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $debugMethod));
    }

    /**
     * Test with logging on.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher::handle
     */
    public function testHandleLogging()
    {
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSetting')
            ->with(static::SETTING_DESTINATION)
            ->willReturn(static::VALUE_FILE);
        $pool = \Krexx::$pool;
        $pool->config = $configMock;

        // We expect nothing.
        $collectorMock = $this->createMock(AssetCollector::class);
        $collectorMock->expects($this->never())
            ->method('addInlineJavaScript');
        $this->injectIntoGeneralUtility(AssetCollector::class, $collectorMock);

        $dispatcher = new InlineJsCssDispatcher($pool);
        $model = new Model($pool);
        $model->setData('console.log("barf");');
        $dispatcher->handle(null, $model);
    }

    /**
     * Test with normal output.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\InlineJsCssDispatcher::handle
     */
    public function testHandleNormal()
    {
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSetting')
            ->with(static::SETTING_DESTINATION)
            ->willReturn(static::VALUE_BROWSER);
        $pool = \Krexx::$pool;
        $pool->config = $configMock;

        $payload = 'console.log("stuff");';
        $collectorMock = $this->createMock(AssetCollector::class);
        $collectorMock->expects($this->once())
            ->method('addInlineJavaScript')
            ->with(
                'krexxDomTools',
                '(function(){' . $payload . '})();',
                [],
                ['priority' => false, 'useNonce' => true]
            );
        $this->injectIntoGeneralUtility(AssetCollector::class, $collectorMock);

        $dispatcher = new InlineJsCssDispatcher($pool);
        $model = new Model($pool);
        $model->setData($payload);
        $dispatcher->handle(null, $model);
    }

    public function testHandleEmpty()
    {
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getSetting')
            ->with(static::SETTING_DESTINATION)
            ->willReturn(static::VALUE_BROWSER);
        $pool = \Krexx::$pool;
        $pool->config = $configMock;

        // We expect nothing.
        $collectorMock = $this->createMock(AssetCollector::class);
        $collectorMock->expects($this->never())
            ->method('addInlineJavaScript');
        $this->injectIntoGeneralUtility(AssetCollector::class, $collectorMock);

        $dispatcher = new InlineJsCssDispatcher($pool);
        $model = new Model($pool);
        $model->setData('');
        $dispatcher->handle(null, $model);
    }
}
