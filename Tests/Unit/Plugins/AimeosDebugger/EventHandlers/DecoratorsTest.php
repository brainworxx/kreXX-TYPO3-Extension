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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\AbstractEventHandler;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators;
use Brainworxx\Includekrexx\Tests\Fixtures\AimeosJobsDecorator;
use Brainworxx\Includekrexx\Tests\Fixtures\Fixture20Job;
use Brainworxx\Includekrexx\Tests\Fixtures\FixtureJob;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Decorators::class, 'handle')]
#[CoversMethod(Decorators::class, 'retrieveMethods')]
#[CoversMethod(Decorators::class, 'checkClassName')]
#[CoversMethod(Decorators::class, 'retrievePublicMethods')]
#[CoversMethod(Decorators::class, 'retrieveReceiverObject')]
#[CoversMethod(AbstractEventHandler::class, 'retrieveProperty')]
#[CoversMethod(Decorators::class, 'retrieveProperty')]
#[CoversMethod(Decorators::class, 'retrieveReceiverObjectName')]
#[CoversMethod(Decorators::class, '__construct')]
class DecoratorsTest extends AbstractHelper
{
    use AimeosTestTrait;

    /**
     * Test the setting of the pool.
     */
    public function testConstruct()
    {
        $this->skipIfAimeosIsNotInstalled();

        $getter = new Decorators(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Call the handle with an invalid class instance.
     */
    public function testHandleEarlyReturn()
    {
        $this->skipIfAimeosIsNotInstalled();

        $wrongClass = new stdClass();
        $fixture = [
            Methods::PARAM_DATA => $wrongClass,
            Methods::PARAM_NAME => 'decorator fixture',
            Methods::PARAM_REF => new ReflectionClass($wrongClass)
        ];

        $decorators = new Decorators(\Krexx::$pool);
        $callback = new CallbackNothing(\Krexx::$pool);
        $callback->setParameters($fixture);
        $this->assertEquals(
            '',
            $decorators->handle($callback, new Model(\Krexx::$pool)),
            'Empy output and no crash.'
        );
    }

    /**
     * Create a decorator, trigger the event and assert the result.
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

        // Load the aimeos language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 'aimeos.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();

        // Create the event calling class.
        $methods = new Methods(Krexx::$pool);
        $this->triggerStartEvent($methods->setParameters($fixture));

        // Checking the models.
        /** @var \Brainworxx\Krexx\Analyse\Model $methodsModel */
        $methodsModel = Krexx::$pool->render->model['renderExpandableChild'][0];
        /** @var \Brainworxx\Krexx\Analyse\Model $objectsModel */
        $objectsModel = Krexx::$pool->render->model['renderExpandableChild'][1];

        $this->assertEquals('Undecorated methods', $methodsModel->getName());
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

        $this->assertEquals('Decorated object', $objectsModel->getName());
        $this->assertSame(
            $testJob,
            $objectsModel->getParameters()[CallbackConstInterface::PARAM_DATA][0],
            'The object that got itself decorated.'
        );
    }
}
