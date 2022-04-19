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

namespace Brainworxx\Krexx\Tests\Unit\Service\Flow;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use SplObjectStorage;
use StdClass;

class RecursionTest extends AbstractTest
{

    const RECURSION_HIVE = 'recursionHive';
    /**
     * @var \Brainworxx\Krexx\Service\Flow\Recursion
     */
    protected $recursion;

    /**
     * Create a new recursion handler
     */
    protected function krexxUp()
    {
        parent::krexxUp();

        $this->recursion = new Recursion(Krexx::$pool);
    }

    /**
     * Test the setting of the recursion marker and the creation of the hive.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::__construct
     */
    public function testConstruct()
    {
        $this->assertStringContainsString('Krexx', $this->recursion->getMarker());
        $this->assertTrue($GLOBALS[$this->recursion->getMarker()]);
        $this->assertEquals(
            new SplObjectStorage(),
            $this->retrieveValueByReflection(static::RECURSION_HIVE, $this->recursion)
        );
        $this->assertSame($this->recursion, Krexx::$pool->recursionHandler);
    }

    /**
     * Test the removal of the recursion marker in the globals.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::__destruct
     */
    public function testDestruct()
    {
        $marker = $this->recursion->getMarker();
        unset($this->recursion);
        $this->assertTrue(isset($GLOBALS[$marker]));
    }

    /**
     * Test the adding of classes to the hive.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::addToHive
     */
    public function testAddToHive()
    {
        $fixture = new StdClass();

        $hiveMock = $this->createMock(SplObjectStorage::class);
        $hiveMock->expects($this->once())
            ->method('attach')
            ->with($fixture);
        $this->setValueByReflection(static::RECURSION_HIVE, $hiveMock, $this->recursion);

        $this->recursion->addToHive($fixture);
    }

    /**
     * Test the actual recursion handling.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::isInHive
     */
    public function testIsInHive()
    {
        $fixture = new StdClass();

        $hiveMock = $this->createMock(SplObjectStorage::class);
        $hiveMock->expects($this->once())
            ->method('contains')
            ->with($fixture)
            ->will($this->returnValue(true));
        $this->setValueByReflection(static::RECURSION_HIVE, $hiveMock, $this->recursion);

        $this->assertTrue($this->recursion->isInHive($fixture));
        $this->assertFalse($this->recursion->isInHive(['some', 'array']));
        $this->assertFalse($this->recursion->isInHive($GLOBALS));
        $this->assertTrue($this->recursion->isInHive($GLOBALS), 'Render them a second time');
    }

    /**
     * Test the geter for the marker
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::getMarker
     */
    public function testGetMarker()
    {
        $marker = 'some string';
        $this->setValueByReflection('recursionMarker', $marker, $this->recursion);
        $this->assertEquals($marker, $this->recursion->getMarker());
    }

    /**
     * Test the meta hive.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::isInMetaHive
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
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Recursion::addToMetaHive
     */
    public function testAddToMetaHive()
    {
        $this->recursion->addToMetaHive('key');
        $this->assertEquals(['key' => true], $this->retrieveValueByReflection('metaRecursionHive', $this->recursion));
    }
}
