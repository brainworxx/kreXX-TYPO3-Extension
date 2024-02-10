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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\AbstractFactory;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use stdClass;

class AbstractFactoryTest extends AbstractHelper
{
    protected function tearDown(): void
    {
        // Remove a sdtClass, which may replace the pool.
        Krexx::$pool = null;
        $this->setValueByReflection('rewriteList', [], Registration::class);
        Pool::createPool();

        parent::tearDown();
    }

    /**
     * Testing the creation of a class with and without rewrites.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\AbstractFactory::createClass
     */
    public function testCreateClass()
    {

        $this->assertInstanceOf(
            stdClass::class,
            Krexx::$pool->createClass(stdClass::class)
        );

        Krexx::$pool->rewrite[stdClass::class] = Model::class;
        $this->assertInstanceOf(
            Model::class,
            Krexx::$pool->createClass(stdClass::class)
        );
    }

    /**
     * Test the retrieval of the super variable globals.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\AbstractFactory::getGlobals
     */
    public function testGetGlobals()
    {
        $this->assertSame($GLOBALS, Krexx::$pool->getGlobals());
        $this->assertSame($GLOBALS['_ENV'], Krexx::$pool->getGlobals('_ENV'));
    }

    /**
     * Test the retrieval of the superglobal SERVER.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\AbstractFactory::getServer
     */
    public function testGetServer()
    {
        $this->assertSame($GLOBALS['_SERVER'], Krexx::$pool->getServer());
    }

    /**
     * Test the creation of a new pool.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\AbstractFactory::createPool
     */
    public function testCreatePool()
    {
        // Pool already exists, test do nothing.
        $oldPool = Krexx::$pool;
        AbstractFactory::createPool();
        $this->assertSame($oldPool, Krexx::$pool);

        // Pool is gone, create a new one.
        Krexx::$pool = null;
        Registration::addRewrite('SomeClass', 'RewriteClass');
        AbstractFactory::createPool();
        $this->assertNotSame($oldPool, Krexx::$pool);
         // Test the retrieval of the rewrites.
        $this->assertEquals(
            ['SomeClass' => 'RewriteClass'],
            Krexx::$pool->rewrite
        );

        // Pool is gone, create an rewrite for it
        Registration::addRewrite(Pool::class, stdClass::class);
        Krexx::$pool = null;
        AbstractFactory::createPool();
        $this->assertInstanceOf(stdClass::class, Krexx::$pool);
    }

    /**
     * Test the retrieval of the error callback, as well as running it.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\AbstractFactory::retrieveErrorCallback
     */
    public function testRetrieveErrorCallback()
    {
        $callback = Krexx::$pool->retrieveErrorCallback();
        $this->assertTrue($callback(1234, 'Barf!'), 'Must do nothing, and return TRUE');
    }
}
