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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\File as Fileservice;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ProcessClosureTest extends AbstractTest
{
    /**
     * Test the processing of a closure.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure::handleNoneScalar
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\AbstractProcessNoneScalar::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure::retrieveParameterList
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure::retrieveSourceCode
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::generateDomIdFromObject
     */
    public function testProcess()
    {
        $this->mockEmergencyHandler();

        /**
         * Just another fixture.
         *
         * @param string $someVar
         * @return string
         */
        $fixture = function (string $someVar) {
            // 'Do' something, to prevent another code smell or a bug.
            return strlen($someVar);
        };
        $containingCode = 'just some source code';
        $filePath = 'some file in a directory';
        $parameter = 'string $someVar';
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        // Prepare the "framework". More frame than work, really.
        $fileserviceMock = $this->createMock(Fileservice::class);
        $fileserviceMock->expects($this->once())
            ->method('readSourcecode')
            ->will($this->returnValue($containingCode));
        $fileserviceMock->expects($this->once())
            ->method('filterFilePath')
            ->will($this->returnValue($filePath));
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->fileService = $fileserviceMock;
        Krexx::$pool->render = $renderNothing;

        // Run the test
        $processClosure = new ProcessClosure(Krexx::$pool);
        $this->mockEventService(
            [ProcessClosure::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $processClosure->handle($model);

        // Run the tests, model.
        $this->assertEquals(ProcessClosure::TYPE_CLOSURE, $model->getType());
        $this->assertEquals(
            ProcessClosure::UNKNOWN_VALUE,
            $model->getNormal(),
            'We are expecting "...", it\'s not really unknown. '
        );
        $this->assertEquals($parameter, $model->getConnectorParameters());
        $this->assertNotEmpty($model->getDomid());
        /** @var \Brainworxx\Krexx\Analyse\Code\Connectors $connectorService */
        $connectorService = $this->retrieveValueByReflection('connectorService', $model);
        $this->assertEquals(Connectors::CONNECTOR_METHOD, $this->retrieveValueByReflection('type', $connectorService));

        // Run the tests, parameters.
        $parameters = $model->getParameters()[ProcessClosure::PARAM_DATA];

        // Meta data inside the callback parameters
        $this->assertContains('Just another fixture.', $parameters[ProcessClosure::META_COMMENT]);
        $this->assertEquals($containingCode, $parameters[ProcessClosure::META_SOURCE]);
        $this->assertContains($filePath, $parameters[ProcessClosure::META_DECLARED_IN]);
        $this->assertEquals(__NAMESPACE__, $parameters[ProcessClosure::META_NAMESPACE]);
        $this->assertEquals($parameter, $parameters[ProcessClosure::META_PARAM_NO . '1']);
    }

    /**
     * Test the check if we can handle the array processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure::canHandle
     */
    public function testCanHandle()
    {
        $processor = new ProcessClosure(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = function () {
            echo 'huhu';
        };

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
        $fixture = 'abc';
        $this->assertFalse($processor->canHandle($model->setData($fixture)));
    }
}
