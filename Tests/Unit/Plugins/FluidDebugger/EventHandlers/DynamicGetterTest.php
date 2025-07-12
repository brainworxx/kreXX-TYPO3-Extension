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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Unit\Plugins\FluidDebugger\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\DynamicGetter;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;

#[CoversMethod(DynamicGetter::class, 'handle')]
#[CoversMethod(DynamicGetter::class, 'retrieveGetterArray')]
#[CoversMethod(DynamicGetter::class, '__construct')]
#[CoversMethod(DynamicGetter::class, 'removeFromGetter')]
class DynamicGetterTest extends AbstractHelper implements CallbackConstInterface, CodegenConstInterface
{
    /**
     * Test the setting of the pool.
     */
    public function testConstruct()
    {
        $getter = new DynamicGetter(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Test the handle method.
     *
     * Yo dawg, we heard you like putting objects into objects.
     */
    public function testHandle()
    {
        if (!class_exists(ContentBlockData::class)) {
            $this->markTestSkipped('ContentBlockData class does not exist, skipping test.');
        }

        // Load the fluid language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 'fluid.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();

        $pool = Krexx::$pool;
        $payload = [
            'uid' => 0,
            'pid' => 0,
            'some' => 'data',
            'another' => 'value',
            'nested' => [
                'key' => 'value',
                'array' => ['item1', 'item2'],
            ],
        ];

        // We need stack 5 Objects into each other. WTF!
        $rawRecord = $this->createMock(RawRecord::class);
        $record = new Record($rawRecord, $payload);
        $cbData = new ContentBlockData($record);
        $reflectionClass = new ReflectionClass($cbData);
        $callback = new CallbackNothing($pool);

        // Mock the already existing getter values.
        $uidMock = $this->createMock(\ReflectionMethod::class);
        $uidMock->expects($this->once())
            ->method('getName')
            ->willReturn('getUid');
        $pidMock = $this->createMock(\ReflectionMethod::class);
        $pidMock->expects($this->once())
            ->method('getName')
            ->willReturn('getPid');
        $anyMock = $this->createMock(\ReflectionMethod::class);
        $anyMock->expects($this->once())
            ->method('getName')
            ->willReturn('getAny');

        $callback->setParameters([
            static::PARAM_REF => $reflectionClass,
            static::PARAM_NORMAL_GETTER => [$uidMock, $pidMock, $anyMock]
        ]);

        // Short circuit the routing, so we can get the results directly.
        $routing = new RoutingNothing($pool);
        $pool->routing = $routing;

        $getter = new DynamicGetter($pool);
        $getter->handle($callback);

        $datas = array_values($payload);
        $names = array_keys($payload);
        $counter = 0;
        foreach ($routing->model as $model) {
            $this->assertEquals(
                $datas[$counter],
                $model->getData(),
                'Data does not match for index ' . $counter
            );
            $this->assertEquals(
                $names[$counter],
                $model->getName(),
                'Name does not match for index ' . $counter
            );
            $this->assertEquals(
                static::CODEGEN_TYPE_PUBLIC,
                $model->getCodeGenType(),
                'Codegen type does not match for index ' . $counter
            );
            $this->assertEquals(
                [$pool->messages->getHelp('metaHelp') => $pool->messages->getHelp('fluidMagicContentBlocks')],
                $model->getJson(),
                'JSON data does not match for index ' . $counter
            );
            ++$counter;
            if ($counter > 4) {
                break; // We only expect 5 items
            }
        }

        // And in a last ditched effort, test if the getter was removed from the callback.
        $parameters = $callback->getParameters();
        $getterMethods = $parameters[static::PARAM_NORMAL_GETTER] ?? [];
        $this->assertCount(1, $getterMethods, 'There is supposed to be the \'getAny\' getter left.');
        $this->assertSame($anyMock, $getterMethods[0], 'The remaining getter is the \'getAny\'.');
    }

    /**
     * Test the handle method with a wrong class.
     */
    public function testHandleWithWrongClass()
    {
        if (!class_exists(ContentBlockData::class)) {
            $this->markTestSkipped('ContentBlockData class does not exist, skipping test.');
        }

        $subject = new \stdClass();
        $reflectionClass = new ReflectionClass($subject);
        $callback = new CallbackNothing(Krexx::$pool);
        $callback->setParameters([
            static::PARAM_REF => $reflectionClass,
            static::PARAM_NORMAL_GETTER => []
        ]);
        $pool = Krexx::$pool;
        $routing = new RoutingNothing($pool);
        $pool->routing = $routing;
        $getter = new DynamicGetter($pool);
        $result = $getter->handle($callback);
        $this->assertEquals('', $result, 'The result should be an empty string for a wrong class.');
    }
}
