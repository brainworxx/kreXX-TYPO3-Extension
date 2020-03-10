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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Analyse\Scalar\ScalarString;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\FileinfoDummy;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use finfo;

class ProcessStringTest extends AbstractTest
{

    const BUFFER_INFO = 'bufferInfo';
    const ENCODING = 'some encoding';
    const ENCODING_PREFIX = 'encoded ';

    /**
     * @var ProcessString
     */
    protected $processString;

    protected function setUp()
    {
        parent::setUp();

        $this->processString = new ProcessString(Krexx::$pool);
        $this->mockEmergencyHandler();
    }

    /**
     * Testing the setting of the pool and of the file info class.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::__construct
     */
    public function testConstructWithoutFinfo()
    {
        // Mock the class_exists method, to return always false.
        $classExistMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
        $classExistMock->expects($this->once())
            ->will($this->returnValue(false));

        $processor = new ProcessString(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $processor));
        $this->assertInstanceOf(
            FileinfoDummy::class,
            $this->retrieveValueByReflection(static::BUFFER_INFO, $processor)
        );
    }

    /**
     * Testing the setting of the pool and of the file info class.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::__construct
     */
    public function testConstructWithFinfo()
    {
        // Mock the class_exists method, to return always true.
        $classExistMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
        $classExistMock->expects($this->once())
            ->will($this->returnValue(true));

        $processor = new ProcessString(Krexx::$pool);
        $this->assertInstanceOf(finfo::class, $this->retrieveValueByReflection(static::BUFFER_INFO, $processor));
    }

    /**
     * Testing with a normal short string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::retrieveLengthAndEncoding
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessNormal()
    {
        $fixture = 'short string';
        $encoding = static::ENCODING;
        $length = 12;
        $model = $this->prepareMocksAndRunTest(
            $fixture,
            $encoding,
            $length
        );

        $this->assertEquals($model::TYPE_STRING . $length, $model->getType());
        $this->assertEquals($length, $model->getJson()[$model::META_LENGTH]);
        $this->assertEquals(static::ENCODING_PREFIX . $fixture, $model->getNormal());
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey($model::META_MIME_TYPE, $model->getJson());
    }

    /**
     * Testing with broken encoding.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::retrieveLengthAndEncoding
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessBrokenEncodung()
    {
        $fixture = 'short string';
        $encoding = false;
        $length = strlen($fixture);
        $model = $this->prepareMocksAndRunTest(
            $fixture,
            $encoding,
            $length
        );

        $this->assertEquals($model::TYPE_STRING .  $length, $model->getType());
        $this->assertEquals($length, $model->getJson()[$model::META_LENGTH]);
        $this->assertEquals(static::ENCODING_PREFIX . $fixture, $model->getNormal());
        $this->assertEquals('broken', $model->getJson()[$model::META_ENCODING]);
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey($model::META_MIME_TYPE, $model->getJson());
    }

    /**
     * Testing with a large string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::retrieveLengthAndEncoding
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessLargerString()
    {
        $fixture = 'a string larger than 20 chars';
        $encoding = static::ENCODING;
        $length = strlen($fixture);
        $fileInfo = 'some mimetype';
        $model = $this->prepareMocksAndRunTest(
            $fixture,
            $encoding,
            $length,
            $fileInfo
        );

        $this->assertEquals($model::TYPE_STRING . $length, $model->getType());
        $this->assertEquals($length, $model->getJson()[$model::META_LENGTH]);
        $this->assertEquals(static::ENCODING_PREFIX . $fixture, $model->getNormal());
        $this->assertEquals($fileInfo, $model->getJson()[$model::META_MIME_TYPE]);
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey($model::META_ENCODING, $model->getJson());
    }

    /**
     * Testing with a string larger than 50 characters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::retrieveLengthAndEncoding
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessHugeString()
    {
        $fixture = 'This is a very large string, bigger than 50 chars. Lorem ipsum and so on, just to fill it up.';
        $encoding = static::ENCODING;
        $length = strlen($fixture);
        $fileInfo = 'some mimetype';
        $model = $this->prepareMocksAndRunTest(
            $fixture,
            $encoding,
            $length,
            $fileInfo
        );

        $this->assertEquals($model::TYPE_STRING . $length, $model->getType());
        $this->assertEquals($length, $model->getJson()[$model::META_LENGTH]);
        $this->assertEquals(
            static::ENCODING_PREFIX . substr($fixture, 0, 50) .  $model::UNKNOWN_VALUE,
            $model->getNormal()
        );
        $this->assertEquals(static::ENCODING_PREFIX . $fixture, $model->getData());
        $this->assertEquals($fileInfo, $model->getJson()[$model::META_MIME_TYPE]);
        $this->assertEquals(true, $model->hasExtra());
        $this->assertArrayNotHasKey($model::META_ENCODING, $model->getJson());
    }

    /**
     * Testing with linebreaks in the fixture.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::retrieveLengthAndEncoding
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessWithLinebreaks()
    {
        $fixture = 'some' . PHP_EOL . 'string';
        $encoding = static::ENCODING;
        $length = 12;
        $model = $this->prepareMocksAndRunTest(
            $fixture,
            $encoding,
            $length
        );

        $this->assertEquals($model::TYPE_STRING . $length, $model->getType());
        $this->assertEquals($length, $model->getJson()[$model::META_LENGTH]);
        $this->assertEquals(static::ENCODING_PREFIX . $fixture . $model::UNKNOWN_VALUE, $model->getNormal());
        $this->assertEquals(true, $model->hasExtra());
        $this->assertArrayNotHasKey($model::META_MIME_TYPE, $model->getJson());
    }

    /**
     * Testing the triggering of the scalar analysis and its recursion handling.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::process
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString::handleStringScalar
     */
    public function testProcessWithScalar()
    {
        // Activate the scalar analysis.
        Krexx::$pool->rewrite[Ini::class] = ConfigSupplier::class;
        ConfigSupplier::$overwriteValues[Fallback::SETTING_ANALYSE_SCALAR] = 'true';
        new Config(\Krexx::$pool);

        $fixture = '{"whatever": "okay"}';
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Normal.
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $this->processString->process($model);

        // And with a recursion.
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $this->processString->process($model);

        $this->assertCount(
            1,
            $renderNothing->model['renderRecursion'],
            'We should have something in the recursion array.'
        );
        $this->assertCount(
            1,
            $renderNothing->model['renderExpandableChild'],
            'The first one should be in the expandable child.'
        );
    }

    /**
     * Prepare the mocks and run the test. Nice, huh?
     * The things you do to prevent a bad rating . . .
     *
     * @param string $fixture
     * @param $encoding
     * @param int $length
     * @param $bufferOutput
     *
     * @return \Brainworxx\Krexx\Analyse\Model
     */
    protected function prepareMocksAndRunTest(string $fixture, $encoding, int $length, $bufferOutput = null): Model
    {
        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
            ->method('mbDetectEncoding')
            ->with($fixture)
            ->will($this->returnValue($encoding));
        $encodingMock->expects($this->once())
            ->method('mbStrLen')
            ->with($fixture)
            ->will($this->returnValue($length));
        if ($length > 50 || strpos($fixture, PHP_EOL) !== false) {
            $cut = substr($fixture, 0, 50);
            $encodingMock->expects($this->exactly(2))
                ->method('encodeString')
                ->withConsecutive(
                    [$cut],
                    [$fixture]
                )
                ->will($this->returnValueMap([
                    [$cut, false, static::ENCODING_PREFIX . $cut],
                    [$fixture, false, static::ENCODING_PREFIX . $fixture]
                ]));

            $encodingMock->expects($this->once())
                ->method('mbSubStr')
                ->with($fixture, 0, 50)
                ->will($this->returnValue($cut));
        } else {
            $encodingMock->expects($this->never())
                ->method('mbSubStr');

            $encodingMock->expects($this->once())
                ->method('encodeString')
                ->with($fixture)
                ->will($this->returnValue(static::ENCODING_PREFIX . $fixture));
        }
        Krexx::$pool->encodingService = $encodingMock;

        $fileinfoMock = $this->createMock(finfo::class);
        if (empty($bufferOutput)) {
             $fileinfoMock->expects($this->never())
                ->method('buffer');
        } else {
             $fileinfoMock->expects($this->once())
                ->method('buffer')
                ->with($fixture)
                ->will($this->returnValue($bufferOutput));
        }

        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        $this->setValueByReflection(static::BUFFER_INFO, $fileinfoMock, $this->processString);
        $this->mockEventService(
            [ProcessString::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $this->processString->process($model);

        return $model;
    }
}
