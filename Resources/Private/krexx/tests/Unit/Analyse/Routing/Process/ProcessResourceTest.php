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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResource;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class ProcessResourceTest extends AbstractTest
{
    /**
     * Testing the processing of a stream resource.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     */
    public function testProcessStream()
    {
        $this->mockEmergencyHandler();

        $resource = 'I\'m the best resource ever. Trust me, only I can do this.';
        $metaResults = [
            'best', 'stream', 'resource', 'ever'
        ];
        $getResourceType = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'get_resource_type');
        $getResourceType->expects($this->once())
            ->will($this->returnValue('stream'));
        $streamGetMetsData = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'stream_get_meta_data');
        $streamGetMetsData->expects($this->once())
            ->will($this->returnValue($metaResults));

        $this->runTheTest($resource, 1, 'resource (stream)', null, $metaResults);
    }

    /**
     * Testing the processing of a curl resource.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     */
    public function testProcessCurl()
    {
        $this->mockEmergencyHandler();

        $resource = 'I am not a string.';
        $metaResults = [
            'everybody', 'likes', 'curling'
        ];
        $getResourceType = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'get_resource_type');
        $getResourceType->expects($this->once())
            ->will($this->returnValue('curl'));
        $getResourceType = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'curl_getinfo');
        $getResourceType->expects($this->once())
            ->will($this->returnValue($metaResults));

        $this->runTheTest($resource, 1, 'resource (curl)', null, $metaResults);
    }

    /**
     * Testing the processing of a not yet implemented resource type analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::renderUnknownOrClosed
     */
    public function testProcessOtherNotPhp72()
    {
        $this->mockEmergencyHandler();

        $resource = 'Letting a string look like a resource is easy.';
        $getResourceType = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'get_resource_type');
        $getResourceType->expects($this->once())
            ->will($this->returnValue('whatever'));
        $versionCompare = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'version_compare');
        $versionCompare->expects($this->once())
            ->will($this->returnValue(false));

        $this->runTheTest($resource, 0, 'resource (whatever)', 'resource (whatever)');
    }

    /**
     * Testing the processing of a not yet implemented resource type analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::renderUnknownOrClosed
     */
    public function testProcessOtherPhp72()
    {
        $this->mockEmergencyHandler();

        $resource = 'Meh, here I might not really look like a resource at all.';
        $getResourceType = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'get_resource_type');
        $getResourceType->expects($this->once())
            ->will($this->returnValue('not a string'));
        $versionCompare = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'version_compare');
        $versionCompare->expects($this->once())
            ->will($this->returnValue(true));

        $this->runTheTest($resource, 0, 'string', 'string');
    }

    /**
     * Running the test is pretty much the same every way here.
     *
     * @param $resource
     * @param $counter
     * @param $normalExpectation
     * @param $dataExpectation
     * @param $metaResults
     */
    protected function runTheTest(
        $resource,
        $counter,
        $normalExpectation,
        $dataExpectation = null,
        $metaResults = null
    ) {
        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        $processor->process($model);

        $this->assertEquals($model::TYPE_RESOURCE, $model->getType());
        $this->assertEquals($normalExpectation, $model->getNormal());
        if (isset($dataExpectation)) {
            $this->assertEquals($dataExpectation, $model->getData());
        }
        if (isset($metaResults)) {
            $this->assertEquals($metaResults, $model->getParameters()[$model::PARAM_DATA]);
        } else {
            $this->assertEmpty($model->getParameters());
        }
        $this->assertEquals($counter, CallbackCounter::$counter);
    }
}