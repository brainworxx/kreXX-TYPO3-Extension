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

namespace Brainworxx\Krexx\Tests\Unit\Service\Flow;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use SplObjectStorage;
use StdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Recursion::class, 'addToMetaHive')]
#[CoversMethod(Recursion::class, 'isInMetaHive')]
#[CoversMethod(Recursion::class, 'getMarker')]
#[CoversMethod(Recursion::class, 'isInHive')]
#[CoversMethod(Recursion::class, 'addToHive')]
#[CoversMethod(Recursion::class, '__construct')]
class RecursionTest extends AbstractHelper
{
    public const  RECURSION_HIVE = 'recursionHive';

    /**
     * @var Recursion
     */
    protected $recursion;

    /**
     * Create a new recursion handler
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->recursion = new Recursion(Krexx::$pool);
    }

    /**
     * Test the setting of the recursion marker and the creation of the hive.
     */
    public function testConstruct(): void
    {
        $this->assertStringContainsString('Krexx', $this->recursion->getMarker());
        $this->assertEquals(
            new SplObjectStorage(),
            $this->retrieveValueByReflection(static::RECURSION_HIVE, $this->recursion)
        );
        $this->assertSame($this->recursion, Krexx::$pool->recursionHandler);
    }

    /**
     * Test the adding of classes to the hive.
     */
    public function testAddToHive(): void
    {
        $fixture = new StdClass();

        $hiveMock = $this->createMock(SplObjectStorage::class);
        $hiveMock->expects($this->once())
            ->method('offsetSet')
            ->with($fixture);
        $this->setValueByReflection(static::RECURSION_HIVE, $hiveMock, $this->recursion);

        $this->recursion->addToHive($fixture);
    }

    /**
     * Test the actual recursion handling.
     */
    public function testIsInHive(): void
    {
        $fixture = new StdClass();

        $hiveMock = $this->createMock(SplObjectStorage::class);
        $hiveMock->expects($this->once())
            ->method('offsetExists')
            ->with($fixture)
            ->willReturn(true);
        $this->setValueByReflection(static::RECURSION_HIVE, $hiveMock, $this->recursion);

        $this->assertTrue($this->recursion->isInHive($fixture));
        $this->assertFalse($this->recursion->isInHive(['some', 'array']));

        // And now the same thing with an array.
        $fixture = [];
        $this->assertFalse($this->recursion->isInHive($fixture));
        $this->assertFalse($this->recursion->isInHive($fixture), 'We do not track arrays');
    }

    /**
     * Test the geter for the marker
     */
    public function testGetMarker(): void
    {
        $marker = 'some string';
        $this->setValueByReflection('recursionMarker', $marker, $this->recursion);
        $this->assertEquals($marker, $this->recursion->getMarker());
    }

    /**
     * Test the meta hive.
     */
    public function testIsInMetaHive(): void
    {
        $hive = ['marker' => true];
        $this->setValueByReflection('metaRecursionHive', $hive, $this->recursion);
        $this->assertTrue($this->recursion->isInMetaHive('marker'));
        $this->assertFalse($this->recursion->isInMetaHive('what'));
    }

    /**
     * Test the adding of stuff to the meta hive.
     */
    public function testAddToMetaHive(): void
    {
        $this->recursion->addToMetaHive('key');
        $this->assertEquals(['key' => true], $this->retrieveValueByReflection('metaRecursionHive', $this->recursion));
    }
}
