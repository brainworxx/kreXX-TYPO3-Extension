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

namespace Brainworxx\Krexx\Tests\Unit\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Fixtures\TraversableFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\View\Skins\RenderHans;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionClass;

#[CoversMethod(File::class, '__construct')]
#[CoversMethod(File::class, 'readSourcecode')]
#[CoversMethod(File::class, 'getFileContentsArray')]
#[CoversMethod(File::class, 'fileIsReadable')]
#[CoversMethod(File::class, 'realpath')]
#[CoversMethod(File::class, 'readFile')]
#[CoversMethod(File::class, 'getFileContents')]
#[CoversMethod(File::class, 'putFileContents')]
#[CoversMethod(File::class, 'deleteFile')]
#[CoversMethod(File::class, 'filetime')]
class FileTest extends AbstractHelper
{
    public const DOC_ROOT = 'docRoot';
    public const IS_READABLE_CACHE = 'isReadableCache';
    public const FILE_NAME = 'some file';
    public const MISC_NAMESPACE = '\\Brainworxx\\Krexx\\Service\\Misc\\';
    public const CHMOD = 'chmod';
    public const UNLINK = 'unlink';
    public const IS_FILE = 'is_file';

    /**
     * @var \Brainworxx\Krexx\Service\Misc\File
     */
    protected $file;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new File(Krexx::$pool);
        // Make sure we have a doc root, independent of everything.
        $this->setValueByReflection(static::DOC_ROOT, 'doc ruth', $this->file);
        // Reset the writable cache in the file service.
        $this->setValueByReflection(static::IS_READABLE_CACHE, [], $this->file);
        // Mock the realpath of the not existing files.
        $realpath = $this->getFunctionMock(static::MISC_NAMESPACE, 'realpath');
        $realpath->expects($this->any())
            ->willReturnArgument(1);
    }

    /**
     * Setting of the pool and assigning itself to the pool.
     */
    public function testConstruct()
    {
        $this->assertSame($this->file, Krexx::$pool->fileService);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->file));
    }

    /**
     * Test the reading of source code from a fixture file.
     */
    public function testReadSourcecodeNormal()
    {
        $returnValue = 'some string';
        $source = 'source';

        // Mock the renderer.
        $renderMock = $this->createMock(RenderHans::class);
        $renderMock->expects($this->exactly(11))
            ->method('renderBacktraceSourceLine')
            ->with(...$this->withConsecutive(
                [$source, 43, $returnValue],
                [$source, 44, $returnValue],
                [$source, 45, $returnValue],
                [$source, 46, $returnValue],
                ['highlight', 47, $returnValue],
                [$source, 48, $returnValue],
                [$source, 49, $returnValue],
                [$source, 50, $returnValue],
                [$source, 51, $returnValue],
                [$source, 52, $returnValue],
                [$source, 53, $returnValue]
            ))->willReturn('');
        Krexx::$pool->render = $renderMock;

        // Mock the string encoder.
        $encoderMock = $this->createMock(Encoding::class);
        $encoderMock->expects($this->exactly(11))
            ->method('encodeString')
            ->with(...$this->withConsecutive(
                ['class SimpleFixture' . "\n", true],
                ['{' . "\n", true],
                ['    /**' . "\n", true],
                ['     * Value 1' . "\n", true],
                ['     *' . "\n", true],
                ['     * @var int' . "\n", true],
                ['     */' . "\n", true],
                ['    public $value1 = 1;' . "\n", true],
                ["\n", true],
                ['    /**' . "\n", true],
                ['     * Value 2' . "\n", true]
            ))->willReturn($returnValue);
        Krexx::$pool->encodingService = $encoderMock;

        $simpleReflection = new ReflectionClass(SimpleFixture::class);
        $this->file->readSourcecode(
            $simpleReflection->getFileName(),
            46,
            42,
            52
        );
    }

    /**
     * Test the reading of source code from a cached fixture file, with messed
     * up parameters.
     */
    public function testReadSourcecodeMessedUp()
    {
        $simpleReflection = new ReflectionClass(SimpleFixture::class);
        $this->assertEmpty(
            $this->file->readSourcecode(
                $simpleReflection->getFileName(),
                450,
                410,
                510
            ),
            'The file is not that large.'
        );
        $this->assertEmpty(
            $this->file->readSourcecode(
                'none-existing file',
                450,
                410,
                510
            ),
            'The file does not exist.'
        );

        // Now to read with really messed up line numbers.
        // The actual test ist that we do not expect any fatals.
        $this->file->readSourcecode($simpleReflection->getFileName(), 450, -20, -10);
        $this->file->readSourcecode($simpleReflection->getFileName(), 450, 0, 510);
    }

    /**
     * Test the reading of a fixture file into a string.
     */
    public function testReadFile()
    {
        $reflection = new ReflectionClass(TraversableFixture::class);
        $result = $this->file->readFile($reflection->getFileName(), 44, 48);
        $expectation = 'class TraversableFixture implements Iterator' . "\n";
        $expectation .= '{' . "\n";
        $expectation .= '    private $position = 0;' . "\n";
        $expectation .= "\n";
        $expectation .= '    private $array = array(' . "\n";

        $this->assertEquals($expectation, $result, 'Read a file range.');
        $this->assertEquals(
            'class TraversableFixture implements Iterator' . "\n",
            $this->file->readFile($reflection->getFileName(), 44, 44),
            'Read a single line.'
        );

        $this->assertEquals(
            '<?php' . "\n",
            $this->file->readFile($reflection->getFileName(), -41, -45),
            'Test it with nonsense from to stuff.'
        );
    }

    /**
     * Test the direct reading of a file into a string.
     */
    public function testGetFileContents()
    {
        $this->assertEmpty($this->file->getFileContents('whatever'), 'File does not exist');
        $this->assertCount(1, Krexx::$pool->messages->getMessages());
        $this->assertEquals('fileserviceAccess', Krexx::$pool->messages->getMessages()['fileserviceAccess']->getKey());
    }

    /**
     * Test the reading of a file that we can not open (for whatever reason.)
     */
    public function testGetFileContentsWithfailedFile()
    {
        $fOpenMock = $this->getFunctionMock(static::MISC_NAMESPACE, 'fopen');
        $fOpenMock->expects($this->once())
            ->willReturn(false);

        $this->setValueByReflection(static::IS_READABLE_CACHE, ['someFile' => true], $this->file);
        $result = $this->file->getFileContents('someFile', false);
        $this->assertEquals('', $result, 'We do not get any content.');
        $this->assertCount(
            1,
            Krexx::$pool->messages->getMessages(),
            'We get a message that says that reading the file failed.'
        );
    }

    /**
     * Test the wrapper around the file_put_contents in the file handler.
     */
    public function testPutFileContents()
    {
        // We will not really write a file here.
        $filePutContents = $this->getFunctionMock(static::MISC_NAMESPACE, 'file_put_contents');
        $filePutContents->expects($this->once())
            ->willReturn(42);

        $path = 'some file.html';
        $this->file->putFileContents($path, 'some text');
        $this->assertEquals([$path => true], $this->retrieveValueByReflection(static::IS_READABLE_CACHE, $this->file));
    }

    /**
     * Test the deleting of a registered file.
     */
    public function testDeleteFileRegistered()
    {
        // Start the mocking.
        $chmod = $this->getFunctionMock(static::MISC_NAMESPACE, static::CHMOD);
        $chmod->expects($this->never());

        $payload = 'some_file.txt';
        $unlink = $this->getFunctionMock(static::MISC_NAMESPACE, static::UNLINK);
        $unlink->expects($this->once())
            ->with($payload)
            ->willReturn(true);

        // Execute the test.
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection(static::IS_READABLE_CACHE, [$payload => true], $fileService);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals([], Krexx::$pool->messages->getMessages());
    }

    /**
     * Test the deleting of a not existing file.
     */
    public function testDeleteFileNotExisting()
    {
        // Start the mocking.
        $unlink = $this->getFunctionMock(static::MISC_NAMESPACE, static::UNLINK);
        $unlink->expects($this->never());
        $chmod = $this->getFunctionMock(static::MISC_NAMESPACE, static::CHMOD);
        $chmod->expects($this->never());

        // Execute the test.
        $payload = 'not_existing_file.txt';
        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.

        $this->assertEquals(
            [],
            Krexx::$pool->messages->getMessages(),
            'We do not give feedback when trying to delete a none existing file.'
        );
    }

    /**
     * Test the deleting of a unregistered file.
     */
    public function testDeleteFileUnRegistered()
    {
        // Start the mocking.
        $payload = 'unregistered_file.txt';
        $unlink = $this->getFunctionMock(static::MISC_NAMESPACE, static::UNLINK);
        $unlink->expects($this->once())
            ->with($payload)
            ->willReturn(true);
        $chmod = $this->getFunctionMock(static::MISC_NAMESPACE, static::CHMOD);
        $chmod->expects($this->once())
            ->with($payload);

        $isFile = $this->getFunctionMock(static::MISC_NAMESPACE, static::IS_FILE);
        $isFile->expects($this->once())
            ->willReturn(true);

        // Execute the test.
        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals([], Krexx::$pool->messages->getMessages());
    }

    /**
     * Test the deleting of a problematic file.
     */
    public function testDeleteFileWithProblems()
    {
        $isFile = $this->getFunctionMock(static::MISC_NAMESPACE, static::IS_FILE);
        $isFile->expects($this->once())
            ->willReturn(true);

        $payload = 'unregistered_file.txt';
        $chmod = $this->getFunctionMock(static::MISC_NAMESPACE, static::CHMOD);
        $chmod->expects($this->once())
            ->with($payload);

        // Execute the test.

        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertCount(
            1,
            Krexx::$pool->messages->getMessages(),
            'Feedback, that we were unable to delete the file.'
        );
        $message = Krexx::$pool->messages->getMessages()['fileserviceDelete'];
        $this->assertEquals('fileserviceDelete', $message->getKey());
        $this->assertEquals([$payload], $message->getArguments());
    }

    /**
     * Test, if an already registered file is readable.
     */
    public function testFileIsReadableRegistered()
    {
        // Set the stage.
        $filename = static::FILE_NAME;
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection(static::IS_READABLE_CACHE, [$filename => true], $fileService);

        // Run the test.
        $this->assertTrue($fileService->fileIsReadable($filename));
    }

    /**
     * Test, if an already registered file is readable.
     */
    public function testFileIsReadableUnregistered()
    {
        $isFile = $this->getFunctionMock(static::MISC_NAMESPACE, static::IS_FILE);
        $isFile->expects($this->once())
            ->willReturn(true);
        $filename = static::FILE_NAME;
        $isReadable = $this->getFunctionMock(static::MISC_NAMESPACE, 'is_readable');
        $isReadable->expects($this->once())
            ->with($filename)
            ->willReturn(true);

        $fileService = new File(Krexx::$pool);

        $this->assertTrue($fileService->fileIsReadable($filename));
    }

    /**
     * Test if a not existing file is readable.
     */
    public function testFileIsReadableNotExisting()
    {
        $fileService = new File(Krexx::$pool);
        $this->assertFalse($fileService->fileIsReadable('barf'));
    }

    /**
     * Test the getting of a file stamp from an "existing" file.
     */
    public function testFileTimeExisting()
    {
        // Set the stage for an "existing" file.
        $filename = static::FILE_NAME;
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection(static::IS_READABLE_CACHE, [$filename => true], $fileService);

        $filemtime = $this->getFunctionMock(static::MISC_NAMESPACE, 'filemtime');
        $filemtime->expects($this->once())
            ->with($filename)
            ->willReturn(42);

        $this->assertEquals(42, $fileService->filetime($filename));
    }

    /**
     * Test the getting of a file stamp from a not "existing" file.
     */
    public function testFileTimeNotExisting()
    {
        $fileService = new File(Krexx::$pool);
        $filePath = 'I am not here';
        $time = $this->getFunctionMock(static::MISC_NAMESPACE, 'time');
        $time->expects($this->once())
            ->willReturn(41);

        $this->assertEquals(41, $fileService->filetime($filePath));
    }
}
