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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Caller;

use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class AbstractCallerTest extends AbstractTest
{
    /**
     * Testing the setting of the pool
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\AbstractCaller::__construct
     */
    public function test__construct()
    {
        $callerFinder = new CallerFinder(\Krexx::$pool);
        $this->assertAttributeEquals(\Krexx::$pool, 'pool', $callerFinder);
    }

    /**
     * Testing the setting of the pattern
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\AbstractCaller::setPattern
     */
    public function testSetPattern()
    {
        $callerFinder = new CallerFinder(\Krexx::$pool);
        $callerFinder->setPattern('some pattern');
        $this->assertAttributeEquals('some pattern', 'pattern', $callerFinder);
    }

    /**
     * Test the getting of the pattern.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\AbstractCaller::getPattern
     */
    public function testGetPattern()
    {
        $callerFinder = new CallerFinder(\Krexx::$pool);

        $this->setValueByReflection('pattern', 'some pattern', $callerFinder);
        $this->assertEquals('some pattern', $callerFinder->getPattern());
    }
}