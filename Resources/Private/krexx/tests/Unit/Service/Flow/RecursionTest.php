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
#[CoversMethod(Recursion::class, '__destruct')]
#[CoversMethod(Recursion::class, '__construct')]
class RecursionTest extends AbstractHelper
{
    public const  RECURSION_HIVE = 'recursionHive';

    /**
     * @var \Brainworxx\Krexx\Service\Flow\Recursion
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
    public function testConstruct()
    {
        $this->assertStringContainsString('Krexx', $this->recursion->getMarker());
        if (version_compare(phpversion(), '8.1.0', '<=')) {
            $this->assertTrue(
                $GLOBALS[$this->recursion->getMarker()],
                'The marker should be set in the globals.'
            );
        }
        $this->assertEquals(
            new SplObjectStorage(),
            $this->retrieveValueByReflection(static::RECURSION_HIVE, $this->recursion)
        );
        $this->assertSame($this->recursion, Krexx::$pool->recursionHandler);
    }

    /**
     * Test the removal of the recursion marker in the globals.
     */
    public function testDestruct()
    {
        if (version_compare(phpversion(), '8.1.0', '>=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }
        $marker = $this->recursion->getMarker();
        $this->recursion->__destruct();
        $this->assertFalse(isset($GLOBALS[$marker]));
        $this->setValueByReflection('recursionMarker', $marker, $this->recursion);
    }

    /**
     * Test the adding of classes to the hive.
     */
    public function testAddToHive()
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
    public function testIsInHive()
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
        if (version_compare(phpversion(), '8.1.0', '<=')) {
            $this->assertFalse($this->recursion->isInHive($GLOBALS));
            $this->assertTrue($this->recursion->isInHive($GLOBALS), 'Render them a second time');
        }

        // And now the same thing with an array.
        $fixture = [];
        $this->assertFalse($this->recursion->isInHive($fixture));
        $this->assertFalse($this->recursion->isInHive($fixture), 'We do not track arrays');
        $fixture[$this->recursion->getMarker()] = true;

        if (version_compare(phpversion(), '8.1.0', '>=')) {
            // 8.1.0 does not have globals anymore.
            $this->assertFalse($this->recursion->isInHive($fixture), 'Pretend that this is the global array.');
            $this->assertTrue($this->recursion->isInHive($fixture), 'We did track it.');
        }
    }

    /**
     * Test the geter for the marker
     */
    public function testGetMarker()
    {
        $marker = 'some string';
        $this->setValueByReflection('recursionMarker', $marker, $this->recursion);
        $this->assertEquals($marker, $this->recursion->getMarker());
    }

    /**
     * Test the meta hive.
     */
    public function testIsInMetaHive()
    {
        $hive = ['marker' => true];
        $this->setValueByReflection('metaRecursionHive', $hive, $this->recursion);
        $this->assertTrue($this->recursion->isInMetaHive('marker'));
        $this->assertFalse($this->recursion->isInMetaHive('what'));
    }

    /**
     * Test the adding of stuff to the meta hive.
     */
    public function testAddToMetaHive()
    {
        $this->recursion->addToMetaHive('key');
        $this->assertEquals(['key' => true], $this->retrieveValueByReflection('metaRecursionHive', $this->recursion));
    }
}
