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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\ScalarString;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ScalarNothing;

class ScalarStringTest extends AbstractTest
{
    /**
     * @var ScalarString
     */
    protected $scalarString;

    /**
     * Reset the scalar helper.
     *
     * @throws \ReflectionException
     */
    public function tearDown()
    {
        ScalarNothing::$canHandle = false;
        ScalarNothing::$canHandleList = [];
        ScalarNothing::$count = 0;
        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();

        $this->scalarString = new ScalarString(Krexx::$pool);
        // Inject the scalar helper, to track the processing.
        $this->setValueByReflection('classList', [ScalarNothing::class], $this->scalarString);
    }

    /**
     * Test the retrieval of the plugin scalar string analysis classes.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\ScalarString::__construct
     */
    public function testConstruct()
    {
        Registration::addScalarStringAnalyser(ScalarNothing::class);
        $this->scalarString = new ScalarString(Krexx::$pool);

        $this->assertTrue(
            in_array(ScalarNothing::class, $this->retrieveValueByReflection('classList', $this->scalarString))
        );
    }

    /**
     * Test the scalar deep analysis, without any fitting callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\ScalarString::handle
     */
    public function testHandleNoHandle()
    {
        // Prepare the fixture.
        $string = 'whatever';
        $fixture = new Model(Krexx::$pool);
        $fixture->setData($string);

        $this->assertSame($fixture, $this->scalarString->handle($fixture, $string));
        $fixture->renderMe();

        $this->assertEquals(0,  ScalarNothing::$count, 'Must not get called.');
        $this->assertEquals(
            [$string],
            ScalarNothing::$canHandleList,
            'We expect the handler to get asked.'
        );
    }

    /**
     * Test the handling with a handler that handles the handling with a handle
     * Meh, the puns are killing me.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\ScalarString::handle
     * @covers \Brainworxx\Krexx\Analyse\Scalar\AbstractScalar::generateDomId
     */
    public function testHandleNormal()
    {
        // Prepare the model.
        $string = 'handle with care';
        $model = new Model(Krexx::$pool);
        $model->setData($string);

        ScalarNothing::$canHandle = true;

        $this->assertSame($model, $this->scalarString->handle($model, $string));
        $model->renderMe();

        $this->assertStringStartsWith('k0_scalar_', $model->getDomid());
        $this->assertEquals(
            [$string],
            ScalarNothing::$canHandleList,
            'Must get asked.'
        );
        $this->assertEquals(1, ScalarNothing::$count);
    }
}
