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

namespace Brainworxx\Krexx\Tests\Unit\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\File as ConfigFromFile;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\Cleanup;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\View\Output\Chunks;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Cleanup::class, 'cleanupOldChunks')]
#[CoversMethod(Cleanup::class, 'cleanupOldLogs')]
#[CoversMethod(Cleanup::class, '__construct')]
class CleanupTest extends AbstractHelper
{
    public const  CHUNKS_DONE = 'chunksDone';
    public const  MISC_NAMESPACE = '\\Brainworxx\\Krexx\\Service\\Misc\\';
    public const  GET_LOGGING_IS_ALLOWED = 'isLoggingAllowed';

    protected $cleanup;

    protected function mockGlob()
    {
        return $this->getFunctionMock(static::MISC_NAMESPACE, 'glob');
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup = new Cleanup(Krexx::$pool);
        $this->setValueByReflection(static::CHUNKS_DONE, false, $this->cleanup);
    }

    /**
     * Test the setting of the pool
     */
    public function testConstruct()
    {
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->cleanup));
    }

    /**
     * Test the cleanup of log folders, when logging is not allowed.
     */
    public function testCleanupOldLogsNoLogging()
    {
        // Logging is not allowed.
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method(static::GET_LOGGING_IS_ALLOWED)
            ->willReturn(false);
        Krexx::$pool->chunks = $chunksMock;

        // The log directory will not get globbed.
        $this->mockGlob()->expects($this->never());

        $this->cleanup->cleanupOldLogs();
    }

    /**
     * Test the cleanup of the log folder, when it is empty.
     */
    public function testCleanupOldLogsNoLogs()
    {
        $logDir = 'some dir';

        // Logging is allowed.
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method(static::GET_LOGGING_IS_ALLOWED)
            ->willReturn(true);
        Krexx::$pool->chunks = $chunksMock;

        // No logs stored.
        $this->mockGlob()->expects($this->once())
            ->with($logDir . '*.Krexx.html')
            ->willReturn([]);

        // Nothing to sort, because of an early return.
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('getSetting');
        $configMock->expects($this->once())
            ->method('getLogDir')
            ->willReturn($logDir);
        Krexx::$pool->config = $configMock;

        $this->cleanup->cleanupOldLogs();
    }

    /**
     * Test the cleanup of old logfiles, with mocked up files.
     */
    public function testCleanupOldLogsNormal()
    {
        $logDir = 'some dir';
        $file1 = 'file 1';
        $file2 = 'file 2';
        $file3 = 'file 3';

        $this->mockGlob()->expects($this->once())
            ->with($logDir . '*.Krexx.html')
            ->willReturn([$file1, $file2, $file3]);

        // Prepare the configuration
        ConfigSupplier::$overwriteValues[Fallback::SETTING_MAX_FILES] = '1';
        Registration::addRewrite(ConfigFromFile::class, ConfigSupplier::class);
        Registration::setLogFolder($logDir);
        Krexx::$pool = null;
        Pool::createPool();

        // Logging is allowed.
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method(static::GET_LOGGING_IS_ALLOWED)
            ->willReturn(true);
        Krexx::$pool->chunks = $chunksMock;

        // Test the retrieval of the file time.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('filetime')
            ->with(...$this->withConsecutive(
                [$file1],
                [$file2],
                [$file3]
            ))->willReturnMap(
                [
                    [$file1, 999],
                    [$file2, 123],
                    [$file3, 789]
                ]
            );

        // Test the deleting of the two oldest files (2 and 3).
        $fileServiceMock->expects($this->exactly(4))
            ->method('deleteFile')
            ->with(...$this->withConsecutive(
                [$file3],
                [$file3 . '.json'],
                [$file2],
                [$file2 . '.json']
            ));

        // Inject hte mock
        Krexx::$pool->fileService = $fileServiceMock;

        // Run the test.
        $this->cleanup = new Cleanup(\Krexx::$pool);
        $this->cleanup->cleanupOldLogs();
    }

    /**
     * Test the cleanup of old chunks, when we have no write access.
     */
    public function testCleanupOldChunksNoWriteAccess()
    {
        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('isChunkAllowed')
            ->willReturn(false);
        Krexx::$pool->chunks = $chunksMock;

        $this->cleanup->cleanupOldChunks();
        $this->assertEquals(
            false,
            $this->retrieveValueByReflection(static::CHUNKS_DONE, $this->cleanup),
            'did not remove any chunks, should be untouched.'
        );
    }

    /**
     * Test the cleanup of old chunks, when we have no write access.
     */
    public function testCleanupOldChunksNormal()
    {
        $chunkDir = 'extra chunky';
        $file1 = 'file 1';
        $file2 = 'file 2';
        $file3 = 'file 3';

        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('isChunkAllowed')
            ->willReturn(true);
        Krexx::$pool->chunks = $chunksMock;

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getChunkDir')
            ->willReturn($chunkDir);
        Krexx::$pool->config = $configMock;

        $this->mockGlob()->expects($this->once())
            ->with($chunkDir . '*.Krexx.tmp')
            ->willReturn([$file1, $file2, $file3]);
        $time = $this->getFunctionMock(static::MISC_NAMESPACE, 'time');
        $time->expects($this->once())
            ->willReturn(10000);

        // Test the retrieval of the file time.
        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('filetime')
            ->with(...$this->withConsecutive(
                [$file1],
                [$file2],
                [$file3]
            ))->willReturnMap(
                [
                    [$file1, 999999],
                    [$file2, 100],
                    [$file3, 200]
                ]
            );

        // Test the deleting of the two oldest files 2 and 3, while 1 is too new.
        $fileServiceMock->expects($this->exactly(2))
            ->method('deleteFile')
            ->with(...$this->withConsecutive(
                [$file2],
                [$file3]
            ));

        Krexx::$pool->fileService = $fileServiceMock;

        $this->cleanup->cleanupOldChunks();
        $this->assertEquals(
            true,
            $this->retrieveValueByReflection(static::CHUNKS_DONE, $this->cleanup),
            'Remember, that we did this before.'
        );
    }
}
