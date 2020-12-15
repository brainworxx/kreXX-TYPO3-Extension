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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Service\Config\From;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\Validation;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class CookieTest extends AbstractTest
{

    const SETTING_01 = 'setting01';
    const SETTING_02 = 'setting02';
    const VALUE_01 = 'value 1';
    const VALUE_02 = 'value 2';
    const SETTINGS = 'settings';

    /**
     * The test fixture.
     *
     * @var array
     */
    protected $fixture;

    protected function krexxUp()
    {
        parent::krexxUp();

        $this->fixture = [
            static::SETTING_01 => static::VALUE_01,
            static::SETTING_02 => static::VALUE_02
        ];
    }

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
        $this->assertSame(Krexx::$pool->config->validation, $this->retrieveValueByReflection('validation', $cookies));
        $this->assertEquals($this->fixture, $cookies->settings);

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
        $this->assertEquals([], $cookies->settings);
    }

    /**
     * What the method name says.
     *
     * @covers \Brainworxx\Krexx\Service\Config\From\Cookie::getConfigFromCookies
     */
    public function testGetConfigFromCookies()
    {
        $someGroup = 'some group';

        $validationMock = $this->createMock(Validation::class);
        $validationMock->expects($this->exactly(2))
            ->method('evaluateSetting')
            ->withConsecutive(
                [$someGroup, static::SETTING_01, static::VALUE_01],
                [$someGroup, static::SETTING_02, static::VALUE_02]
            )
            ->will(
                $this->returnValueMap(
                    [
                        [$someGroup, static::SETTING_01, static::VALUE_01, true],
                        [$someGroup, static::SETTING_02, static::VALUE_02, false]
                    ]
                )
            );

        $cookies = new Cookie(Krexx::$pool);
        $this->setValueByReflection('validation', $validationMock, $cookies);
        $this->setValueByReflection(static::SETTINGS, $this->fixture, $cookies);
        $this->assertEquals(
            static::VALUE_01,
            $cookies->getConfigFromCookies($someGroup, static::SETTING_01),
            'validation correct'
        );
        $this->assertNull($cookies->getConfigFromCookies($someGroup, static::SETTING_02), 'validation failed');
        $this->assertNull($cookies->getConfigFromCookies($someGroup, 'setting03'), 'an unknown setting');
    }
}
