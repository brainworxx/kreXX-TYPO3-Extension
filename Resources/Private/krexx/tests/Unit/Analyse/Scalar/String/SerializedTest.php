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
use Brainworxx\Krexx\Analyse\Scalar\String\AbstractScalarAnalysis;
use Brainworxx\Krexx\Analyse\Scalar\String\Serialized;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Serialized::class, 'isActive')]
#[CoversMethod(Serialized::class, 'canHandle')]
#[CoversMethod(AbstractScalarAnalysis::class, 'callMe')]
#[CoversMethod(Serialized::class, 'handle')]

class SerializedTest extends AbstractHelper
{
    /**
     * Test if the pretty print for the serialize is active.
     */
    public function testIsActive()
    {
        // Best. Test. Ever.
        // There are no special conditions for this one.
        $this->assertTrue(Serialized::isActive());
    }

    /**
     * Test with loops you have to jump through to test if we can pretty print
     * this one.
     */
    public function testCanHandle()
    {
        $serialized = new Serialized(Krexx::$pool);
        $jsonKey = Krexx::$pool->messages->getHelp('metaMimeTypeString');

        // Test with a normal string.
        $fixture = 'just a string';
        $model = new Model(Krexx::$pool);
        $mimeType = 'whatever';
        $model->addToJson($jsonKey, $mimeType)
            ->setData($fixture);
        $this->assertFalse($serialized->canHandle($fixture, $model));

        // Test with the "right" mimeType.
        $mimeType = 'Ima binary whatever';
        $model->addToJson($jsonKey, $mimeType);
        $this->assertFalse($serialized->canHandle($fixture, $model));

        // Test with some real data.
        $fixture = serialize(['one', 'two', 'three']);
        $model->setData($fixture);
        $this->assertTrue($serialized->canHandle($fixture, $model));
        $this->assertEquals($fixture, $this->retrieveValueByReflection('handledValue', $serialized));
    }

    /**
     * Test the calling of the pretty print class.
     */
    public function testCallMeNormal()
    {
        $serialized = new Serialized(Krexx::$pool);

        // Set up the event tests.
        $this->mockEventService(
            [Serialized::class . PluginConfigInterface::START_EVENT, $serialized],
            [Serialized::class . '::callMe' . CallbackConstInterface::EVENT_MARKER_END, $serialized]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        // Prepare the test.
        $mimeType = 'Ima binary whatever';
        $array = ['one', 'two', 'three'];
        $fixture = serialize($array);
        $jsonKey = Krexx::$pool->messages->getHelp('metaMimeTypeString');
        $model = new Model(Krexx::$pool);
        $model->addToJson($jsonKey, $mimeType)
            ->setData($fixture);

        // Run the test.
        $this->assertTrue($serialized->canHandle($fixture, $model));
        $serialized->callMe();

        // Check the result.
        $result = CallbackCounter::$staticParameters[0][Serialized::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);
        foreach ($array as $value) {
            $this->assertStringContainsString($value, $result['Pretty print']);
        }
    }

    /**
     * Test the calling of the pretty print class. We expect it to fail, because
     * we do not provide a valid serialized string.
     */
    public function testCallMeFail()
    {
        $serialized = new Serialized(Krexx::$pool);

        // Set up the event tests.
        $this->mockEventService(
            [Serialized::class . PluginConfigInterface::START_EVENT, $serialized]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        // Prepare the test.
        $mimeType = 'Ima binary whatever';
        $array = ['öne', 'twö', 'three'];
        // This should destroy the string pretty well.
        $fixture = htmlspecialchars(serialize($array));
        $jsonKey = Krexx::$pool->messages->getHelp('metaMimeTypeString');
        $model = new Model(Krexx::$pool);
        $model->addToJson($jsonKey, $mimeType)
            ->setData($fixture);

        // Run the test.
        $this->assertTrue($serialized->canHandle($fixture, $model));
        $serialized->callMe();

        // Check the result.
        $this->assertEmpty(CallbackCounter::$staticParameters);
        $this->assertEquals(0, CallbackCounter::$counter);
    }
}
