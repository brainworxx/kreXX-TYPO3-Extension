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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\ClassName;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ClassName::class, 'handle')]
#[CoversMethod(ClassName::class, 'canHandle')]
#[CoversMethod(ClassName::class, 'isActive')]
class ClassNameTest extends AbstractHelper
{
    /**
     * Test if it is active.
     */
    public function testIsActive()
    {
        $this->assertTrue(ClassName::isActive(), 'It is always active');
    }

    /**
     * Test the class name recognition
     */
    public function testCanHandle()
    {
        $className = new ClassName(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $this->assertTrue(
            $className->canHandle(static::class, $model),
            'Recognition of an existing class'
        );
        $this->assertFalse(
            $className->canHandle('qay wsx', $model),
            'Recognition of a simple string.'
        );
    }

    /**
     * Test the handling of the json.
     */
    public function testHandle()
    {
        $className = new ClassName(Krexx::$pool);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [ClassName::class . PluginConfigInterface::START_EVENT, $className],
            [ClassName::class . '::callMe' . CallbackConstInterface::EVENT_MARKER_END, $className]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $string = static::class;
        $model = new Model(Krexx::$pool);
        $model->setHasExtra(true)
            ->setData($string);

        $expectation = new ReflectionClass($string);
        $className->canHandle($string, $model);
        $className->callMe();

        $result = CallbackCounter::$staticParameters[0][ClassName::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals($string, $result['Content']);
        $this->assertEquals($expectation, $result['Reflection']);
    }
}
