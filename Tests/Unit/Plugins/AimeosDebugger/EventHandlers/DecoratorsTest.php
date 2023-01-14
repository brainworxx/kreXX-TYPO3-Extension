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

use Aimeos\Map;
use Aimeos\MShop\Context\Item\Standard as MShopContextStandard;
use Aimeos\MShop\Context as MShopContext;
use Aimeos\Bootstrap;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators;
use Brainworxx\Includekrexx\Tests\Fixtures\AimeosJobsDecorator;
use Brainworxx\Includekrexx\Tests\Fixtures\Fixture20Job;
use Brainworxx\Includekrexx\Tests\Fixtures\FixtureJob;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class DecoratorsTest extends AbstractTest
{
    use AimeosTestTrait;

    /**
     * Test the setting of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::__construct
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $getter = new Decorators(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Create a decorator, trigger the event and assert the result.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::handle
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::retrieveMethods
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::checkClassName
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::retrievePublicMethods
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::retrieveReceiverObject
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::retrieveProperty
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators::retrieveReceiverObjectName
     */
    public function testHandle()
    {
        $this->skipIfAimeosIsNotInstalled();

        // Create a fixture with a decorator.
        if (class_exists(MShopContextStandard::class)) {
            $context = new MShopContextStandard();
        } else {
            $context = new MShopContext();
        }

        $aimeos = new Bootstrap();
        if (class_exists(Map::class)) {
            $testJob = new Fixture20Job();
        } else {
            $testJob = new FixtureJob();
        }

        $decorator = new AimeosJobsDecorator($testJob, $context, $aimeos);
        $fixture = [
            Methods::PARAM_DATA => $decorator,
            Methods::PARAM_NAME => 'decorator fixture',
            Methods::PARAM_REF => new ReflectionClass($decorator)
        ];

        // Subscribing.
        Registration::registerEvent(
            Methods::class . PluginConfigInterface::START_EVENT,
            Decorators::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Short circuit the rendering process.
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);

        // Create the event calling class.
        $methods = new Methods(Krexx::$pool);
        $this->triggerStartEvent($methods->setParameters($fixture));

        // Checking the models.
        /** @var \Brainworxx\Krexx\Analyse\Model $methodsModel */
        $methodsModel = Krexx::$pool->render->model['renderExpandableChild'][0];
        /** @var \Brainworxx\Krexx\Analyse\Model $objectsModel */
        $objectsModel = Krexx::$pool->render->model['renderExpandableChild'][1];

        $this->assertEquals('Undecorated Methods', $methodsModel->getName());
        // List of the methods of the decorated class, that are not implemented of
        // the surrounding class.
        $expectations = [
            'originalMethod'
        ];
        /** @var \ReflectionMethod $reflectionMethod */
        $index = 0;
        foreach ($methodsModel->getParameters()[CallbackConstInterface::PARAM_DATA] as $key => $reflectionMethod) {
            $this->assertEquals($key, $reflectionMethod->name);
            $this->assertEquals($expectations[$index], $key);
            ++$index;
        }
        $this->assertEquals(1, $index, 'There should only be one method undecorated.');

        $this->assertEquals('Decorated Object', $objectsModel->getName());
        $this->assertSame(
            $testJob,
            $objectsModel->getParameters()[CallbackConstInterface::PARAM_DATA][0],
            'The object that got itself decorated.'
        );
    }
}
