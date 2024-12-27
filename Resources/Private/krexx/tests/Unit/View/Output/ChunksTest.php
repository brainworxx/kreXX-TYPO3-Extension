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

namespace Brainworxx\Krexx\Tests\Unit\View\Output;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\File as ConfigFromFile;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\View\Output\Chunks;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Chunks::class, 'getOfficialEncoding')]
#[CoversMethod(Chunks::class, 'detectEncoding')]
#[CoversMethod(Chunks::class, '__destruct')]
#[CoversMethod(Chunks::class, 'addMetadata')]
#[CoversMethod(Chunks::class, 'isLoggingAllowed')]
#[CoversMethod(Chunks::class, 'setLoggingAllowed')]
#[CoversMethod(Chunks::class, 'isChunkAllowed')]
#[CoversMethod(Chunks::class, 'setChunkAllowed')]
#[CoversMethod(Chunks::class, 'saveDechunkedToFile')]
#[CoversMethod(Chunks::class, 'sendDechunkedToBrowser')]
#[CoversMethod(Chunks::class, 'dechunkMe')]
#[CoversMethod(Chunks::class, 'chunkMe')]
#[CoversMethod(Chunks::class, '__construct')]
class ChunksTest extends AbstractHelper
{
    public const  CHUNK_DIR = 'chunkDir';
    public const  LOG_DIR = 'logDir';
    public const  FILE_STAMP = 'fileStamp';
    public const  PUT_FILE_CONTENTS = 'putFileContents';
    public const  DELETE_FILE = 'deleteFile';
    public const  LOGGING_IS_ALLOWED = 'loggingAllowed';
    public const  OFFICIAL_ENCODING = 'officialEncoding';
    public const  META_DATA = 'metadata';

    /**
     * Test the initialization of a new chunks class.
     */
    public function testConstruct()
    {
        $pool = Krexx::$pool;
        // Mock the configuration.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getChunkDir')
            ->willReturn(static::CHUNK_DIR);
        $configMock->expects($this->once())
            ->method('getLogDir')
            ->willReturn(static::LOG_DIR);
        $pool->config = $configMock;

        // Mock the microtime.
        $microtime = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'microtime');
        $microtime->expects($this->once())
            ->willReturn('0.96136800 1565016056');

        $chunks = new Chunks($pool);
        // Setting of the pool.
        $this->assertSame($pool, $this->retrieveValueByReflection('pool', $chunks));
        // Retrieval of the directories.
        $this->assertEquals(static::CHUNK_DIR, $this->retrieveValueByReflection(static::CHUNK_DIR, $chunks));
        $this->assertEquals(static::LOG_DIR, $this->retrieveValueByReflection(static::LOG_DIR, $chunks));
        // Setting of the file stamp.
        $this->assertEquals('156501605696136800', $this->retrieveValueByReflection(static::FILE_STAMP, $chunks));
        // Assigning itself to the pool.
        $this->assertSame($chunks, $pool->chunks);
    }

    /**
     * Test the adding of a small chunk string
     */
    public function testChunkMeSmall()
    {
        $chunks = new Chunks(Krexx::$pool);
        $fixture = 'very small string';

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->never())
            ->method(static::PUT_FILE_CONTENTS);
        Krexx::$pool->fileService = $fileServiceMock;

        $this->assertEquals($fixture, $chunks->chunkMe($fixture));
    }

    /**
     * Test the adding of a large chunk string, without chunking.
     */
    public function testChunkMeLargeNochunk()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setChunkAllowed(false);

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->never())
            ->method(static::PUT_FILE_CONTENTS);
        Krexx::$pool->fileService = $fileServiceMock;

        $fixture = 'chunkable string';
        $fixture = str_pad($fixture, 10005, '*');

        $this->assertEquals($fixture, $chunks->chunkMe($fixture));
    }

    /**
     * Test the adding of a large chunk string.
     */
    public function testChunkMeLarge()
    {
        $chunks = new Chunks(Krexx::$pool);
        $fixture = 'chunkable string';
        $fixture = str_pad($fixture, 10005, '*');
        $fileStamp = '12345';

        $this->setValueByReflection(static::FILE_STAMP, $fileStamp, $chunks);
        $this->setValueByReflection(static::CHUNK_DIR, 'chunkDir/', $chunks);

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method(static::PUT_FILE_CONTENTS)
            ->with(
                $this->callback(
                    function ($fileName) use ($fileStamp) {
                        $this->assertStringContainsString($fileStamp, $fileName);
                        return true;
                    }
                ),
                $this->callback(
                    function ($contents) use ($fixture) {
                        $this->assertStringContainsString($fixture, $contents);
                        return true;
                    }
                )
            );

        Krexx::$pool->fileService = $fileServiceMock;

        $this->assertStringContainsString('@@@12345_', $chunks->chunkMe($fixture));
    }

    /**
     * Test the sending of the output to the browser.
     */
    public function testSendDechunkedToBrowser()
    {
        // This one is a little bit tricky, because we need to simulate a few
        // chunk files with pointers in them.
        $chunkDir = 'some dir';
        $fileEnding = '.Krexx.tmp';
        $startChunk = 'Murry @@@1234@@@';
        $chunk1Content = 'had @@@1235@@@';
        $chunk1File = $chunkDir . '1234' . $fileEnding;
        $chunk2Content = 'a @@@1236@@@ lampp';
        $chunk2File = $chunkDir . '1235' . $fileEnding;
        $chunk3Content = 'little';
        $chunk3File = $chunkDir . '1236' . $fileEnding;
        $expected = 'Murry had a little lampp';

        // Simulate the actual files.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->with(...$this->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            ))->willReturnMap(
                [
                    [$chunk1File, true, $chunk1Content],
                    [$chunk2File, true, $chunk2Content],
                    [$chunk3File, true, $chunk3Content],
                ]
            );
        $fileServiceMock->expects($this->exactly(3))
            ->method(static::DELETE_FILE)
            ->with(...$this->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            ));
        Krexx::$pool->fileService = $fileServiceMock;

        // Prevent any flushing, so that unit tests can intercept the output.
        $obFlushMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output', 'ob_flush');
        $obFlushMock->expects($this->exactly(4));
        $flushMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output', 'flush');
        $flushMock->expects($this->exactly(4));

        // Create the chunks class and set the simulated chunks directory.
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection(static::CHUNK_DIR, $chunkDir, $chunks);

        // Run the actual test.
        $this->expectOutputString($expected);
        $chunks->sendDechunkedToBrowser($startChunk);
    }

    /**
     * Test the sending of the output to a logfile.
     */
    public function testSaveDechunkedToFile()
    {
        // The tings you do to test your code . . .
        $chunkDir = 'some dir';
        $logDir = 'another dear';
        $fileStamp = 'mauritius';
        $fileEnding = '.Krexx.tmp';
        $startChunk = 'Murry @@@1234@@@';
        $chunk1Content = 'had @@@1235@@@';
        $chunk1File = $chunkDir . '1234' . $fileEnding;
        $chunk2Content = 'a @@@1236@@@ lampp';
        $chunk2File = $chunkDir . '1235' . $fileEnding;
        $chunk3Content = 'little';
        $chunk3File = $chunkDir . '1236' . $fileEnding;
        $logFileName = $logDir . $fileStamp . '.Krexx.html';
        $metaFileName = $logFileName . '.json';
        $metaData = [];
        $metaData['whatever'] = 'some data';

        // Simulate the actual files.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->with(...$this->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File]
            ))->willReturnMap(
                [
                    [$chunk1File, true, $chunk1Content],
                    [$chunk2File, true, $chunk2Content],
                    [$chunk3File, true, $chunk3Content],
                ]
            );
        $fileServiceMock->expects($this->exactly(4))
            ->method(static::DELETE_FILE)
            ->with(...$this->withConsecutive(
                [$chunk1File],
                [$chunk2File],
                [$chunk3File],
                [$metaFileName]
            ));
        $fileServiceMock->expects($this->exactly(5))
            ->method(static::PUT_FILE_CONTENTS)
            ->with(...$this->withConsecutive(
                [$logFileName, 'Murry '],
                [$logFileName, 'had '],
                [$logFileName, 'a '],
                [$logFileName, 'little lampp'],
                [$metaFileName, json_encode($metaData)]
            ));
        Krexx::$pool->fileService = $fileServiceMock;

        // Create the chunks class and set the simulated chunks directory.
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection(static::CHUNK_DIR, $chunkDir, $chunks);
        $this->setValueByReflection(static::LOG_DIR, $logDir, $chunks);
        $this->setValueByReflection(static::FILE_STAMP, $fileStamp, $chunks);
        $this->setValueByReflection(static::META_DATA, $metaData, $chunks);

        // Run the actual test.
        $chunks->saveDechunkedToFile($startChunk);

        // And now with forbidden logging.
        $this->setValueByReflection(static::LOGGING_IS_ALLOWED, false, $chunks);
        $chunks->saveDechunkedToFile($startChunk);
    }

    /**
     * Test the setter for chunk allowance. Pun intended.
     */
    public function testSetChunkAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setChunkAllowed(true);
        $this->assertEquals(true, $chunks->isChunkAllowed());

        $chunks->setChunkAllowed(false);
        $this->assertEquals(false, $chunks->isChunkAllowed());
    }

    /**
     * Test the getter for chunk allowance. Pun intended.
     */
    public function testIsChunkAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection('chunkAllowed', true, $chunks);
        $this->assertTrue($chunks->isChunkAllowed());

        $this->setValueByReflection('chunkAllowed', false, $chunks);
        $this->assertFalse($chunks->isChunkAllowed());
    }

    /**
     * Test the setter for the logging allowance. The puns are killing me.
     */
    public function testSetLoggingAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $chunks->setLoggingAllowed(true);
        $this->assertEquals(true, $chunks->isLoggingAllowed());

        $chunks->setLoggingAllowed(false);
        $this->assertEquals(false, $chunks->isLoggingAllowed());
    }

    /**
     * Test the getter for the logging is allowed. No pun,see?
     */
    public function testIsLoggingAllowed()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection(static::LOGGING_IS_ALLOWED, true, $chunks);
        $this->assertTrue($chunks->isLoggingAllowed());

        $this->setValueByReflection(static::LOGGING_IS_ALLOWED, false, $chunks);
        $this->assertFalse($chunks->isLoggingAllowed());
    }

    /**
     * Test the adding of meta data.
     */
    public function testAddMetaData()
    {
        $metadata = ['some meta stuff'];

        // Test with browser output.
        $chunks = new Chunks(Krexx::$pool);
        $chunks->addMetadata($metadata);
        $this->assertEmpty($this->retrieveValueByReflection(static::META_DATA, $chunks));

        // Test with file output
        ConfigSupplier::$overwriteValues[Fallback::SETTING_DESTINATION] = Fallback::VALUE_FILE;
        Registration::addRewrite(ConfigFromFile::class, ConfigSupplier::class);
        Krexx::$pool = null;
        Pool::createPool();
        $chunks = new Chunks(Krexx::$pool);
        $chunks->addMetadata($metadata);
        $this->assertEquals([$metadata], $this->retrieveValueByReflection(static::META_DATA, $chunks));
    }

    /**
     * Test cleanup of all currently used chunkfiles, in case there was
     * something left. Actually, this should not happen.
     */
    public function testDestruct()
    {
        $fileList = [
          'file 1',
          'file 2',
          'filofax'
        ];
        $chunkDir = 'chunk dir';
        $fileStamp = 'stampede';

        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection(static::CHUNK_DIR, $chunkDir, $chunks);
        $this->setValueByReflection(static::FILE_STAMP, $fileStamp, $chunks);

        $globMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'glob');
        $globMock->expects($this->any())
            ->willReturn($fileList);

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(count($fileList)))
            ->method(static::DELETE_FILE)
            ->with(...$this->withConsecutive(
                [$fileList[0]],
                [$fileList[1]],
                [$fileList[2]]
            ));
        Krexx::$pool->fileService = $fileServiceMock;

        // We need to call this by hand, because it's a singleton, with references
        // at who-knows-where.
        $chunks->__destruct();
    }

    /**
     * Test the cleanup without any directory to clean up.
     */
    public function testDestructWithoutChunkDir()
    {
        $chunks = new Chunks(Krexx::$pool);
        $globMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'glob');
        $globMock->expects($this->never());
        $this->setValueByReflection('chunkDir', '', $chunks);

        $chunks->__destruct();
    }

    /**
     * Test the encoding detection.
     */
    public function testDetectEncoding()
    {
        $chunks = new Chunks(Krexx::$pool);

        $specialEncoding = 'special stuff';
        $string = 'string';
        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
           ->method('mbDetectEncoding')
           ->willReturn($specialEncoding);
        Krexx::$pool->encodingService = $encodingMock;
        $chunks->detectEncoding($string);
        $this->assertEquals($specialEncoding, $this->retrieveValueByReflection(static::OFFICIAL_ENCODING, $chunks));

        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
           ->method('mbDetectEncoding')
           ->willReturn(false);
        Krexx::$pool->encodingService = $encodingMock;
        $chunks->detectEncoding($string);
        $this->assertEquals($specialEncoding, $this->retrieveValueByReflection(static::OFFICIAL_ENCODING, $chunks));
    }

    /**
     * Test the getter for the official encoding.
     */
    public function testGetOfficialEncoding()
    {
        $chunks = new Chunks(Krexx::$pool);
        $this->setValueByReflection(static::OFFICIAL_ENCODING, 'whatever', $chunks);

        $this->assertEquals('whatever', $chunks->getOfficialEncoding());
    }
}
