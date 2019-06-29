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

namespace Brainworxx\Krexx\Tests\Service\Config\From;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class CookieTest extends AbstractTest
{
    /**
     * The test fixture.
     *
     * @var array
     */
    protected $fixture = [
        'setting01' => 'value 1',
        'setting02' => 'value 2'
    ];

    /**
     * Testing the assigning of the validation class and the reading of mocked
     * values.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Cookie::__construct
     */
    public function testConstruct()
    {
        // Run with a working fixture.
        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('getGlobals')
            ->with('_COOKIE')
            ->will($this->returnValue([
                'KrexxDebugSettings' => json_encode($this->fixture)
            ]));
        $poolMock->config = Krexx::$pool->config;

        $cookies = new Cookie($poolMock);
        $this->assertAttributeSame(Krexx::$pool->config->validation, 'validation', $cookies);
        $this->assertAttributeEquals($this->fixture, 'settings', $cookies);

        // Run with a broken fixture.
        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('getGlobals')
            ->with('_COOKIE')
            ->will($this->returnValue([
                'KrexxDebugSettings' => 'a none json string'
            ]));
        $poolMock->config = Krexx::$pool->config;

        $cookies = new Cookie($poolMock);
        $this->assertAttributeEquals([], 'settings', $cookies);
    }

    /**
     * What the method name says.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Cookie::getConfigFromCookies
     */
    public function testGetConfigFromCookies()
    {
        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(2))
            ->method('evaluateSetting')
            ->withConsecutive(
                ['some group', 'setting01', 'value 1'],
                ['some group', 'setting02', 'value 2']
            )
            ->will(
                $this->returnValueMap(
                    [
                        ['some group', 'setting01', 'value 1', true],
                        ['some group', 'setting02', 'value 2', false]
                    ]
                )
            );

        $cookies = new Cookie(Krexx::$pool);
        $this->setValueByReflection('validation', $validationMock, $cookies);
        $this->setValueByReflection('settings', $this->fixture, $cookies);
        $this->assertEquals('value 1', $cookies->getConfigFromCookies('some group', 'setting01'), 'validation correct');
        $this->assertNull($cookies->getConfigFromCookies('some group', 'setting02'), 'validation failed');
        $this->assertNull($cookies->getConfigFromCookies('some group', 'setting03'), 'an unknown setting');
    }
}
