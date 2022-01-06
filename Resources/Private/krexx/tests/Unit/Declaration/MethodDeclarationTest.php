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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

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
        $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComplexMethodFixture.php', $result);

        // Doing fancy stuff with traits within traits.
        $fixture = $reflectionClass->getMethod('traitFunction');
        $result = $methodDeclaration->retrieveDeclaration($fixture);
        $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'TraitFixture.php', $result);
    }
}