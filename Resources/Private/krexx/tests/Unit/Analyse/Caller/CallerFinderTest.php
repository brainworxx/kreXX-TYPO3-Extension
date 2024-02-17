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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Caller;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\LoggerCallerFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use ReflectionClass;

class CallerFinderTest extends AbstractHelper
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
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockDebugBacktrace()
    {
        return $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
    }

    /**
     * Creating the Caller finder.
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Prepare the uri.
        // The things you do, to mock an uri call . . .
        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue([
                'SERVER_PROTOCOL' => 'abcd/',
                'SERVER_PORT' => 123,
                'SERVER_NAME' => 'localhorst',
                'HTTPS' => 'on',
                'REQUEST_URI' => 'some/uri'
            ]));
        $poolMock->fileService = Krexx::$pool->fileService;
        $poolMock->encodingService = Krexx::$pool->encodingService;
        $poolMock->config = Krexx::$pool->config;
        $poolMock->emergencyHandler = Krexx::$pool->emergencyHandler;
        $poolMock->messages = Krexx::$pool->messages;
        $poolMock->expects($this->any())
            ->method('createClass')
            ->will($this->returnCallback(function ($classname) {
                return Krexx::$pool->createClass($classname);
            }));

        // Create our test subject.
        $this->callerFinder = new CallerFinder($poolMock);
        $this->pathToFixture = DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'ComplexMethodFixture.php';
    }

    /**
     * Return the fixture.
     *
     * @param int $line
     *
     * @return array
     */
    protected function createFixture($line)
    {
        $classRef = new ReflectionClass(ComplexMethodFixture::class);
        return [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [
                BacktraceConstInterface::TRACE_FUNCTION => static::FUNCTION_TO_TRACE,
                BacktraceConstInterface::TRACE_CLASS => ComplexMethodFixture::class,
                BacktraceConstInterface::TRACE_FILE => $classRef->getFileName(),
                BacktraceConstInterface::TRACE_LINE => $line
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
        $krexx = 'krexx';
        $this->assertEquals($krexx, $this->callerFinder->getPattern());
        $this->assertEquals(
            [
                $krexx,
                'krexxlog',
                'krexx::open',
                'Krexx',
                'Krexxlog',
                'Krexx::open',
                'Krexx::log',
                'krexx::log',
            ],
            $this->retrieveValueByReflection('callPattern', $this->callerFinder)
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
     * @covers \Brainworxx\Krexx\Analyse\Caller\AbstractCaller::getCurrentUrl
     *
     * @throws \ReflectionException
     */
    public function testFindCallerNormal()
    {
        $this->mockDebugBacktrace()
            ->expects($this->once())
            ->willReturn($this->createFixture(75));

        // Run the test
        $result = $this->callerFinder->findCaller('', $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture, $result[BacktraceConstInterface::TRACE_FILE]);
        $this->assertEquals(75, $result[BacktraceConstInterface::TRACE_LINE]);
        $this->assertEquals('$parameter', $result[BacktraceConstInterface::TRACE_VARNAME]);
        $this->assertEquals('Analysis of $parameter, string', $result[BacktraceConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(BacktraceConstInterface::TRACE_DATE, $result);
        $this->assertEquals('abcds://localhorst:123some/uri', $result[BacktraceConstInterface::TRACE_URL]);
    }

    /**
     * Test the resolving of inline calles of kreXX.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\AbstractCaller::getType
     *
     * @throws \ReflectionException
     */
    public function testFindCallerInline()
    {
        $this->mockDebugBacktrace()
            ->expects($this->once())
            ->willReturn($this->createFixture(85));

        // Run the test
        $result = $this->callerFinder->findCaller('', 1.2345);

        // We only need to check the var name.
        $this->assertEquals(
            '$this-&gt;parameterizedMethod(&#039;()&quot;2&#039;)',
            $result[BacktraceConstInterface::TRACE_VARNAME]
        );
        $this->assertEquals(
            'Analysis of $this-&gt;parameterizedMethod(&#039;()&quot;2&#039;), float',
            $result[BacktraceConstInterface::TRACE_TYPE]
        );
    }

    /**
     * Test with an externally set headline.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     *
     * @throws \ReflectionException
     */
    public function testFindCallerHeadline()
    {

        $this->mockDebugBacktrace()
            ->expects($this->once())
            ->willReturn($this->createFixture(75));

        // Run the test
        $result = $this->callerFinder->findCaller(static::HEADLINE_STRING, $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture, $result[BacktraceConstInterface::TRACE_FILE]);
        $this->assertEquals(75, $result[BacktraceConstInterface::TRACE_LINE]);
        $this->assertEquals(static::HEADLINE_STRING, $result[BacktraceConstInterface::TRACE_VARNAME]);
        $this->assertEquals(static::HEADLINE_STRING, $result[BacktraceConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(BacktraceConstInterface::TRACE_DATE, $result);
    }

    /**
     * Test with an source file, that is not readable.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     *
     * @throws \ReflectionException
     */
    public function testFindCallerUnreadableSource()
    {
        // Create a fixture.
        $fixture = $this->createFixture(74);
        $fixture[4][BacktraceConstInterface::TRACE_FILE] .= ' file not there';

        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
        $debugBacktrace->expects($this->once())
            ->willReturn($fixture);

        // Run the test
        $result = $this->callerFinder->findCaller('', $this->subjectVar);

        // Check the result
        $this->assertStringEndsWith($this->pathToFixture . ' file not there', $result[BacktraceConstInterface::TRACE_FILE]);
        $this->assertEquals(74, $result[BacktraceConstInterface::TRACE_LINE]);
        $this->assertEquals('. . .', $result[BacktraceConstInterface::TRACE_VARNAME]);
        $this->assertEquals('Analysis of . . ., string', $result[BacktraceConstInterface::TRACE_TYPE]);
        $this->assertArrayHasKey(BacktraceConstInterface::TRACE_DATE, $result);
    }

    /**
     * Test the caller finder with the forced logger.
     *
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::findCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getVarName
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::getType
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::identifyCaller
     * @covers \Brainworxx\Krexx\Analyse\Caller\CallerFinder::removeKrexxPartFromCommand
     */
    public function testFindCallerLogging()
    {
        $classRef = new ReflectionClass(LoggerCallerFixture::class);
        $fixture = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [
                BacktraceConstInterface::TRACE_FUNCTION => static::FUNCTION_TO_TRACE,
                BacktraceConstInterface::TRACE_CLASS => LoggerCallerFixture::class,
                BacktraceConstInterface::TRACE_FILE => $classRef->getFileName(),
                BacktraceConstInterface::TRACE_LINE => 47
            ]
        ];

        $this->mockDebugBacktrace()
            ->expects($this->once())
            ->willReturn($fixture);

        // Run the test
        $result = $this->callerFinder->findCaller('', $this->subjectVar);
        $this->assertEquals(47, $result[BacktraceConstInterface::TRACE_LINE]);
        $this->assertEquals('some value', $result[BacktraceConstInterface::TRACE_VARNAME]);
    }
}
