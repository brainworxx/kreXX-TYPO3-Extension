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

namespace Brainworxx\Krexx\Tests\Service\Factory;


use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Misc\Registry;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Render;
use Brainworxx\Krexx\View\Skins\RenderHans;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;
use stdClass;

class PoolTest extends AbstractTest
{

    public function tearDown()
    {
        parent::tearDown();

        // Reset the overwrites for the is_writable mock.
        \Brainworxx\Krexx\Service\Factory\is_writable();
    }

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
        $this->assertInstanceOf(Render::class, Krexx::$pool->render);
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
        $this->assertInstanceOf(RenderSmokyGrey::class, Krexx::$pool->render);

        Krexx::$pool = null;
        ConfigSupplier::$overwriteValues[Fallback::SETTING_SKIN] = Fallback::SKIN_HANS;
        Registration::addRewrite(Ini::class, ConfigSupplier::class);
        Pool::createPool();
        $this->assertInstanceOf(RenderHans::class, Krexx::$pool->render);
    }

    /**
     * Test the checking of the environment, where kreXX is running.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Pool::checkEnvironment
     */
    public function testCheckEnvironment()
    {
        // Chunks folder is writable
        // Log folder is writable
        \Brainworxx\Krexx\Service\Factory\is_writable(Krexx::$pool->config->getChunkDir(), true);
        \Brainworxx\Krexx\Service\Factory\is_writable(Krexx::$pool->config->getLogDir(), true);
        Krexx::$pool = null;
        Pool::createPool();
        $this->assertAttributeEquals(true, 'useChunks', Krexx::$pool->chunks);
        $this->assertAttributeEquals(true, 'useLogging', Krexx::$pool->chunks);
        $this->assertAttributeEmpty('keys', Krexx::$pool->messages);

        // Chunks folder is not writable
        // Log folder is not writable
        \Brainworxx\Krexx\Service\Factory\is_writable(Krexx::$pool->config->getChunkDir(), false);
        \Brainworxx\Krexx\Service\Factory\is_writable(Krexx::$pool->config->getLogDir(), false);
        Krexx::$pool = null;
        Pool::createPool();
        $this->assertAttributeEquals(false, 'useChunks', Krexx::$pool->chunks);
        $this->assertAttributeEquals(false, 'useLogging', Krexx::$pool->chunks);
        $this->assertAttributeCount(2, 'keys', Krexx::$pool->messages);
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
        Krexx::$pool->reset();

        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->recursionHandler);
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->codegenHandler);
        $this->assertNotInstanceOf(stdClass::class, Krexx::$pool->scope);
    }
}
