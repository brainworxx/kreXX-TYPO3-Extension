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

namespace Unit\Plugins\FluidDebugger\Rewrites;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ConstantsFixture71;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Getter::class, 'callMe')]
#[CoversMethod(Getter::class, 'retrieveMethodList')]
class GetterTest extends AbstractHelper implements CallbackConstInterface
{
    /**
     * Testing the fluid rendering with no getter at all.
     *
     */
    public function testCallMeEmpty()
    {
        $getter = new Getter(\Krexx::$pool);
        $fixture = [static::PARAM_REF => new ReflectionClass(ConstantsFixture71::class)];
        $getter->setParameters($fixture);

        $this->assertSame('', $getter->callMe(), 'We expect no getter, hence no output, hence empty string.');
    }

    /**
     * Normal test.
     */
    public function testCallMe()
    {
        $getter = new Getter(\Krexx::$pool);
        $ref = new ReflectionClass(GetterFixture::class);
        $fixture = [static::PARAM_REF => $ref];
        $getter->setParameters($fixture);
        \Krexx::$pool->rewrite[ThroughGetter::class] = CallbackCounter::class;

        $result = $getter->callMe();
        $this->assertStringNotContainsString('<span class="kname">Getter</span>', $result);
        $this->assertSame(1, CallbackCounter::$counter, 'Only call it once!');
        $params = CallbackCounter::$staticParameters[0];

        $this->assertSame($ref, $params[static::PARAM_REF], 'The fixture must be found in the class to test.');
        $this->assertInstanceOf(\ReflectionMethod::class, $params['normalGetter'][0]);
        $this->assertInstanceOf(\ReflectionMethod::class, $params['isGetter'][0]);
        $this->assertInstanceOf(\ReflectionMethod::class, $params['hasGetter'][0]);
    }
}
