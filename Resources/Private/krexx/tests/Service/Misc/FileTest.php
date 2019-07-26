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

namespace Brainworxx\Krexx\Tests\Service\Misc;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Fixtures\TraversableFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Render;
use ReflectionClass;

class FileTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Service\Misc\File
     */
    protected $file;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->file = new File(Krexx::$pool);
        // Make sure we have a doc root, independent from everything.
        $this->setValueByReflection('docRoot', 'doc ruth', $this->file);
        // Reset the writable cache in the file service.
        $this->setValueByReflection('isReadableCache', [], $this->file);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        // undo the mocking.
        \Brainworxx\Krexx\Service\Misc\unlink('', false);
        \Brainworxx\Krexx\Service\Misc\chmod('', 0777, false);
        \Brainworxx\Krexx\Service\Misc\realpath('', false);
        \Brainworxx\Krexx\Service\Misc\is_file('', false);
        \Brainworxx\Krexx\Service\Misc\is_file('', false);
        \Brainworxx\Krexx\Service\Misc\filemtime('', false);
        \Brainworxx\Krexx\Service\Misc\time(false);
    }

    /**
     * Setting of the pool and assigning itself to the pool.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::__construct
     */
    public function testConstruct()
    {
        $this->assertSame($this->file, Krexx::$pool->fileService);
        $this->assertAttributeSame(Krexx::$pool, 'pool', $this->file);
    }

    /**
     * Test the reading of source code from a fixture file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::readSourcecode
     * @covers \Brainworxx\Krexx\Service\Misc\File::getFileContentsArray
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
     */
    public function testReadSourcecodeNormal()
    {
        $returnValue = 'some string';
        $source = 'source';

        // Mock the renderer.
        $renderMock = $this->createMock(Render::class);
        $renderMock->expects($this->exactly(11))
            ->method('renderBacktraceSourceLine')
            ->withConsecutive(
                [$source, 42, $returnValue],
                [$source, 43, $returnValue],
                [$source, 44, $returnValue],
                [$source, 45, $returnValue],
                ['highlight', 46, $returnValue],
                [$source, 47, $returnValue],
                [$source, 48, $returnValue],
                [$source, 49, $returnValue],
                [$source, 50, $returnValue],
                [$source, 51, $returnValue],
                [$source, 52, $returnValue]
            )
            ->will($this->returnValue(''));
        Krexx::$pool->render = $renderMock;

        // Mock the string encoder.
        $encoderMock = $this->createMock(Encoding::class);
        $encoderMock->expects($this->exactly(11))
            ->method('encodeString')
            ->withConsecutive(
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
            )->will($this->returnValue($returnValue));
        Krexx::$pool->encodingService = $encoderMock;

        $simpleReflection = new ReflectionClass(SimpleFixture::class);
        $this->file->readSourcecode(
            $simpleReflection->getFileName(),
            45,
            41,
            51
        );
    }

    /**
     * Test the reading of source code from a cached fixture file, with messed
     * up parameters.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::readSourcecode
     * @covers \Brainworxx\Krexx\Service\Misc\File::getFileContentsArray
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
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
    }

    /**
     * Test the reading of a fixture file into a string.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::readFile
     * @covers \Brainworxx\Krexx\Service\Misc\File::getFileContentsArray
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
     */
    public function testReadFile()
    {
        $reflection = new ReflectionClass(TraversableFixture::class);
        $result = $this->file->readFile($reflection->getFileName(), 41, 45);
        $expectation = 'class TraversableFixture implements \Iterator' . "\n";
        $expectation .= '{' . "\n";
        $expectation .= '    private $position = 0;' . "\n";
        $expectation .= "\n";
        $expectation .= '    private $array = array(' . "\n";

        $this->assertEquals($expectation, $result, 'Read a file range.');
        $this->assertEquals(
            'class TraversableFixture implements \Iterator' . "\n",
            $this->file->readFile($reflection->getFileName(), 41, 41),
            'Read a single line.'
        );
    }

    /**
     * Test the direct reading of a file into a string.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::getFileContents
     * @covers \Brainworxx\Krexx\Service\Misc\File::filterFilePath
     */
    public function testGetFileContents()
    {
        $this->assertEmpty($this->file->getFileContents('whatever'), 'File does not exist');
        $this->assertEquals(
            ['fileserviceAccess' => ['key' => 'fileserviceAccess', 'params' => ['whatever']]],
            Krexx::$pool->messages->getKeys()
        );
    }

    /**
     * Test the wrapper around the file_put_contents in the file handler.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::putFileContents
     */
    public function testPutFileContents()
    {
        // We will not really write a file here.
        \Brainworxx\Krexx\Service\Misc\file_put_contents(
            '',
            '',
            0,
            null,
            true
        );

        $path = 'some file.html';
        $this->file->putFileContents($path, 'some text');

        $this->assertAttributeEquals([$path => true], 'isReadableCache', $this->file);
        \Brainworxx\Krexx\Service\Misc\file_put_contents(
            '',
            '',
            0,
            null,
            false
        );
    }

    /**
     * Test the deleting of a registered file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::deleteFile
     */
    public function testDeleteFileRegistered()
    {
        // Start the mocking.
        \Brainworxx\Krexx\Service\Misc\unlink('', true);
        \Brainworxx\Krexx\Service\Misc\chmod('', 0777, true);
        \Brainworxx\Krexx\Service\Misc\realpath('', true);

        // Execute the test.
        $payload = 'some_file.txt';
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection('isReadableCache', [$payload => true], $fileService);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals([$payload], \Brainworxx\Krexx\Service\Misc\unlink('', false));
        $this->assertEquals([], \Brainworxx\Krexx\Service\Misc\chmod('', 0777, false));
        $this->assertEquals([], Krexx::$pool->messages->getKeys());
    }

    /**
     * Test the deleting of a not existing file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::deleteFile
     */
    public function testDeleteFileNotExisting()
    {
        // Start the mocking.
        \Brainworxx\Krexx\Service\Misc\unlink('', true);
        \Brainworxx\Krexx\Service\Misc\chmod('', 0777, true);

        // Execute the test.
        $payload = 'not_existing_file.txt';
        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals([], \Brainworxx\Krexx\Service\Misc\unlink('', false));
        $this->assertEquals([], \Brainworxx\Krexx\Service\Misc\chmod('', 0777, false));

        $this->assertEquals(
            [],
            Krexx::$pool->messages->getKeys(),
            'We do not give feedback when trying to delete a none existing file.'
        );
    }

    /**
     * Test the deleting of a unregistered file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::deleteFile
     */
    public function testDeleteFileUnRegistered()
    {
        // Start the mocking.
        \Brainworxx\Krexx\Service\Misc\unlink('', true);
        \Brainworxx\Krexx\Service\Misc\chmod('', 0777, true);
        \Brainworxx\Krexx\Service\Misc\realpath('', true);
        \Brainworxx\Krexx\Service\Misc\is_file('', true);

        // Execute the test.
        $payload = 'unregistered_file.txt';
        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals([$payload], \Brainworxx\Krexx\Service\Misc\unlink('', false));
        $this->assertEquals([$payload], \Brainworxx\Krexx\Service\Misc\chmod('', 0777, false));
        $this->assertEquals([], Krexx::$pool->messages->getKeys());
    }

    /**
     * Test the deleting of a problematic file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::deleteFile
     */
    public function testDeleteFileWithProblems()
    {
        \Brainworxx\Krexx\Service\Misc\chmod('', 0777, true);
        \Brainworxx\Krexx\Service\Misc\realpath('', true);
        \Brainworxx\Krexx\Service\Misc\is_file('', true);

        // Execute the test.
        $payload = 'unregistered_file.txt';
        $fileService = new File(Krexx::$pool);
        $fileService->deleteFile($payload);

        // Check the results.
        $this->assertEquals(
            [$payload],
            \Brainworxx\Krexx\Service\Misc\chmod('', 0777, false),
            'Change the access rights of the file we want to delete.'
        );
        $this->assertEquals(
            ['fileserviceDelete' => ['key' => 'fileserviceDelete', 'params' => [$payload]]],
            Krexx::$pool->messages->getKeys(),
            'Feedback, that we were unable to delete the file.'
        );
    }

    /**
     * Test the filepath filter
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::filterFilePath
     */
    public function testFilterFilePath()
    {
        // Set the stage.
        \Brainworxx\Krexx\Service\Misc\realpath('', true);
        $docRoot = 'somewhere on the server';
        $filename = 'some file';
        $payload = $docRoot . DIRECTORY_SEPARATOR . $filename;
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection('docRoot', $docRoot, $fileService);

        // Run the test
        $this->assertEquals('...' . DIRECTORY_SEPARATOR . $filename, $fileService->filterFilePath($payload));

        // And now without a identifiable docroot.
        $this->setValueByReflection('docRoot', false, $fileService);
        $this->assertEquals($payload, $fileService->filterFilePath($payload));
    }

    /**
     * Test, if an already registered file is readable.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
     */
    public function testFileIsReadableRegistered()
    {
        // Set the stage.
        \Brainworxx\Krexx\Service\Misc\realpath('', true);
        $filename = 'some file';
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection('isReadableCache', [$filename => true], $fileService);

        // Run the test.
        $this->assertTrue($fileService->fileIsReadable($filename));
    }

    /**
     * Test, if an already registered file is readable.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
     */
    public function testFileIsReadableUnregistered()
    {
        \Brainworxx\Krexx\Service\Misc\is_file('', true);
        \Brainworxx\Krexx\Service\Misc\is_readable('', true);
        $filename = 'some file';
        $fileService = new File(Krexx::$pool);

        $this->assertTrue($fileService->fileIsReadable($filename));
        $this->assertEquals([$filename], \Brainworxx\Krexx\Service\Misc\is_readable('', false));
    }

    /**
     * Test if a not existing file is readable.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::fileIsReadable
     */
    public function testFileIsReadableNotExisting()
    {
        $fileService = new File(Krexx::$pool);
        $this->assertFalse($fileService->fileIsReadable('barf'));
    }

    /**
     * Test the getting of a file stemp from an "existing" file.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\File::filetime
     */
    public function testFileTimeExisting()
    {
        // Set the stage for an "existing" file.
        \Brainworxx\Krexx\Service\Misc\realpath('', true);
        $filename = 'some file';
        $fileService = new File(Krexx::$pool);
        $this->setValueByReflection('isReadableCache', [$filename => true], $fileService);

        \Brainworxx\Krexx\Service\Misc\filemtime('', true);
        $this->assertEquals(42, $fileService->filetime($filename));
    }

    public function testFileTimeNotExisting()
    {
        $fileService = new File(Krexx::$pool);
        \Brainworxx\Krexx\Service\Misc\time(true);
        $this->assertEquals(41, $fileService->filetime('I am not here'));
    }
}
