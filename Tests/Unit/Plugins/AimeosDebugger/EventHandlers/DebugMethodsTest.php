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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

use Aimeos\Map;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\DebugMethods;
use Brainworxx\Includekrexx\Tests\Fixtures\Aimeos20Item;
use Brainworxx\Includekrexx\Tests\Fixtures\Aimeos24Item;
use Brainworxx\Includekrexx\Tests\Fixtures\AimeosItem;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DebugMethodFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods as AnalyseDebugMethods;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Aimeos\MShop\Product\Item\Standard as StandardProduct;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(DebugMethods::class, 'handle')]
#[CoversMethod(DebugMethods::class, 'callDebugMethod')]
#[CoversMethod(DebugMethods::class, 'retrieveParameters')]
#[CoversMethod(DebugMethods::class, '__construct')]
class DebugMethodsTest extends AbstractHelper implements CallbackConstInterface
{
    use AimeosTestTrait;

    /**
     * Subscribing our class to test to the right event.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Subscribing.
        Registration::registerEvent(
            AnalyseDebugMethods::class . PluginConfigInterface::START_EVENT,
            DebugMethods::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);
    }

    /**
     * Test the assigning of the pool.
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $debugMethod = new DebugMethods(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $debugMethod));
    }

    /**
     * Test the subscribing and then handling of the event, with the wrong object.
     */
    public function testHandleWrongObject()
    {
        $this->skipIfAimeosIsNotInstalled();

        $this->mockEmergencyHandler();

        // Create the calling class an a fixture.
        $analyseDebugMethods = new AnalyseDebugMethods(\Krexx::$pool);
        $object = new DebugMethodFixture();
        $fixture = [
            static::PARAM_DATA => new DebugMethodFixture(),
            static::PARAM_NAME => 'noOutputHere',
            static::PARAM_REF => new ReflectionClass($object)
        ];
        $analyseDebugMethods->setParameters($fixture);

        // Trigger the event.
        $this->assertEmpty(
            $this->triggerStartEvent($analyseDebugMethods),
            'This is not an Aimeos object, hence we should get no output here.'
        );
    }

    /**
     * Test the subscribing and then handling of the event, with the right object.
     */
    public function testHandleNormal()
    {
        $this->skipIfAimeosIsNotInstalled();

        $this->mockEmergencyHandler();

        // Short circuit the rendering process.
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);

        // Create the calling class in a fixture.
        $analyseDebugMethods = new AnalyseDebugMethods(\Krexx::$pool);
        $itemReflection = new ReflectionClass(StandardProduct::class);
        $parameters = $itemReflection->getMethod('__construct')->getParameters();
        if (count($parameters) === 2) {
            $object = new Aimeos24Item();
        } elseif (class_exists(Map::class)) {
            $object = new Aimeos20Item();
        } else {
            $object = new AimeosItem();
        }

        $fixture = [
            static::PARAM_DATA => new DebugMethodFixture(),
            static::PARAM_NAME => 'noOutputHere',
            static::PARAM_REF => new ReflectionClass($object)
        ];
        $analyseDebugMethods->setParameters($fixture);

        // Trigger the event.
        $this->triggerStartEvent($analyseDebugMethods);

        // Get the results.
        $models = Krexx::$pool->render->model['renderExpandableChild'];

        if (class_exists(Map::class)) {
            // Aimeos 2020+
            $mapping = ['getRefItems', 'getPropertyItems', 'getListItems'];
            $connectorRight = [
                '($domain = NULL, $listtype = NULL, $type = NULL, bool $active = TRUE)',
                '($type = NULL, bool $active = TRUE)',
                '($domain = NULL, $listtype = NULL, $type = NULL, bool $active = TRUE)'
            ];
        } else {
            // Aimeos 2019 and below
            $mapping = ['getRefItems', 'getListItems'];
            $connectorRight = [
                '($domain = NULL, $type = NULL, $listtype = NULL, $active = TRUE)',
                '($domain = NULL, $listtype = NULL, $type = NULL, $active = TRUE)'
            ];
        }



        // Testing the standard values.
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        foreach ($models as $key => $model) {
            $this->assertEquals('Debug method', $model->getType());
            $this->assertEquals(static::UNKNOWN_VALUE, $model->getNormal());
            $this->assertEquals('->', $model->getConnectorLeft());
            $this->assertEquals($connectorRight[$key], $model->getConnectorRight(), 'Method: ' . $mapping[$key]);
            $this->assertEquals($mapping[$key], $model->getName(), 'Key is: ' . $key);
            if ($mapping[$key] === 'getPropertyItems') {
                $this->assertNull($model->getParameters()[static::PARAM_DATA][0], 'This data does not exist in the fixture.');
                $this->assertNull($model->getParameters()[static::PARAM_DATA][1], 'This data does not exist in the fixture.');
            } else {
                $this->assertInstanceOf(\StdClass::class, $model->getParameters()[static::PARAM_DATA][0]);
                $this->assertInstanceOf(\DateTime::class, $model->getParameters()[static::PARAM_DATA][1]);
            }
        }
    }
}
