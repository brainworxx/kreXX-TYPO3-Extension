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

namespace Brainworxx\Krexx\Tests\Unit\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Registry::class, 'get')]
#[CoversMethod(Registry::class, 'has')]
#[CoversMethod(Registry::class, 'set')]
#[CoversMethod(Registry::class, '__construct')]
class RegistryTest extends AbstractHelper
{
    /**
     * Test the setting of itself in the pool.
     */
    public function testConstruct()
    {
        $registry = new Registry(Krexx::$pool);
        $this->assertSame($registry, Krexx::$pool->registry);
    }

    /**
     * What the method name says.
     */
    public function testSet()
    {
        $registry = new Registry(Krexx::$pool);
        $key = 'key';
        $value = 'value';
        $registry->set($key, $value);
        $this->assertEquals($value, $registry->get($key));
        $registry->set($key, null);
        $this->assertNull($registry->get($key));
    }

    /**
     * What the method name says.
     */
    public function testGetHas()
    {
        $registry = new Registry(Krexx::$pool);
        $key = 'key';
        $value = 'value';
        $this->setValueByReflection('data', [$key => $value], $registry);
        $this->assertEquals($value, $registry->get($key));
        $this->assertNull($registry->get($value));
        $this->assertTrue($registry->has($key));
        $this->assertFalse($registry->has($value));
    }
}
