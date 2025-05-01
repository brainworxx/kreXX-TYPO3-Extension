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

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Getter::class, 'handle')]
#[CoversMethod(Getter::class, 'assignResultsToModel')]
#[CoversMethod(Getter::class, 'retrieveValueArray')]
#[CoversMethod(AbstractEventHandler::class, 'retrieveProperty')]
#[CoversMethod(Getter::class, 'retrievePossibleKey')]
#[CoversMethod(Getter::class, '__construct')]
class GetterTest extends AbstractHelper
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
            ThroughGetter::class . '::retrievePropertyValue::resolving',
            Getter::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);
    }

    /**
     * Test the assigning of the pool.
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $getter = new Getter(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Test the analysis of something else.
     */
    public function testHandleEmpty()
    {
        $this->skipIfAimeosIsNotInstalled();
        $this->mockEmergencyHandler();
        $params = [];

        // We already have a result.
        $params[Getter::PARAM_ADDITIONAL][Getter::PARAM_NOTHING_FOUND] = false;
        $getter = new Getter(Krexx::$pool);
        $callback = new CallbackNothing(Krexx::$pool);
        $callback->setParameters($params);
        $this->assertEquals('', $getter->handle($callback), 'We already have a result.');

        // Test with an empty item.
        // Create a simple log item.
        $item = new \Aimeos\MAdmin\Log\Item\Standard([]);
        $ref = new ReflectionClass($item);
        $params[Getter::PARAM_ADDITIONAL][Getter::PARAM_NOTHING_FOUND] = true;
        $params[ThroughGetter::CURRENT_PREFIX] = 'get';
        $params[Getter::PARAM_REF] = $ref;
        $params[Getter::PARAM_ADDITIONAL][Getter::PARAM_REFLECTION_METHOD] = new \ReflectionMethod($item, 'getPriority');
        $callback = new CallbackNothing(Krexx::$pool);
        $callback->setParameters($params);
        /** @var Model $model */
        $model = \Krexx::$pool->createClass(Model::class);
        $getter->handle($callback, $model);
        $this->assertEquals(0, $model->getData(), '0 is the default value.');
    }

    /**
     * Test the analysis of an Aimeos item.
     */
    public function testHandle()
    {
        $this->skipIfAimeosIsNotInstalled();
        $this->mockEmergencyHandler();

        $values = [
            // Base class (bdata)
            'log.id' => '123456',
            'log.siteid' => '42',
            'log.mtime' => 'today',
            'log.ctime' => 'yesterday',
            'log.timestamp' => null,
            // Standard class (values)
            'log.facility' => 'kreXX',
            // Funfact: The priority should be a number, but we use a string here.
            // evilGrin();
            'log.priority' => 'high',
            'log.message' => 'testing',
            'log.request' => 'please'
        ];

        // Create a simple log item.
        $item = new \Aimeos\MAdmin\Log\Item\Standard($values);
        $ref = new ReflectionClass($item);

        // Create the fixture.
        $fixture = [
            'normalGetter' => [
                new \ReflectionMethod($item, 'getFacility'),
                new \ReflectionMethod($item, 'getTimestamp'),
                new \ReflectionMethod($item, 'getPriority'),
                new \ReflectionMethod($item, 'getMessage'),
                new \ReflectionMethod($item, 'getRequest'),
                new \ReflectionMethod($item, 'getId'),
                new \ReflectionMethod($item, 'getSiteId'),
                new \ReflectionMethod($item, 'getTimeModified'),
                new \ReflectionMethod($item, 'getTimeCreated')
            ],
            'isGetter' => [],
            'hasGetter' => [],
            'ref' => $ref,
            'data' => $item
        ];

        // Prevent the further routing.
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Prepare the triggering object.
        $throughGetter = new ThroughGetter(Krexx::$pool);
        $throughGetter->setParameters($fixture)->callMe();

        // Assert the result
        $models = Krexx::$pool->routing->model;
        $this->assertCount(
            count($values),
            $models,
            'Asserting that we were able to get them all.'
        );

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        foreach ($models as $model) {
            $this->assertTrue(
                in_array($model->getData(), $values),
                'Asserting, that we were able to get the right results.'
            );
        }
    }
}
