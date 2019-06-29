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
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;

class ThroughMethodAnalysisTest extends AbstractTest
{
    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis::callMe::end';

    /**
     * Our test subject.
     *
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis
     */
    protected $throughMethodAnalysis;

    public function setUp()
    {
        parent::setUp();
        $this->throughMethodAnalysis = new ThroughMethodAnalysis(Krexx::$pool);
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
            [$this->startEvent, $this->throughMethodAnalysis]
        );

        // Inject the fixture.
        $fixture = [$this->throughMethodAnalysis::PARAM_DATA =>[]];
        // Run test.
        $this->throughMethodAnalysis
            ->setParameters($fixture)
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
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Test the events.
        $this->mockEventService(
            [$this->startEvent, $this->throughMethodAnalysis],
            [$this->endEvent, $this->throughMethodAnalysis],
            [$this->endEvent, $this->throughMethodAnalysis],
            [$this->endEvent, $this->throughMethodAnalysis],
            [$this->endEvent, $this->throughMethodAnalysis]
        );

        // Create a fixture.
        $someKey = 'some key';
        $fixture = [
            $this->throughMethodAnalysis::PARAM_DATA => [
                $someKey => 'some normal key',
                ConstInterface::META_COMMENT => 'some comment',
                ConstInterface::META_DECLARED_IN => 'some line',
                ConstInterface::META_SOURCE => 'source code'
            ]
        ];

        // Run test.
        $this->throughMethodAnalysis
            ->setParameters($fixture)
            ->callMe();

        $models = $renderNothing->model['renderSingleChild'];

        // Test the result.
        $this->assertModelValues(
            $models[0],
            $fixture[$this->throughMethodAnalysis::PARAM_DATA][$someKey],
            $someKey,
            $this->throughMethodAnalysis::TYPE_REFLECTION,
            $fixture[ConstInterface::PARAM_DATA][$someKey],
            false
        );

        $this->assertModelValues(
            $models[1],
            $fixture[$this->throughMethodAnalysis::PARAM_DATA][ConstInterface::META_COMMENT],
            ConstInterface::META_COMMENT,
            ConstInterface::TYPE_REFLECTION,
            ConstInterface::UNKNOWN_VALUE,
            true
        );

        $this->assertModelValues(
            $models[2],
            $fixture[$this->throughMethodAnalysis::PARAM_DATA][ConstInterface::META_DECLARED_IN],
            ConstInterface::META_DECLARED_IN,
            ConstInterface::TYPE_REFLECTION,
            ConstInterface::UNKNOWN_VALUE,
            true
        );

        $this->assertModelValues(
            $models[3],
            $fixture[$this->throughMethodAnalysis::PARAM_DATA][ConstInterface::META_SOURCE],
            ConstInterface::META_SOURCE,
            ConstInterface::TYPE_REFLECTION,
            ConstInterface::UNKNOWN_VALUE,
            true
        );
    }

    /**
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @param mixed $data
     * @param string $name
     * @param string $type
     * @param string $normal
     * @param bool $hasExtras
     */
    protected function assertModelValues(
        Model $model,
        $data,
        string $name,
        string $type,
        string $normal,
        bool $hasExtras
    ) {
        $this->assertEquals($data, $model->getData());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($type, $model->getType());
        $this->assertEquals($normal, $model->getNormal());
        $this->assertEquals($hasExtras, $model->getHasExtra());
    }
}