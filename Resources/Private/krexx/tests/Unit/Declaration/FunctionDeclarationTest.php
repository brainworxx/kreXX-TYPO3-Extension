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

use Brainworxx\Krexx\Analyse\Declaration\FunctionDeclaration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class FunctionDeclarationTest extends AbstractTest
{
    /**
     * Test the retrieval of declaration of simple functions.
     *
     * @covers \Brainworxx\Krexx\Analyse\Declaration\FunctionDeclaration::retrieveDeclaration
     */
    public function testRetrieveDeclaration()
    {
        $functionDeclaration = new FunctionDeclaration(\Krexx::$pool);

        $fixtureA = new \ReflectionFunction('myLittleCallback');
        $result = $functionDeclaration->retrieveDeclaration($fixtureA);
        $this->assertStringContainsString(
            DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Callback.php',
            $result,
            'The actual declaration place in the fixtures.'
        );

        $fixtureB = new \ReflectionFunction('time');
        $result = $functionDeclaration->retrieveDeclaration($fixtureB);
        $this->assertStringEndsWith(
            'predeclared',
            $result,
            'It is predeclared, after all.'
        );
    }
}