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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Comment;

use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use Brainworxx\Krexx\Tests\Fixtures\ReturnTypeFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Krexx;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionFunction;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ReturnType::class, 'getComment')]
#[CoversMethod(ReturnType::class, 'retrieveReturnTypeFromComment')]
class ReturnTypesTest extends AbstractHelper
{
    /**
     * Test the retrieval of different return types from different sources.
     *
     * @see \Brainworxx\Krexx\Tests\Fixtures\ReturnTypeFixture
     */
    public function testGetComment()
    {
        $fixture = new ReturnTypeFixture();
        $returnType = new ReturnType(Krexx::$pool);
        $refClass = new ReflectionClass($fixture);

        $refMethod = $refClass->getMethod('returnSelf');
        $this->assertEquals('\\' . ReturnTypeFixture::class, $returnType->getComment($refMethod, $refClass));

        $refMethod = $refClass->getMethod('returnThis');
        $this->assertEquals('\\' . ReturnTypeFixture::class, $returnType->getComment($refMethod, $refClass));

        $refMethod = $refClass->getMethod('returnNothing');
        $this->assertEquals('', $returnType->getComment($refMethod, $refClass));

        $refMethod = $refClass->getMethod('injectionAttempt');
        $this->assertEquals('\&lt;h1&gt;Headline!&lt;/h1&gt;', $returnType->getComment($refMethod, $refClass));

        $refMethod = $refClass->getMethod('trashComment');
        $this->assertEquals('', $returnType->getComment($refMethod, $refClass));

        $refMethod = $refClass->getMethod('multipleTypes');
        $this->assertEquals('bool|null', $returnType->getComment($refMethod, $refClass));

        $refFunction = new ReflectionFunction('myLittleCallback');
        $this->assertEquals('string', $returnType->getComment($refFunction));

        $refFunction = new ReflectionFunction('justAnotherFunction');
        $this->assertEquals('', $returnType->getComment($refFunction));

        $refFunction = new ReflectionFunction('returnString');
        $this->assertEquals('bool', $returnType->getComment($refFunction));
    }
}
