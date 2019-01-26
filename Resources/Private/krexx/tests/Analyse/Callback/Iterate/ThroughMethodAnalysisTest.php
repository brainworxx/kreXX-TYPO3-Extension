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

namespace Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ThroughMethodAnalysisTest extends AbstractTest
{
    /**
     * Our test subject.
     *
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis
     */
    protected $throughMethodAnalysis;

    public function setUp()
    {
        parent::setUp();
        $this->throughMethodAnalysis = new ThroughMethodAnalysis(\Krexx::$pool);
    }

    /**
     * Testing with empty values.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis::callMe
     */
    public function testCallMeEmpty()
    {
        // Test start event
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::start', $this->throughMethodAnalysis]
        );

        // Inject the fixture.
        $fixture = [$this->throughMethodAnalysis::PARAM_DATA =>[]];
        // Run test.
        $this->throughMethodAnalysis
            ->setParams($fixture)
            ->callMe();
    }

    /**
     * Testing the rendering of a method analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis::callMe
     */
    public function testCallMeNormal()
    {
        // Inject render nothing.
        $renderNothing = new RenderNothing(\Krexx::$pool);
        \Krexx::$pool->render = $renderNothing;

        // Test the events.
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::start', $this->throughMethodAnalysis],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::end', $this->throughMethodAnalysis],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::end', $this->throughMethodAnalysis],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::end', $this->throughMethodAnalysis],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::end', $this->throughMethodAnalysis]
        );

        // Create a fixture.
        $fixture = [
            $this->throughMethodAnalysis::PARAM_DATA => [
                'some key' => 'some normal key',
                'comments' => 'some comment',
                'declared in' => 'some line',
                'source' => 'source'
            ]
        ];

        // Run test.
        $this->throughMethodAnalysis
            ->setParams($fixture)
            ->callMe();

        $models = $renderNothing->model['renderSingleChild'];

        // Test the result.
        $this->assertEquals($fixture[$this->throughMethodAnalysis::PARAM_DATA]['some key'], $models[0]->getData());
        $this->assertEquals('some key', $models[0]->getName());
        $this->assertEquals($this->throughMethodAnalysis::TYPE_REFLECTION,$models[0]->getType());
        $this->assertEquals($fixture[$this->throughMethodAnalysis::PARAM_DATA]['some key'], $models[0]->getNormal());
        $this->assertFalse($models[0]->getHasExtra());

        $this->assertEquals($fixture[$this->throughMethodAnalysis::PARAM_DATA]['comments'], $models[1]->getData());
        $this->assertEquals('comments', $models[1]->getName());
        $this->assertEquals($this->throughMethodAnalysis::TYPE_REFLECTION,$models[1]->getType());
        $this->assertEquals('. . .', $models[1]->getNormal());
        $this->assertTrue($models[1]->getHasExtra());

        $this->assertEquals($fixture[$this->throughMethodAnalysis::PARAM_DATA]['declared in'], $models[2]->getData());
        $this->assertEquals('declared in', $models[2]->getName());
        $this->assertEquals($this->throughMethodAnalysis::TYPE_REFLECTION, $models[2]->getType());
        $this->assertEquals('. . .', $models[2]->getNormal());
        $this->assertTrue($models[2]->getHasExtra());

        $this->assertEquals($fixture[$this->throughMethodAnalysis::PARAM_DATA]['source'], $models[3]->getData());
        $this->assertEquals('source', $models[3]->getName());
        $this->assertEquals($this->throughMethodAnalysis::TYPE_REFLECTION, $models[3]->getType());
        $this->assertEquals('. . .', $models[3]->getNormal());
        $this->assertTrue($models[3]->getHasExtra());
    }
}