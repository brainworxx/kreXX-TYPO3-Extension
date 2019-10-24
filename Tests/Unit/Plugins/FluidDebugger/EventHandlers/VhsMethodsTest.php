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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class VhsMethodsTest extends AbstractTest implements ConstInterface
{
    /**
     * Test the setting of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods::__construct
     */
    public function testConstruct()
    {
        $getter = new VhsMethods(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Test the fluid-vhs-ique source code generation for methods.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods::handle
     */
    public function testHandle()
    {
        $methodFixture = new ComplexMethodFixture();
        $reflection = new ReflectionClass($methodFixture);
        $fixture = [
            static::PARAM_REF => $reflection,
            static::PARAM_DATA => [
                $reflection->getMethod('finalMethod'),
                $reflection->getMethod('privateMethod'),
                $reflection->getMethod('staticMethod'),
                $reflection->getMethod('parameterizedMethod'),
                $reflection->getMethod('interfaceMethod'),
                $reflection->getMethod('traitComment'),
            ]
        ];

        // Subscribing
        Registration::registerEvent(
            ThroughMethods::class . PluginConfigInterface::END_EVENT,
            VhsMethods::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        $render = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $render;

        $throughMethods = new ThroughMethods(Krexx::$pool);
        $throughMethods->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = $render->model['renderExpandableChild'];
        foreach ($models as $model) {
            $this->assertEquals(Codegen::VHS_CALL_VIEWHELPER, $model->getMultiLineCodeGen());
            if ($model->getName() !== 'parameterizedMethod') {
                $this->assertEquals([], $model->getParameters()[Codegen::PARAM_ARRAY]);
            } else {
                $this->assertEquals('parameter', $model->getParameters()[Codegen::PARAM_ARRAY][0]);
            }
        }
    }
}
