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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger\Rewrites\Code;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Codegen::class, 'setComplicatedWrapperRight')]
#[CoversMethod(Codegen::class, 'generateWrapperRight')]
#[CoversMethod(Codegen::class, 'setComplicatedWrapperLeft')]
#[CoversMethod(Codegen::class, 'generateWrapperLeft')]
#[CoversMethod(Codegen::class, 'generateSource')]
#[CoversMethod(Codegen::class, 'generateAll')]
#[CoversMethod(Codegen::class, 'generateVhsCall')]
#[CoversMethod(Codegen::class, 'isUnknownType')]
class CodegenTest extends AbstractHelper
{
    /**
     * Testing the source code generation for fluid.
     */
    public function testGenerateSource()
    {
        // The forbidden one.
        $codeGen = new Codegen(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $this->assertEquals('', $codeGen->generateSource($model));

        // The dotty name.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('dotty.dot');
        $this->assertEquals($codeGen::UNKNOWN_VALUE, $codeGen->generateSource($model));

        // The configured debug method.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('debugmethod');
        $model->setType('Debug method');
        $this->assertEquals($codeGen::UNKNOWN_VALUE, $codeGen->generateSource($model));

        // The special debug method getProperties
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('getProperties');
        $model->setType('Debug method');
        $this->assertEquals('properties', $codeGen->generateSource($model));

        // The VHS version.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('bluRay');
        $model->setCodeGenType($codeGen::VHS_CALL_VIEWHELPER);
        $fixture = [
            'play' => 'dvd',
            'stop' => 'HD',
            'video' => 'BetaMax'
        ];
        $model->addParameter(Codegen::PARAM_ARRAY, $fixture);
        $this->assertEquals(
            ' -> v:call(method: \'bluRay\', arguments: {dvd: \'dvd\', HD: \'HD\', BetaMax: \'BetaMax\'})',
            $codeGen->generateSource($model)
        );

        // The VHS Version without any arguments
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('webem');
        $model->setCodeGenType($codeGen::VHS_CALL_VIEWHELPER);
        $model->addParameter(Codegen::PARAM_ARRAY, []);
        $this->assertEquals(
            ' -> v:call(method: \'webem\')',
            $codeGen->generateSource($model)
        );

        // The dreaded _all variable name, the _all itself.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('_all');
        $this->assertEquals('', $codeGen->generateSource($model));
        $this->assertTrue($this->retrieveValueByReflection('isAll', $codeGen));

        // A child of _all. Sounds like a black metal song.
        $model = new Model(Krexx::$pool);
        $model->setName('child');
        $model->setType(ProcessConstInterface::TYPE_ARRAY);
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getNestingLevel')
            ->willReturn(2);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $this->assertEquals('child', $codeGen->generateSource($model));

        // Iterator to array generation (which does not exist).
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('somIteratorClass');
        $model->setCodeGenType(Codegen::CODEGEN_TYPE_ITERATOR_TO_ARRAY);
        $this->assertEquals($codeGen::UNKNOWN_VALUE, $codeGen->generateSource($model));

        // Json deconding, which also does not exist.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('somIteratorClass');
        $model->setCodeGenType(Codegen::CODEGEN_TYPE_JSON_DECODE);
        $this->assertEquals($codeGen::UNKNOWN_VALUE, $codeGen->generateSource($model));

        // And finally, some normal generation.
        $codeGen = new Codegen(Krexx::$pool);
        $codeGen->setCodegenAllowed(true);
        $model = new Model(Krexx::$pool);
        $model->setName('normalStuff');
        $this->assertEquals('normalStuff', $codeGen->generateSource($model));
    }

    /**
     * Test the setter / getter
     */
    public function testSetGenerateComplicatedWrapperLeft()
    {
        $codeGen = new Codegen(Krexx::$pool);
        $fixture = 'some string';
        $this->assertEquals($fixture, $codeGen->setComplicatedWrapperLeft($fixture)->generateWrapperLeft());
        $codeGen->setComplicatedWrapperLeft($fixture);
    }

    /**
     * Test the setter / getter
     */
    public function testSetGenerateComplicatedWrapperRight()
    {
        $codeGen = new Codegen(Krexx::$pool);
        $fixture = 'another string';
        $codeGen->setComplicatedWrapperRight($fixture);
        $this->assertEquals($fixture, $codeGen->setComplicatedWrapperRight($fixture)->generateWrapperRight());
    }
}
