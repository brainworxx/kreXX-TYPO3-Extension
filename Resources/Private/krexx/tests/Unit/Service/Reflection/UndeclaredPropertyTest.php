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

use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use stdClass;
use ReflectionClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(UndeclaredProperty::class, '__construct')]
#[CoversMethod(UndeclaredProperty::class, 'isStatic')]
#[CoversMethod(UndeclaredProperty::class, 'getDeclaringClass')]
#[CoversMethod(UndeclaredProperty::class, 'isDefault')]
#[CoversMethod(UndeclaredProperty::class, 'isPrivate')]
#[CoversMethod(UndeclaredProperty::class, 'isProtected')]
#[CoversMethod(UndeclaredProperty::class, 'isPublic')]
#[CoversMethod(UndeclaredProperty::class, 'setIsPublic')]
#[CoversMethod(UndeclaredProperty::class, 'getName')]
#[CoversMethod(UndeclaredProperty::class, '__toString')]
#[CoversMethod(UndeclaredProperty::class, 'getDefaultValue')]
class UndeclaredPropertyTest extends AbstractHelper
{

    /**
     * This one is too simple and I'm too lazy to write test methods for
     * everything. So we test everything at once.
     */
    public function testThemAll()
    {
        $fixture = new stdClass();
        $reflectionClass = new ReflectionClass($fixture);
        $name = 'varname';
        $undeclaredProperty = new UndeclaredProperty($reflectionClass, $name);

        $this->assertSame($reflectionClass, $undeclaredProperty->getDeclaringClass());
        $this->assertSame($name, $undeclaredProperty->getName());
        $this->assertFalse($undeclaredProperty->isStatic());
        $this->assertSame($reflectionClass, $undeclaredProperty->getDeclaringClass());
        $this->assertFalse($undeclaredProperty->isDefault());
        $this->assertFalse($undeclaredProperty->isPrivate());
        $this->assertFalse($undeclaredProperty->isProtected());
        $this->assertTrue($undeclaredProperty->isPublic(), 'Dafault value.');
        $this->assertFalse($undeclaredProperty->setIsPublic(false)->isPublic(), 'Changed value.');
        $this->assertSame($name, $undeclaredProperty->getName());
        $this->assertEmpty($undeclaredProperty->__toString());
        $this->assertNull($undeclaredProperty->getDefaultValue());
    }
}
