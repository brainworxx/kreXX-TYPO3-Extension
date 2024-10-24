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

namespace Brainworxx\Krexx\Tests\Unit\Service\Reflection;

use Brainworxx\Krexx\Service\Reflection\HiddenProperty;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use DateTime;
use ReflectionClass;

class HiddenPropertyTest extends AbstractHelper
{
    /**
     * Test the setting of the class reflection as well as the
     * special handling of the DateTime anomaly.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\HiddenProperty::__construct
     */
    public function testConstruct()
    {
        $fixture = new ReflectionClass($this);
        $justaName = 'whatever';

        $hiddenProperty = new HiddenProperty($fixture, $justaName);
        $this->assertTrue($hiddenProperty->isPublic(), 'Nearly all hidden propeties are public.');

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects($this->once())
            ->method('getName')
            ->willReturn(DateTime::class);

        $hiddenProperty = new HiddenProperty($reflectionClassMock, $justaName);
        $this->assertFalse($hiddenProperty->isPublic(), 'The DateTime properties are not public.');
    }

    /**
     * @covers \Brainworxx\Krexx\Service\Reflection\HiddenProperty::hasType
     */
    public function testHasType()
    {
        $fixture = new ReflectionClass($this);
        $justaName = 'whatever';

        $hiddenProperty = new HiddenProperty($fixture, $justaName);

        $this->assertFalse($hiddenProperty->hasType());
    }
}