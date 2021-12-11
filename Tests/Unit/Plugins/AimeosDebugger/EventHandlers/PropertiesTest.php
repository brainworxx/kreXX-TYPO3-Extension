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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Aimeos\MW\View\Standard as StandardView;

class PropertiesTest extends AbstractTest
{
    use AimeosTestTrait;

    /**
     * Test the handling of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::__construct
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $properties = new Properties(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $properties));
    }

    /**
     * Test the retrieval of the Aimeos magical properties.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::handle
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::extractValues
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::dumpTheMagic
     */
    public function testHandleItem()
    {
        $this->skipIfAimeosIsNotInstalled();

        $values = [
            'log.id' => '123456',
            'log.siteid' => '42',
            'log.editor' => 'me',
            'log.facility' => 'kreXX',
            'log.priority' => 'high',
            'log.message' => 'testing',
            'log.request' => 'please',
            'log.mtime' => 'today',
            'log.ctime' => 'yesterday',
        ];

        // Create a simple log item.
        $item = new \Aimeos\MAdmin\Log\Item\Standard($values);
        $dynamicValue = 'dynamicValue';
        $item->$dynamicValue = $dynamicValue;
        $fixture = [
            CallbackConstInterface::PARAM_DATA => $item,
            CallbackConstInterface::PARAM_REF => new ReflectionClass($item)
        ];

        // Prevent the further routing.
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Subscribing.
        Registration::registerEvent(
            PublicProperties::class . PluginConfigInterface::START_EVENT,
            Properties::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Run the test.
        $publicProperties = new PublicProperties(Krexx::$pool);
        $publicProperties->setParameters($fixture);
        $this->triggerStartEvent($publicProperties);

        // An asserting the results.
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = Krexx::$pool->routing->model;
        $count = 0;
        foreach ($values as $name => $data) {
            $this->assertEquals($name, $models[$count]->getName());
            $this->assertEquals($data, $models[$count]->getData());
            $this->assertEquals('->{\'', $models[$count]->getConnectorLeft());
            $this->assertEquals('\'}', $models[$count]->getConnectorRight());
            $this->assertEquals(CodegenConstInterface::CODEGEN_TYPE_PUBLIC, $models[$count]->getCodeGenType());
            ++$count;
        }

        // Now for the dynamic value.
        $this->assertEquals($dynamicValue, $models[$count]->getName());
        $this->assertEquals($dynamicValue, $models[$count]->getData());
        $this->assertEquals('->', $models[$count]->getConnectorLeft());
        $this->assertEquals('', $models[$count]->getConnectorRight());
        $this->assertEquals(CodegenConstInterface::CODEGEN_TYPE_PUBLIC, $models[$count]->getCodeGenType());
    }

    /**
     * Pretty much the same as the item handler, but with a view object.
     * And with a much lesser complexity, because the really deep stuff already
     * got themselves tested.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::handle
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::extractValues
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties::dumpTheMagic
     */
    public function testHandleView()
    {
        $view = new StandardView();
        $dynamicValue = 'dynamicValue';
        $view->$dynamicValue = $dynamicValue;
        $fixture = [
            CallbackConstInterface::PARAM_DATA => $view,
            CallbackConstInterface::PARAM_REF => new ReflectionClass($view)
        ];

        // Prevent the further routing.
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Subscribing.
        Registration::registerEvent(
            PublicProperties::class . PluginConfigInterface::START_EVENT,
            Properties::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Run the test.
        $publicProperties = new PublicProperties(Krexx::$pool);
        $publicProperties->setParameters($fixture);
        $this->triggerStartEvent($publicProperties);

        // An asserting the results.
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $model = Krexx::$pool->routing->model[0];

        $this->assertEquals($dynamicValue, $model->getName());
        $this->assertEquals($dynamicValue, $model->getData());
        $this->assertEquals('->', $model->getConnectorLeft());
        $this->assertEquals('', $model->getConnectorRight());
        $this->assertEquals(CodegenConstInterface::CODEGEN_TYPE_PUBLIC, $model->getCodeGenType());
    }
}
