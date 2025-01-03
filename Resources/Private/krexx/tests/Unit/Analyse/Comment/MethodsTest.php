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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Comment;

use Brainworxx\Krexx\Analyse\Comment\AbstractComment;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\InheritDocFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Methods::class, 'getComment')]
#[CoversMethod(Methods::class, 'getMethodComment')]
#[CoversMethod(AbstractComment::class, 'prettifyComment')]
#[CoversMethod(AbstractComment::class, 'checkComment')]
#[CoversMethod(Methods::class, 'getInterfaceComment')]
#[CoversMethod(Methods::class, 'getTraitComment')]
#[CoversMethod(AbstractComment::class, 'replaceInheritComment')]
#[CoversMethod(Methods::class, 'retrieveComment')]
class MethodsTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected $methodComment;

    /**
     * {@inheritdoc}
     * Creating out class instance to test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->methodComment = new Methods(Krexx::$pool);
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function returnTestResult($className, $methodName)
    {
        $reflectionClass = new ReflectionClass($className);
        return $this->methodComment->getComment($reflectionClass->getMethod($methodName), $reflectionClass);
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Comment in plain sight above method.
     */
    public function testGetComment()
    {
        // Comment in plain sight above method.
        $this->assertEquals(
            'Static function',
            $this->returnTestResult(ComplexMethodFixture::class, 'staticMethod')
        );

        // Comment inherited from underlying class
        $this->assertEquals(
            '&#64;param $parameter',
            $this->returnTestResult(InheritDocFixture::class, 'parameterizedMethod')
        );

        // Multiple comment inheritance from a deeper underlying class
        $this->assertEquals(
            'Do nothing.',
            $this->returnTestResult(InheritDocFixture::class, 'traitComment')
        );

        // Comment from an interface.
        $this->assertEquals(
            'Interface method comment.',
            $this->returnTestResult(InheritDocFixture::class, 'interfaceMethod')
        );

        // Comment from a trait using a trait.
        $this->assertEquals(
            'Do nothing.',
            $this->returnTestResult(ComplexMethodFixture::class, 'traitComment')
        );

        // Unresolvable comment.
        $this->assertEquals(
            '::could not resolve the inherited comment::',
            $this->returnTestResult(InheritDocFixture::class, 'unresolvable')
        );
    }
}
