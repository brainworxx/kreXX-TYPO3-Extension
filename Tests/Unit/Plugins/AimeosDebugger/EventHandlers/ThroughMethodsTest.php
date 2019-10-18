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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as IterateThroughMethods;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use ReflectionMethod;

class ThroughMethodsTest extends AbstractTest
{

    /**
     * Test the handling of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods::__construct
     */
    public function testConstruct()
    {
        $properties = new ThroughMethods(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->getValueByReflection('pool', $properties));
    }

    /**
     * Test the correcting of the name with the factory method name.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods::handle
     */
    public function testHandle()
    {
        // Subscribing.
        Registration::registerEvent(
            IterateThroughMethods::class . '::callMe::end',
            ThroughMethods::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        // Inject the render nothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // The fixtures.
        $fixture = [
            IterateThroughMethods::PARAM_REF => new ReflectionClass(ComplexMethodFixture::class),
            IterateThroughMethods::PARAM_DATA => [
                new ReflectionMethod(ComplexMethodFixture::class, 'publicMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'protectedMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'privateMethod'),
                new ReflectionMethod(MethodsFixture::class, 'privateMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'troublesomeMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'finalMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'parameterizedMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'traitFunction')
            ],
            ThroughMethods::PARAM_FACTORY_NAME => 'myFactory'
        ];

        $throughMethods = new IterateThroughMethods(Krexx::$pool);
        $throughMethods
            ->setParameters($fixture)
            ->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        foreach ($renderNothing->model['renderExpandableChild'] as $model) {
            $this->assertEquals(
                $fixture[ThroughMethods::PARAM_FACTORY_NAME],
                $model->getName(),
                'We only assert the part where the event handler changes something.'
            );
        }
    }
}
