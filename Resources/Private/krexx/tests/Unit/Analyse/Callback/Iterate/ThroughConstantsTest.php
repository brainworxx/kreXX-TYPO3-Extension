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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ConstantsFixture71;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;

class ThroughConstantsTest extends AbstractHelper
{
    const SKIPPED_REASON = 'Skipped due to wrong PHP version.';
    const PUBLIC_CONSTANT = 'Public constant ';
    const STATIC_COLON_COLON = 'static::';

    /**
     * Run the test with the provided class name.
     *
     * @param $className
     * @return array
     * @throws \ReflectionException
     */
    protected function runTheTest($className)
    {
        $this->mockEmergencyHandler();

        // Inject route nothing
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Create a fixture
        $ref = new ReflectionClass($className);
        $fixture = [
            ThroughConstants::PARAM_DATA => $ref->getConstants(),
            ThroughConstants::PARAM_CLASSNAME => $className,
            ThroughConstants::PARAM_REF => $ref,
        ];

        // Listen for the start event.
        $throughConstants = new ThroughConstants(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConstants::callMe::start', $throughConstants]
        );

        // Run the tests
        $throughConstants->setParameters($fixture)->callMe();

        return $fixture;
    }

    /**
     * Testing the PHP 7.1 plus constants handling.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::canDump
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::retrieveAdditionalData
     */
    public function testCallMe()
    {
        \Krexx::$pool->scope->setScope('$somethingElse');
        $this->runTheTest(ConstantsFixture71::class);

        // And check the output.
        // We are expecting an analysis of the two public ones, since we are not
        // in scope.
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = Krexx::$pool->routing->model;
        $this->assertCount(2, $models);
        $this->assertEquals(ConstantsFixture71::CONST_1, $models[0]->getData());
        $this->assertEquals(ConstantsFixture71::CONST_2, $models[1]->getData());
        $this->assertEquals(static::PUBLIC_CONSTANT, $models[0]->getAdditional());
        $this->assertEquals(static::PUBLIC_CONSTANT, $models[1]->getAdditional());
        $this->assertEquals('CONST_1', $models[0]->getName());
        $this->assertEquals('CONST_2', $models[1]->getName());
        $this->assertEquals('\\' . ConstantsFixture71::class . '::', $models[0]->getConnectorLeft());
        $this->assertEquals('\\' . ConstantsFixture71::class . '::', $models[1]->getConnectorLeft());
    }

    /**
     * And now the same thing while coming from the inside.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::canDump
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::retrieveAdditionalData
     */
    public function testCallMe71InScope()
    {
        \Krexx::$pool->scope->setScope('$this');
        $this->runTheTest(ConstantsFixture71::class);

        // And check the output.
        // We are expecting an analysis of all of them because they are in scope.
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = Krexx::$pool->routing->model;
        $this->assertCount(4, $models);
        $this->assertEquals(ConstantsFixture71::CONST_1, $models[0]->getData());
        $this->assertEquals(ConstantsFixture71::CONST_2, $models[1]->getData());
        $this->assertEquals('string', $models[2]->getData());
        $this->assertEquals(21, $models[3]->getData());
        $this->assertEquals(static::PUBLIC_CONSTANT, $models[0]->getAdditional());
        $this->assertEquals(static::PUBLIC_CONSTANT, $models[1]->getAdditional());
        $this->assertEquals('Protected constant ', $models[2]->getAdditional());
        $this->assertEquals('Private constant ', $models[3]->getAdditional());
        $this->assertEquals('CONST_1', $models[0]->getName());
        $this->assertEquals('CONST_2', $models[1]->getName());
        $this->assertEquals('CONST_3', $models[2]->getName());
        $this->assertEquals('CONST_4', $models[3]->getName());
        $this->assertEquals(static::STATIC_COLON_COLON, $models[0]->getConnectorLeft());
        $this->assertEquals(static::STATIC_COLON_COLON, $models[1]->getConnectorLeft());
        $this->assertEquals(static::STATIC_COLON_COLON, $models[2]->getConnectorLeft());
        $this->assertEquals(static::STATIC_COLON_COLON, $models[3]->getConnectorLeft());
    }
}
