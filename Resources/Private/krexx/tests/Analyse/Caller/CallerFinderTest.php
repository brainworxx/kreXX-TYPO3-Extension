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
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;

class CallerFinderTest extends AbstractTest
{
    const FUNCTION_TO_TRACE = 'krexx';
    const HEADLINE_STRING = 'A headline';

    /**
     * @var \Brainworxx\Krexx\Analyse\Caller\CallerFinder
     */
    protected $callerFinder;

    /**
     * @var string
     */
    protected $subjectVar = 'string';

    /**
     * @var string
     */
    protected $pathToFixture;

    /**
     * Creating the Caller finder.
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Create our test subject.
        $this->callerFinder = new CallerFinder(Krexx::$pool);
        $this->pathToFixture = DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComplexMethodFixture.php';
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
                ConstInterface::TRACE_FUNCTION => static::FUNCTION_TO_TRACE,
                ConstInterface::TRACE_CLASS => ComplexMethodFixture::class,
                ConstInterface::TRACE_FILE => $classRef->getFileName(),
                ConstInterface::TRACE_LINE => 74
            ]
        ];
    }

    /**
     * Test the setting of the call pattern and the pattern itself.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::__construct
     */
    public function testConstruct()
    {
        $this->assertAttributeEquals('krexx', 'pattern', $this->callerFinder);
        $this->assertAttributeEquals(
            [
                'krexx',
                'krexx::open',
                'krexx::' . Krexx::$pool->config->getDevHandler(),
                'Krexx',
                'Krexx::open',
                'Krexx::' . Krexx::$pool->config->getDevHandler(),
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
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     */
    public function testFindCallerNormal()
    {
        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
        $debugBacktrace->expects($this->once())
            ->willReturn($this->createFixture());

        // Run the test
        $result = $this->callerFinder->findCaller('', $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture, $result[ConstInterface::TRACE_FILE]);
        $this->assertEquals(74, $result[ConstInterface::TRACE_LINE]);
        $this->assertEquals('$parameter', $result[ConstInterface::TRACE_VARNAME]);
        $this->assertEquals('Analysis of $parameter, string', $result[ConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(ConstInterface::TRACE_DATE, $result);
    }

    /**
     * Test with an externally set headline.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     */
    public function testFindCallerHeadline()
    {

        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
        $debugBacktrace->expects($this->once())
            ->willReturn($this->createFixture());

        // Run the test
        $result = $this->callerFinder->findCaller(static::HEADLINE_STRING, $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture, $result[ConstInterface::TRACE_FILE]);
        $this->assertEquals(74, $result[ConstInterface::TRACE_LINE]);
        $this->assertEquals('$parameter', $result[ConstInterface::TRACE_VARNAME]);
        $this->assertEquals(static::HEADLINE_STRING, $result[ConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(ConstInterface::TRACE_DATE, $result);
    }

    /**
     * Test with an source file, that is not readable.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     */
    public function testFindCallerUnreadableSource()
    {
        // Create a fixture.
        $fixture = $this->createFixture();
        $fixture[4][ConstInterface::TRACE_FILE] .= ' file not there';

        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
        $debugBacktrace->expects($this->once())
            ->willReturn($fixture);

        // Run the test
        $result = $this->callerFinder->findCaller(static::HEADLINE_STRING, $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture . ' file not there', $result[ConstInterface::TRACE_FILE]);
        $this->assertEquals(74, $result[ConstInterface::TRACE_LINE]);
        $this->assertEquals('. . .', $result[ConstInterface::TRACE_VARNAME]);
        $this->assertEquals(static::HEADLINE_STRING, $result[ConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(ConstInterface::TRACE_DATE, $result);
    }
}
