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

namespace Brainworxx\Krexx\Tests\Unit\Service\Factory;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Skins\RenderHans;
use stdClass;

class PoolTest extends AbstractHelper
{
    const MISC_NAMESPACE = '\\Brainworxx\\Krexx\\Service\\Misc\\';

    /**
     * Testing the creation of all neccessary classes.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::__construct
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::checkEnvironment
     */
    public function testConstruct()
    {
        Krexx::$pool = null;
        Pool::createPool();

        // The standard stuff.
        $this->assertInstanceOf(Recursion::class, Krexx::$pool->recursionHandler);
        $this->assertInstanceOf(Codegen::class, Krexx::$pool->codegenHandler);
        $this->assertInstanceOf(Emergency::class, Krexx::$pool->emergencyHandler);
        $this->assertInstanceOf(RenderHans::class, Krexx::$pool->render);
        $this->assertInstanceOf(Config::class, Krexx::$pool->config);
        $this->assertInstanceOf(Messages::class, Krexx::$pool->messages);
        $this->assertInstanceOf(Chunks::class, Krexx::$pool->chunks);
        $this->assertInstanceOf(Scope::class, Krexx::$pool->scope);
        $this->assertInstanceOf(Registry::class, Krexx::$pool->registry);
        $this->assertInstanceOf(Routing::class, Krexx::$pool->routing);
        $this->assertInstanceOf(File::class, Krexx::$pool->fileService);
        $this->assertInstanceOf(Encoding::class, Krexx::$pool->encodingService);
        $this->assertInstanceOf(Event::class, Krexx::$pool->eventService);

        // Testing the assigning of the right render class.
        // Smoky Grey is the standard render skin.
        $this->assertInstanceOf(AbstractRender::class, Krexx::$pool->render);

        Krexx::$pool = null;
        ConfigSupplier::$overwriteValues[Fallback::SETTING_SKIN] = 'hans';
        Pool::createPool();
        $this->assertInstanceOf(RenderHans::class, Krexx::$pool->render);
    }

    /**
     * Test the checking of the environment, where kreXX is running.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::checkEnvironment
     * @covers \Brainworxx\Krexx\Service\Misc\File::isDirectoryWritable
     */
    public function testCheckEnvironmentIsWritable()
    {
        $filename = 'test';
        // Chunks folder is writable
        // Log folder is writable
        /** @var \PHPUnit\Framework\MockObject\MockObject $filePutContents */
        $filePutContents = $this->getFunctionMock(static::MISC_NAMESPACE, 'file_put_contents');
        $filePutContents->expects($this->exactly(2))
            ->will($this->returnValue(true));
        $unlink = $this->getFunctionMock(static::MISC_NAMESPACE, 'unlink');
        $unlink->expects($this->exactly(2))
            ->will($this->returnValue(true));

        Krexx::$pool = null;
        Pool::createPool();
        $this->assertTrue(Krexx::$pool->chunks->isChunkAllowed(), 'Chunking is NOT allowed, but it should be.');
        $this->assertTrue(Krexx::$pool->chunks->isLoggingAllowed(), 'Logging is NOT allowed, but it should be.');
        $this->assertEmpty(Krexx::$pool->messages->getMessages());
    }

    /**
     * Test the checking of the environment, where kreXX is running.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::checkEnvironment
     * @covers \Brainworxx\Krexx\Service\Misc\File::isDirectoryWritable
     */
    public function testCheckEnvironmentIsNotWritable()
    {
        $filename = 'test';
        // Chunks folder is not writable
        // Log folder is not writable
        $filePutContents = $this->getFunctionMock(static::MISC_NAMESPACE, 'file_put_contents');
        $filePutContents->expects($this->exactly(2))
            ->will(
                $this->returnValueMap([
                    [Krexx::$pool->config->getChunkDir() . $filename, 'x', false],
                    [Krexx::$pool->config->getLogDir() . $filename, 'x', false]
                ])
            );
        $unlink = $this->getFunctionMock(static::MISC_NAMESPACE, 'unlink');
        // Ther was no file "created", hence there is no unlink'ing done.
        $unlink->expects($this->never());

        Krexx::$pool = null;
        Pool::createPool();
        $this->assertEquals(false, Krexx::$pool->chunks->isChunkAllowed());
        $this->assertEquals(false, Krexx::$pool->chunks->isLoggingAllowed());
        $this->assertCount(2, Krexx::$pool->messages->getMessages());
    }

    /**
     * Test the renewal of the "semi-singletons" after an analysis.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::reset
     */
    public function testReset()
    {
        Krexx::$pool->recursionHandler = new stdClass();
        Krexx::$pool->codegenHandler = new stdClass();
        Krexx::$pool->scope = new stdClass();
        Krexx::$pool->routing = new stdClass();
        Krexx::$pool->reset();

        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->recursionHandler);
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->codegenHandler);
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->scope);
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->routing);
    }

    /**
     * Test the renewal of the "semi-singletons" after an analysis, with
     * simulating a new process fork.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::reset
     */
    public function testResetWithNewFork()
    {
        $getmypidMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Factory', 'getmypid');
        $getmypidMock->expects($this->exactly(2))
            ->will($this->returnValue(12345));
        Krexx::$pool->chunks = new stdClass();

        Krexx::$pool->reset();
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->chunks);
    }
}
