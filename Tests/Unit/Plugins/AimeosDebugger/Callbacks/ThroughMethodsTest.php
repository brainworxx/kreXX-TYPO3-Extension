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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\Callbacks;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\ConstInterface as AimeosConstInterface;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughMethods;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as IterateThroughMethods;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ThroughMethods::class, 'callMe')]
class ThroughMethodsTest extends AbstractHelper implements CallbackConstInterface
{
    use AimeosTestTrait;

    /**
     * Test the preprocessing of methods.
     */
    public function testCallMe()
    {
        $this->skipIfAimeosIsNotInstalled();

        $thoughMethods = new ThroughMethods(Krexx::$pool);
        // Test the start event.
        $this->mockEventService([ThroughMethods::class . PluginConfigInterface::START_EVENT, $thoughMethods]);

        // Create a fixture with reflections and names as keys.
        $fixture = [
            static::PARAM_DATA => [
                'some name' => new ReflectionMethod(MethodsFixture::class, 'publicMethod'),
                'another name' => new ReflectionMethod(MethodsFixture::class, 'protectedMethod'),
                'whatever' => new ReflectionMethod(MethodsFixture::class, 'privateMethod'),
                'trouble' => new ReflectionMethod(MethodsFixture::class, 'troublesomeMethod'),
            ],
            AimeosConstInterface::PARAM_IS_FACTORY_METHOD => true
        ];

        // Rewrite the "callback" IterateThroughMethods
        Krexx::$pool->rewrite[IterateThroughMethods::class] = CallbackCounter::class;

        $thoughMethods->setParameters($fixture)->callMe();

        // Assert the results.
        $this->assertEquals(count($fixture[static::PARAM_DATA]), CallbackCounter::$counter);

        foreach (CallbackCounter::$staticParameters as $result) {
            $this->assertSame(
                $fixture[static::PARAM_DATA][$result[AimeosConstInterface::PARAM_FACTORY_NAME]],
                $result[static::PARAM_DATA][0],
                'Check the passing of the reflection method by ref'
            );
            $this->assertEquals(
                MethodsFixture::class,
                $result[static::PARAM_REF]->getName(),
                'Check the creation of the reflection class.'
            );
        }
    }
}
