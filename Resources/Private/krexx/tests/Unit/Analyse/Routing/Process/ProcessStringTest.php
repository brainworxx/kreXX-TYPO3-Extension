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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\FileinfoDummy;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use finfo;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ProcessString::class, '__construct')]
#[CoversMethod(ProcessString::class, 'canHandle')]
#[CoversMethod(ProcessString::class, 'handle')]
#[CoversMethod(ProcessString::class, 'retrieveLengthAndEncoding')]
#[CoversMethod(AbstractRouting::class, 'dispatchProcessEvent')]
#[CoversMethod(ProcessString::class, 'handleStringScalar')]
class ProcessStringTest extends AbstractHelper
{

    public const  BUFFER_INFO = 'bufferInfo';
    public const  ENCODING = 'some encoding';
    public const  ENCODING_PREFIX = 'encoded ';

    /**
     * @var ProcessString
     */
    protected $processString;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processString = new ProcessString(Krexx::$pool);
        $this->mockEmergencyHandler();
    }

    /**
     * Testing the setting of the pool and of the file info class.
     */
    public function testConstructWithoutFinfo()
    {
        // Mock the class_exists method, to return always false.
        $classExistMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
        $classExistMock->expects($this->once())
            ->willReturn(false);

        $processor = new ProcessString(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $processor));
        $this->assertInstanceOf(
            FileinfoDummy::class,
            $this->retrieveValueByReflection(static::BUFFER_INFO, $processor)
        );

        // And while we are at it, test if the internal setting was set.
        $this->assertNotNull($this->retrieveValueByReflection('scalarString', $processor));
        $this->assertNotNull($this->retrieveValueByReflection('analyseScalar', $processor));
    }

    /**
     * Testing the setting of the pool and of the file info class.
     */
    public function testConstructWithFinfo()
    {
        // Mock the class_exists method, to return always true.
        $classExistMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'class_exists');
        $classExistMock->expects($this->once())
            ->willReturn(true);

        $processor = new ProcessString(Krexx::$pool);
        $this->assertInstanceOf(finfo::class, $this->retrieveValueByReflection(static::BUFFER_INFO, $processor));
    }

    /**
     * Testing with a normal short string.
     */
    public function testProcessNormal()
    {
        $fixture = 'short string';
        $model = $this->prepareMocksAndRunTest($fixture . '<');

        $this->assertEquals(ProcessConstInterface::TYPE_STRING, $model->getType());
        $this->assertEquals(13, $model->getJson()['Length']);
        $this->assertEquals($fixture . '&lt;', $model->getNormal());
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey('Mimetype', $model->getJson());
    }

    /**
     * Testing with broken encoding.
     */
    public function testProcessBrokenEncodung()
    {
        $fixture = 'short string';
        $model = $this->prepareMocksAndRunTest($fixture . substr('üä', 1));

        $this->assertEquals(ProcessConstInterface::TYPE_STRING, $model->getType());
        $this->assertEquals(14, $model->getJson()['Length']);
        $this->assertEquals('&#115;&#104;&#111;&#114;&#116;&#32;&#115;&#116;&#114;&#105;&#110;&#103;&#63;&#228;', $model->getNormal());
        $this->assertEquals('broken', $model->getJson()['Encoding']);
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey('Mimetype', $model->getJson());
    }

    /**
     * Testing with a large string.
     */
    public function testProcessLargerString()
    {
        $fixture = 'a string larger than 20 chars';
        $length = strlen($fixture);
        $fileInfo = 'some mimetype';
        $model = $this->prepareMocksAndRunTest($fixture, $fileInfo);

        $this->assertEquals(ProcessConstInterface::TYPE_STRING, $model->getType());
        $this->assertEquals($length, $model->getJson()['Length']);
        $this->assertEquals($fixture, $model->getNormal());
        $this->assertEquals($fileInfo, $model->getJson()['Mimetype string']);
        $this->assertEquals(false, $model->hasExtra());
        $this->assertArrayNotHasKey('Encoding', $model->getJson());
    }

    /**
     * Testing with a string larger than 50 characters.
     */
    public function testProcessHugeString()
    {
        $fixture = 'This is a very large string, bigger than 50 chars. Lorem ipsum and so on, just to fill it up.';
        $length = strlen('>' . $fixture . '<');
        $fileInfo = 'some mimetype';
        $model = $this->prepareMocksAndRunTest('>' . $fixture . '<', $fileInfo);

        $this->assertEquals(ProcessConstInterface::TYPE_STRING, $model->getType());
        $this->assertEquals($length, $model->getJson()['Length']);
        $this->assertEquals(
            '&gt;' . substr($fixture, 0, 49) .  CallbackConstInterface::UNKNOWN_VALUE,
            $model->getNormal()
        );
        $this->assertEquals('&gt;' . $fixture . '&lt;', $model->getData());
        $this->assertEquals($fileInfo, $model->getJson()['Mimetype string']);
        $this->assertEquals(true, $model->hasExtra());
        $this->assertArrayNotHasKey('Encoding', $model->getJson());
    }

    /**
     * Testing with linebreaks in the fixture.
     */
    public function testProcessWithLinebreaks()
    {
        $fixture = 'some' . PHP_EOL . 'string';
        $model = $this->prepareMocksAndRunTest($fixture . '&');

        $this->assertEquals(ProcessConstInterface::TYPE_STRING, $model->getType());
        $this->assertEquals(strlen($fixture . '&'), $model->getJson()['Length']);
        $this->assertEquals(
            $fixture . '&amp;' . CallbackConstInterface::UNKNOWN_VALUE,
            $model->getNormal()
        );
        $this->assertEquals(true, $model->hasExtra());
        $this->assertArrayNotHasKey('Mimetype', $model->getJson());
    }

    /**
     * Testing the triggering of the scalar analysis and its recursion handling.
     */
    public function testProcessWithScalar()
    {
        $fixture = '{"whatever": "okay"}';
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Normal.
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $this->processString->canHandle($model);
        $this->processString->handle();

        // And with a recursion.
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $this->processString->canHandle($model);
        $this->processString->handle();

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

    public function testProcessWithoutScalar()
    {
        // Deactivate the scalar analysis.
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        ConfigSupplier::$overwriteValues[ConfigConstInterface::SETTING_ANALYSE_SCALAR] = false;
        new Config(\Krexx::$pool);

        $fixture = '{"whatever": "okay"}';
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $recursionHandlerMock = $this->createMock(Recursion::class);
        $recursionHandlerMock->expects($this->never())
            ->method('isInMetaHive');
        $recursionHandlerMock->expects($this->never())
            ->method('addToMetaHive');
        Krexx::$pool->recursionHandler = $recursionHandlerMock;

        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        $this->processString = new ProcessString(Krexx::$pool);
        $this->processString->canHandle($model);
        $this->processString->handle();

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
    protected function prepareMocksAndRunTest(string $fixture, $bufferOutput = null): Model
    {
        $fileinfoMock = $this->createMock(finfo::class);
        if (empty($bufferOutput)) {
             $fileinfoMock->expects($this->never())
                ->method('buffer');
        } else {
             $fileinfoMock->expects($this->once())
                ->method('buffer')
                ->with($fixture)
                ->willReturn($bufferOutput);
        }

        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        $this->setValueByReflection(static::BUFFER_INFO, $fileinfoMock, $this->processString);
        $this->mockEventService(
            [ProcessString::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $this->processString->canHandle($model);
        $this->processString->handle();

        return $model;
    }

    /**
     * Test the check if we can handle the array processing.
     */
    public function testCanHandle()
    {
        $processor = new ProcessString(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = 'abc';

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
        $fixture = 50;
        $this->assertFalse($processor->canHandle($model->setData($fixture)));
    }
}
