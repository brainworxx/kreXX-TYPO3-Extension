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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class GetterTest extends AbstractTest
{
    use AimeosTestTrait;

    /**
     * Subscribing our class to test to the right event.
     *
     * {@inheritDoc}
     */
    protected function krexxUp()
    {
        parent::krexxUp();

        // Subscribing.
        Registration::registerEvent(
            ThroughGetter::class . '::retrievePropertyValue::resolving',
            Getter::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);
    }

    /**
     * Test the assigning of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter::__construct
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $getter = new Getter(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Test the analysis of an 'Aimeos item.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter::handle
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter::assignResultsToModel
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter::retrieveValueArray
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter::retrievePossibleKey
     */
    public function testHandle()
    {
        $this->skipIfAimeosIsNotInstalled();

        $values = [
            // Base class (bdata)
            'log.id' => '123456',
            'log.siteid' => '42',
            'log.mtime' => 'today',
            'log.ctime' => 'yesterday',
            'log.editor' => null,
            // Standard class (values)
            'log.facility' => 'kreXX',
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
              new \ReflectionMethod($item, 'getResourceType'),
              new \ReflectionMethod($item, 'getId'),
              new \ReflectionMethod($item, 'getSiteId'),
              new \ReflectionMethod($item, 'getTimeModified'),
              new \ReflectionMethod($item, 'getTimeCreated'),
              new \ReflectionMethod($item, 'getEditor'),

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
