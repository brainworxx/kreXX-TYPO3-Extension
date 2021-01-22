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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMetaReflections;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class ThroughMetaReflectionsTest extends AbstractTest
{
    /**
     * Test the iteratin through meta reflections.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMetaReflections::callMe
     */
    public function testCallMe()
    {
        $throughMetaRef = new ThroughMetaReflections(Krexx::$pool);

        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMetaReflections::callMe::start', $throughMetaRef]
        );

        // Normally, we are handling reflections here, but meh
        $fixture = [
            $throughMetaRef::PARAM_DATA => [
                'key' => 'value'
            ]
        ];
        Krexx::$pool->rewrite[Meta::class] = CallbackCounter::class;

        $throughMetaRef->setParameters($fixture)->callMe();
        $parameter = CallbackCounter::$staticParameters[0];
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals('value', $parameter[$throughMetaRef::PARAM_REF]);
        $this->assertEquals('key', $parameter[$throughMetaRef::PARAM_META_NAME]);
    }
}
