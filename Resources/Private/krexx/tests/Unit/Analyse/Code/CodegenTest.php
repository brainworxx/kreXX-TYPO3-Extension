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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Code;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Fixtures\MethodParameterFixture;
use Brainworxx\Krexx\Tests\Fixtures\UnionTypeFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use ReflectionParameter;

class CodegenTest extends AbstractHelper
{
    const FIRST_RUN = 'firstRun';
    const DISABLE_COUNT = 'disableCount';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const CONCATENATED_CONNECTORS = 'getConnectorLeftnamegetConnectorRight';

    /**
     * Our test subject
     *
     * @var Codegen
     */
    protected $codegenHandler;

    /**
     * The model for the code generation.
     *
     * @var \Brainworxx\Krexx\Analyse\Model
     */
    protected $fixture;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $connectorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codegenHandler = new Codegen(Krexx::$pool);
        $this->codegenHandler->setCodegenAllowed(true);
        $this->setValueByReflection(static::DISABLE_COUNT, 0, $this->codegenHandler);
        $this->setValueByReflection(static::FIRST_RUN, false, $this->codegenHandler);

        $this->fixture = new Model(Krexx::$pool);
        $this->fixture->setName('name')->setType('class');

        // Mock the connector service and inject it into the model.
        $this->connectorMock = $this->createMock(Connectors::class);
        $this->setValueByReflection('connectorService', $this->connectorMock, $this->fixture);
    }

    /**
     * Add the expects calls to the already injected connector mock.
     *
     * @param int $left
     * @param int $right
     */
    protected function expectConnectorCalls($left, $right)
    {
        $this->connectorMock->expects($this->exactly($left))
            ->method(static::GET_CONNECTOR_LEFT)
            ->will($this->returnValue(static::GET_CONNECTOR_LEFT));
        $this->connectorMock->expects($this->exactly($right))
            ->method(static::GET_CONNECTOR_RIGHT)
            ->will($this->returnValue(static::GET_CONNECTOR_RIGHT));
    }

    /**
     * Test the pool handling.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::__construct
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->codegenHandler));
        $this->assertEquals($this->codegenHandler, Krexx::$pool->codegenHandler);
    }

    /**
     * Test the forbidden code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceNoGen()
    {
        $this->codegenHandler->setCodegenAllowed(false);
        $this->expectConnectorCalls(0, 0);

        $this->assertEquals('. . .', $this->codegenHandler->generateSource($this->fixture));
    }

    /**
     * Test the concatenation of the first run.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::addTypeHint
     */
    public function testGenerateSourceFirstRun()
    {
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->expectConnectorCalls(0, 0);
        $this->fixture->setNormal(static::class)->setName('$name');

        $this->assertEquals(
            '$name',
            $this->codegenHandler->generateSource($this->fixture)
        );

        // It's not the first run anymore.
        $this->assertEquals(false, $this->retrieveValueByReflection(static::FIRST_RUN, $this->codegenHandler));
        // Check the type hint value.
        $json = $this->fixture->getJson();
        $this->assertArrayHasKey(Codegen::CODEGEN_TYPE_HINT, $json);
        $this->assertEquals(
            '/** @var ' . static::class . ' $name */',
            $json[Codegen::CODEGEN_TYPE_HINT],
            'Test the typehint'
        );
    }

    /**
     * Test the type hint with a more complicated varname from the source.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::addTypeHint
     */
    public function testGenerateSourceFirstRunNoTypeHint()
    {
        $this->fixture->setName('$instance->getValue()');
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->expectConnectorCalls(0, 0);
        $this->fixture->setNormal(static::class);

        $this->codegenHandler->generateSource($this->fixture);
        $json = $this->fixture->getJson();
        $this->assertArrayNotHasKey(Codegen::CODEGEN_TYPE_HINT, $json, 'Type hint is not set.');

        // do it again, with another name.
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->fixture->setNormal(static::class)->setName('justastring');
        $this->codegenHandler->generateSource($this->fixture);
        $json = $this->fixture->getJson();
        $this->assertArrayNotHasKey(Codegen::CODEGEN_TYPE_HINT, $json, 'Do not add a typehint ot a none variable');

        // And again.
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->fixture->setNormal(static::class)->setName('$justastring . \'wasGehtAb\'');
        $this->codegenHandler->generateSource($this->fixture);
        $json = $this->fixture->getJson();
        $this->assertArrayNotHasKey(Codegen::CODEGEN_TYPE_HINT, $json, 'Do not add a typehint ot a none variable');
    }

    /**
     * Test an empty run, something like krexx(), without any variable.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::addTypeHint
     */
    public function testGenerateSourceEmptyFirstRunNoTypeHint()
    {
        $this->fixture->setName('');
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->codegenHandler->generateSource($this->fixture);
        $json = $this->fixture->getJson();
        $this->assertArrayNotHasKey(Codegen::CODEGEN_TYPE_HINT, $json, 'Type hint is not set.');
    }

    /**
     * Test the type hint with a more complitated varname from t he source.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::addTypeHint
     */
    public function testGenerateSourceFirstRunTypeHintScalar()
    {
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->expectConnectorCalls(0, 0);
        $this->fixture
            ->setName('$variable')
            ->setNormal(static::class)
            ->setType('array');

        $this->codegenHandler->generateSource($this->fixture);
        $json = $this->fixture->getJson();
        $this->assertArrayHasKey(Codegen::CODEGEN_TYPE_HINT, $json);
        $this->assertEquals(
            '/** @var array $variable */',
            $json[Codegen::CODEGEN_TYPE_HINT],
            'Test the typehint'
        );
    }

    /**
     * Test the stop return, in case of constants.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceMetaConstants()
    {
        $this->expectConnectorCalls(0, 0);
        $this->fixture->setCodeGenType(Codegen::CODEGEN_TYPE_META_CONSTANTS);
        $this->assertEquals(
            ';stop;',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test an empty return value, in case of empty connectors.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceEmpty()
    {
        $this->connectorMock->expects($this->exactly(1))
            ->method(static::GET_CONNECTOR_LEFT)
            ->will($this->returnValue(''));
        $this->connectorMock->expects($this->exactly(1))
            ->method(static::GET_CONNECTOR_RIGHT)
            ->will($this->returnValue(''));

        $this->assertEquals(
            '',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of debug methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIsDebug()
    {
        $this->expectConnectorCalls(1, 1);
        $this->fixture
            ->setType($this->codegenHandler::TYPE_DEBUG_METHOD)
            ->setCodeGenType(Codegen::CODEGEN_TYPE_PUBLIC);
        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of debug methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIteratorToArray()
    {
        $this->expectConnectorCalls(1, 1);
        $this->fixture->setCodeGenType($this->codegenHandler::CODEGEN_TYPE_ITERATOR_TO_ARRAY);
        $this->assertEquals(
            'iterator_to_array(;firstMarker;)getConnectorLeftnamegetConnectorRight',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the meta json code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceMetaDecodedJson()
    {
        $this->fixture->setCodeGenType($this->codegenHandler::CODEGEN_TYPE_JSON_DECODE);
        $this->assertEquals(
            'json_decode(;firstMarker;)',
            $this->codegenHandler->generateSource($this->fixture),
            'There should not be any connectors, so we just expect the wrapper string.'
        );
    }

    /**
     * Test the meta Base64 code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateMetaDecodedBase64()
    {
        $this->fixture->setCodeGenType($this->codegenHandler::CODEGEN_TYPE_BASE64_DECODE);
        $this->assertEquals(
            'base64_decode(;firstMarker;)',
            $this->codegenHandler->generateSource($this->fixture),
            'There should not be any connectors, so we just expect the wrapper string.'
        );
    }

    /**
     * Test the coegeneration for unaccessible array values.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceArrayValueAccess()
    {
        $this->expectConnectorCalls(0, 0);
        $this->connectorMock->expects($this->once())
            ->method('setParameters')
            ->with('0');
        $this->connectorMock->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue('0'));

        $this->fixture
            ->setCodeGenType($this->codegenHandler::CODEGEN_TYPE_ARRAY_VALUES_ACCESS)
            ->setConnectorParameters('0');
        $this->assertEquals(
            'array_values(;firstMarker;)[0]',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of public access.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIsPublic()
    {
        $this->expectConnectorCalls(1, 1);
        $this->fixture->setCodeGenType(Codegen::CODEGEN_TYPE_PUBLIC);
        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case that the model is in the scope.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceInScope()
    {
        $this->expectConnectorCalls(3, 1);

        // Create the scope mock and inject it.
        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('testModelForCodegen')
            ->with($this->fixture)
            ->will($this->returnValue(true));
        Krexx::$pool->scope = $scopeMock;

        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the '. . .' when out of scope.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateComplicatedStuff
     */
    public function testGenerateSourceNotInScope()
    {
        $this->expectConnectorCalls(2, 0);

        // Create the scope mock and inject it.
        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('testModelForCodegen')
            ->with($this->fixture)
            ->will($this->returnValue(false));
        Krexx::$pool->scope = $scopeMock;

        $this->assertEquals('. . .', $this->codegenHandler->generateSource($this->fixture));
    }

    /**
     * Test the special handling of special chars in the parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceWithEscaping()
    {
        $fixture = new Model(\Krexx::$pool);

        $fixture->setName('Greg')
            ->setConnectorParameters("<>''")
            ->setConnectorType(ConnectorsConstInterface::CONNECTOR_METHOD)
            ->setCodeGenType(CodegenConstInterface::CODEGEN_TYPE_PUBLIC);
        $this->setValueByReflection('firstRun', false, $this->codegenHandler);


        $this->assertEquals(
            '-&gt;Greg(&lt;&gt;&#039;&#039;)',
            $this->codegenHandler->generateSource($fixture)
        );
    }

    /**
     * Test the small ones.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateWrapperLeft
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateWrapperRight
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::setCodegenAllowed
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::isCodegenAllowed
     */
    public function testSimpleGetterandSetter()
    {
        $this->assertEquals('', $this->codegenHandler->generateWrapperLeft());
        $this->assertEquals('', $this->codegenHandler->generateWrapperRight());
        // This is set during the setUp
        $this->assertEquals(true, $this->codegenHandler->isCodegenAllowed());
        $this->assertEquals(true, $this->codegenHandler->isCodegenAllowed());
    }

    /**
     * Test the multiple enabling / disabling of the code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::setCodegenAllowed
     */
    public function testSetAllowCodegen()
    {
        $this->codegenHandler->setCodegenAllowed(false);
        $this->assertFalse(
            $this->codegenHandler->isCodegenAllowed(),
            'Normal getter test.'
        );
        $this->codegenHandler->setCodegenAllowed(false);
        $this->codegenHandler->setCodegenAllowed(true);
        $this->assertFalse(
            $this->codegenHandler->isCodegenAllowed(),
            'Should still be disabled, because we enabled it ony once.'
        );
        $this->codegenHandler->setCodegenAllowed(true);
        $this->assertTrue(
            $this->codegenHandler->isCodegenAllowed(),
            'Should be enabled, because we enabled it twice.'
        );

        $this->codegenHandler->setCodegenAllowed(true);
        $this->codegenHandler->setCodegenAllowed(false);
        $this->assertFalse(
            $this->codegenHandler->isCodegenAllowed(),
            'Should be disabled, because we are not counting the enableding after 0.'
        );
    }

    /**
     * Test the parameter analysis, with a required parameter.
     * We use a special DateTime parameter as a fixture.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::parameterToString
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     */
    public function testParameterToString()
    {
        $fixture = function (\DateTimeZone $object){};
        $reflectionFunction = new \ReflectionFunction($fixture);
        $reflectionParameter = $reflectionFunction->getParameters()[0];

        $this->assertEquals(
            '\DateTimeZone $object',
            $this->codegenHandler->parameterToString($reflectionParameter)
        );
    }

    /**
     * Test the parameter analysis, with a special default value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::parameterToString
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::retrieveParameterType
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     */
    public function testParameterToStringWithQuotationMarks()
    {
        $refParamMock = $this->createMock(ReflectionParameter::class);
        $refParamMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('greg'));
        $refParamMock->expects($this->once())
            ->method('isDefaultValueAvailable')
            ->will($this->returnValue(true));
        $refParamMock->expects($this->once())
            ->method('getDefaultValue')
            ->will($this->returnValue("some 'string'"));

        $this->assertEquals(
            '$greg = &#039;some \&#039;string\&#039;&#039;',
            $this->codegenHandler->parameterToString($refParamMock)
        );
    }

    /**
     * Test with a bunch of real parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::parameterToString
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::retrieveParameterType
     */
    public function testDefaultValueTranslation()
    {
        $reflection = new \ReflectionClass(MethodParameterFixture::class);
        $reflectionMethod = $reflection->getMethod('arrayDefault');
        $reflectionParameter = $reflectionMethod->getParameters()[0];
        $this->assertEquals(
            'array $parameter = array()',
            $this->codegenHandler->parameterToString($reflectionParameter)
        );

        $reflectionMethod = $reflection->getMethod('trueDefault');
        $reflectionParameter = $reflectionMethod->getParameters()[0];
        $this->assertEquals(
            'bool $parameter = TRUE',
            $this->codegenHandler->parameterToString($reflectionParameter)
        );

        $reflectionMethod = $reflection->getMethod('falseDefault');
        $reflectionParameter = $reflectionMethod->getParameters()[0];
        $this->assertEquals(
            'bool $parameter = FALSE',
            $this->codegenHandler->parameterToString($reflectionParameter)
        );

        $reflectionMethod = $reflection->getMethod('nullDefault');
        $reflectionParameter = $reflectionMethod->getParameters()[0];
        $this->assertEquals(
            '$parameter = NULL',
            $this->codegenHandler->parameterToString($reflectionParameter)
        );

        if (version_compare(phpversion(), '8.0.0', '>=')) {
            // Test for union types.
            $reflection = new \ReflectionClass(UnionTypeFixture::class);
            $reflectionMethod = $reflection->getMethod('unionParameter');
            $reflectionParameter = $reflectionMethod->getParameters()[0];
            $this->assertEquals(
                'array|int|bool $parameter',
                $this->codegenHandler->parameterToString($reflectionParameter)
            );
        }
    }
}
