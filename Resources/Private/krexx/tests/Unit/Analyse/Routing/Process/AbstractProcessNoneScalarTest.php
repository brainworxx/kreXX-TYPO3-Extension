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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Routing\Process\AbstractProcessNoneScalar;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Krexx;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AbstractProcessNoneScalar::class, 'handle')]
#[CoversMethod(AbstractProcessNoneScalar::class, 'handleNestedTooDeep')]
#[CoversMethod(AbstractProcessNoneScalar::class, 'handleRecursion')]
#[CoversMethod(AbstractRouting::class, 'generateDomIdFromObject')]
class AbstractProcessNoneScalarTest extends AbstractHelper
{
    /**
     * Test the handling of a too deep nesting.
     */
    public function testHandleNestedTooDeep()
    {
        // Prepare the mock objects
        $emergencyHandlerMock = $this->createMock(Emergency::class);
        $emergencyHandlerMock->expects($this->once())
            ->method('checkNesting')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyHandlerMock;
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Prepare the fixture
        $fixture = new stdClass();
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        // Run the test.
        $objectProcessor = new ProcessObject(Krexx::$pool);
        $objectProcessor->canHandle($model);
        $objectProcessor->handle();

        // Check the model.
        $this->assertEquals(
            'To increase this value, change the Prune output => level setting.',
            $model->getData()
        );
        $this->assertEquals(
            'Maximum nesting level for the analysis was reached. I will not go any further.',
            $model->getNormal()
        );
        $this->assertEquals(ProcessObject::TYPE_OBJECT, $model->getType());
        $this->assertTrue($model->hasExtra());
    }

    /**
     * Test the recursion handling of the none scalar routing.
     */
    public function testHandleRecursionObject()
    {
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInHive')
            ->willReturn(true);
        Krexx::$pool->recursionHandler = $recursionMock;
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Prepare the fixture
        $fixture = new stdClass();
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        // Run the test.
        $objectProcessor = new ProcessObject(Krexx::$pool);
        $objectProcessor->canHandle($model);
        $objectProcessor->handle();

        $this->assertEquals('\\' . stdClass::class, $model->getNormal());
        $this->assertEquals(
            'k' . Krexx::$pool->emergencyHandler->getKrexxCount() . '_' . spl_object_hash($fixture),
            $model->getDomid()
        );
    }

    /**
     * Test the recursion handling of the globals array.
     */
    public function testHandleGlobals()
    {
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInHive')
            ->willReturn(true);
        Krexx::$pool->recursionHandler = $recursionMock;
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Prepare the fixture
        $fixture = ['blargh!'];
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        // Run the test.
        $arrayProcessor = new ProcessArray(Krexx::$pool);
        $arrayProcessor->canHandle($model);
        $arrayProcessor->handle();

        $this->assertEquals('$GLOBALS', $model->getNormal());
    }
}
