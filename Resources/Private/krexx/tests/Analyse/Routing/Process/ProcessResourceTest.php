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

namespace Brainworxx\Krexx\Tests\Analyse\Routing\Process;

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
        \Brainworxx\Krexx\Analyse\Routing\Process\get_resource_type(null, 'stream');
        \Brainworxx\Krexx\Analyse\Routing\Process\stream_get_meta_data(null, $metaResults);

        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        $processor->process($model);

        $this->assertEquals($model::TYPE_RESOURCE, $model->getType());
        $this->assertEquals('resource (stream)', $model->getNormal());
        $this->assertEquals($metaResults, $model->getParameters()[$model::PARAM_DATA]);
        $this->assertEquals(1, CallbackCounter::$counter);
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
        \Brainworxx\Krexx\Analyse\Routing\Process\get_resource_type(null, 'curl');
        \Brainworxx\Krexx\Analyse\Routing\Process\curl_getinfo(null, $metaResults);

        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        $processor->process($model);

        $this->assertEquals($model::TYPE_RESOURCE, $model->getType());
        $this->assertEquals('resource (curl)', $model->getNormal());
        $this->assertEquals($metaResults, $model->getParameters()[$model::PARAM_DATA]);
        $this->assertEquals(1, CallbackCounter::$counter);
    }

     /**
     * Testing the processing of a not yet implemented resource type analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     */
    public function testProcessOtherNotPhp72()
    {
        $this->mockEmergencyHandler();

        $resource = 'Letting a string look like a resource is easy.';
        \Brainworxx\Krexx\Analyse\Routing\Process\get_resource_type(null, 'whatever');
        \Brainworxx\Krexx\Analyse\Routing\Process\version_compare(null, null, null, false);

        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        $processor->process($model);

        $this->assertEquals($model::TYPE_RESOURCE, $model->getType());
        $this->assertEquals('resource (whatever)', $model->getNormal());
        $this->assertEquals('resource (whatever)', $model->getData());
        $this->assertEmpty($model->getParameters());
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Testing the processing of a not yet implemented resource type analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource::process
     */
    public function testProcessOtherPhp72()
    {
        $this->mockEmergencyHandler();

        $resource = 'Meh, here I might not really look like a resource at all.';
        \Brainworxx\Krexx\Analyse\Routing\Process\get_resource_type(null, 'not a string');
        \Brainworxx\Krexx\Analyse\Routing\Process\version_compare(null, null, null, true);

        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        $processor->process($model);

        $this->assertEquals($model::TYPE_RESOURCE, $model->getType());
        $this->assertEquals('string', $model->getNormal());
        $this->assertEquals('string', $model->getData());
        $this->assertEmpty($model->getParameters());
        $this->assertEquals(0, CallbackCounter::$counter);
    }
}