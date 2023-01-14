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

namespace Brainworxx\Krexx\Tests\Unit\Declaration;

use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\LoggerCallerFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodParameterFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Fixtures\ReturnTypeFixture;
use Brainworxx\Krexx\Tests\Fixtures\UnionTypeFixture;
use ReflectionClass;

class MethodDeclarationTest extends AbstractTest
{
    /**
     * Test the retrieval of declaration of simple functions.
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration::retrieveDeclaration
     * @covers \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration::retrieveDeclaringReflection
     */
    public function testRetrieveDeclaration()
    {
        $methodDeclaration = new MethodDeclaration(\Krexx::$pool);

        // Test with a predeclared method from a predeclared class.
        $reflectionClass = new \ReflectionClass(\DateTime::class);
        $fixture = $reflectionClass->getMethod('format');
        $result = $methodDeclaration->retrieveDeclaration($fixture);
        $this->assertStringEndsWith('is predeclared', $result);

        // Test with a simple class method, without any inheritance.
        $reflectionClass = new \ReflectionClass(ComplexMethodFixture::class);
        $fixture = $reflectionClass->getMethod('finalMethod');
        $result = $methodDeclaration->retrieveDeclaration($fixture);
        $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComplexMethodFixture.php', $result);

        // Doing fancy stuff with traits within traits.
        $fixture = $reflectionClass->getMethod('traitFunction');
        $result = $methodDeclaration->retrieveDeclaration($fixture);
        $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'TraitFixture.php', $result);
    }

    /**
     * Testing the retrieval of the return type by reflections.
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration::retrieveReturnType
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::retrieveNamedType
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::formatNamedType
     */
    public function testRetrieveReturnType()
    {
        $fixture = new ReturnTypeFixture();
        $returnType = new MethodDeclaration(\Krexx::$pool);
        $refClass = new ReflectionClass($fixture);
        $refMethod = $refClass->getMethod('returnBool');
        $this->assertEquals('bool', $returnType->retrieveReturnType($refMethod));

        // Doing PHP 8+ specific tests.
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $fixture = new UnionTypeFixture();
            $refClass = new ReflectionClass($fixture);
            $refMethod = $refClass->getMethod('unionParameter');
            $this->assertEquals('array|int|bool', $returnType->retrieveReturnType($refMethod));
        }
    }

    /**
     * Testing the retrieval of a declared parameter type.
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration::retrieveParameterType
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::retrieveNamedType
     * @covers \Brainworxx\Krexx\Analyse\Declaration\AbstractDeclaration::formatNamedType
     */
    public function testRetrieveParameterType()
    {
        $methodDeclaration = new MethodDeclaration(\Krexx::$pool);

        // No deklaration
        $classReflection = new \ReflectionClass(MethodParameterFixture::class);
        $methodReflection = $classReflection->getMethod('nullDefault');
        $parameterReflection = $methodReflection->getParameters()[0];
        $this->assertEquals('', $methodDeclaration->retrieveParameterType($parameterReflection));

        // Simple type
        $methodReflection = $classReflection->getMethod('falseDefault');
        $parameterReflection = $methodReflection->getParameters()[0];
        $this->assertEquals('bool ', $methodDeclaration->retrieveParameterType($parameterReflection));

        // Namespaced class
        $classReflection = new \ReflectionClass(MethodsFixture::class);
        $methodReflection = $classReflection->getMethod('classMethod');
        $parameterReflection = $methodReflection->getParameters()[0];
        $this->assertEquals(
            '\\' . LoggerCallerFixture::class . ' ',
            $methodDeclaration->retrieveParameterType($parameterReflection),
            'The prefixed class name.'
        );

        // Namespaced unknown class
        $methodReflection = $classReflection->getMethod('troublesomeMethod');
        $parameterReflection = $methodReflection->getParameters()[0];
        $this->assertEquals(
            '\\someNotExistingClass ',
            $methodDeclaration->retrieveParameterType($parameterReflection),
            'The prefixed class name, that does not exist.'
        );

        // Union type
        // Doing PHP 8+ specific tests.
        if (version_compare(phpversion(), '8.0.0', '>=')) {
            $classReflection = new \ReflectionClass(UnionTypeFixture::class);
            $methodReflection = $classReflection->getMethod('unionParameter');
            $parameterReflection = $methodReflection->getParameters()[0];
            $this->assertEquals(
                'array|int|bool ',
                $methodDeclaration->retrieveParameterType($parameterReflection),
                'The not prefixes type list with the | symbol.'
            );
        }
    }
}