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
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class CallerFinderTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Caller\CallerFinder
     */
    protected $callerFinder;

    /**
     * @var string
     */
    protected $subjectVar = 'string';

    /**
     * Creating the Caller finder.
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Create our test subject.
        $this->callerFinder = new CallerFinder(\Krexx::$pool);
    }

    /**
     * Return the fixture.
     *
     * @return array
     */
    protected function createFixture()
    {
        $classRef = new \ReflectionClass(ComplexMethodFixture::class);
        return [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [
                'function' => 'krexx',
                'class' => ComplexMethodFixture::class,
                'file' => $classRef->getFileName(),
                'line' => 69
            ]
        ];
    }

    /**
     * Test the setting of the call pattern and the pattern itself.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::__construct
     */
    public function test__construct()
    {
        $this->assertAttributeEquals('krexx', 'pattern', $this->callerFinder);
        $this->assertAttributeEquals(
            [
                'krexx',
                'krexx::open',
                'krexx::' . \Krexx::$pool->config->getDevHandler(),
                'Krexx',
                'Krexx::open',
                'Krexx::' . \Krexx::$pool->config->getDevHandler(),
                'Krexx::log',
                'krexx::log',
            ],
            'callPattern',
            $this->callerFinder
        );
    }

    /**
     * Test normally, without any outside iterference, the way it is normally
     * exrcuted
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     */
    public function testFindCallerNormal()
    {
        \Brainworxx\Krexx\Analyse\Caller\debug_backtrace(
            'xxx',
            'xxx',
            $this->createFixture()
        );

        // Run the test
        $result = $this->callerFinder->findCaller('', $this->subjectVar);

        // Check the result
        $this->assertEquals('.../tests/Fixtures/ComplexMethodFixture.php', $result['file']);
        $this->assertEquals(69, $result['line']);
        $this->assertEquals('$parameter', $result['varname']);
        $this->assertEquals('Analysis of $parameter, string', $result['type']);
    }

    /**
     * Test with an externally set headline.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     */
    public function testFindCallerHeadline()
    {
        \Brainworxx\Krexx\Analyse\Caller\debug_backtrace(
            'xxx',
            'xxx',
            $this->createFixture()
        );

        // Run the test
        $result = $this->callerFinder->findCaller('A headline', $this->subjectVar);

        // Check the result
        $this->assertEquals('.../tests/Fixtures/ComplexMethodFixture.php', $result['file']);
        $this->assertEquals(69, $result['line']);
        $this->assertEquals('$parameter', $result['varname']);
        $this->assertEquals('A headline', $result['type']);
    }

    /**
     * Test with an source file, that is not readable.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     */
    public function testFindCallerUnreadableSource()
    {
        // Create a fixture.
        $fixture = $this->createFixture();
        $fixture[4]['file'] .= ' file not there';

        \Brainworxx\Krexx\Analyse\Caller\debug_backtrace(
            'xxx',
            'xxx',
            $fixture
        );

        // Run the test
        $result = $this->callerFinder->findCaller('A headline', $this->subjectVar);

        // Check the result
        $this->assertEquals('.../tests/Fixtures/ComplexMethodFixture.php file not there', $result['file']);
        $this->assertEquals(69, $result['line']);
        $this->assertEquals('. . .', $result['varname']);
        $this->assertEquals('A headline', $result['type']);
    }
}
