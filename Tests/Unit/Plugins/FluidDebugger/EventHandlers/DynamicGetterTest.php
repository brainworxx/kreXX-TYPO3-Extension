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

use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\ContentBlocksRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\DomainRecordRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\GetterRetrieverInterface;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\RawRecordRetriever;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever\SettingsRetriever;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ContainerFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\DynamicGetter;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionMethod;
use stdClass;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;
use TYPO3\CMS\Core\Settings\Settings;

#[CoversMethod(DynamicGetter::class, 'handle')]
#[CoversMethod(DynamicGetter::class, '__construct')]
#[CoversMethod(DynamicGetter::class, 'removeFromGetter')]
#[CoversMethod(ContentBlocksRetriever::class, 'canHandle')]
#[CoversMethod(ContentBlocksRetriever::class, 'handle')]
#[CoversMethod(DomainRecordRetriever::class, 'canHandle')]
#[CoversMethod(DomainRecordRetriever::class, 'handle')]
#[CoversMethod(RawRecordRetriever::class, 'canHandle')]
#[CoversMethod(RawRecordRetriever::class, 'handle')]
#[CoversMethod(SettingsRetriever::class, 'canHandle')]
#[CoversMethod(SettingsRetriever::class, 'handle')]
class DynamicGetterTest extends AbstractHelper implements CallbackConstInterface, CodegenConstInterface
{
    /**
     * Test the setting of the pool.
     */
    public function testConstruct()
    {
        $getter = new DynamicGetter(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));

        foreach ($this->retrieveValueByReflection('retriever', $getter) as $retriever) {
            $this->assertTrue(
                $retriever instanceof GetterRetrieverInterface,
                'The retriever ' .  get_class($retriever) .  '  should implement the GetterRetrieverInterface.'
            );
        }
    }

    public function testHandle()
    {
        if (!class_exists(ContentBlockData::class)) {
            $this->markTestSkipped('ContentBlockData class is not available in this context.');
        }

        $payload = [
            'class' => ContentBlockData::class,
            'method' => 'get',
            'args' => [1],
        ];

        $errorClosure = new RecordPropertyClosure(
            function () {
                throw new \RuntimeException('This is a test error.');
            }
        );
        $troublePayload = [
            'uid' => 1,
            'pid' => 2,
            'error' => $errorClosure,
            'someData' => 'wat'
        ];
        $troubleRaw = new RawRecord(1, 2, $troublePayload, new ComputedProperties(), 'unit_test.1');
        $troubleRecord = new Record($troubleRaw, $payload);

        $computedProperties = new ComputedProperties();
        $rawRecord = new RawRecord(1, 2, $payload, $computedProperties, 'unit_test');
        $record = new Record($rawRecord, $payload);
        $contentBlockData = new ContentBlockData($record, 'unit_test');
        $settings = new Settings($payload);
        $testSubjects = [
            // The computed properties are not handled at all.
            new ReflectionClass($computedProperties),
            // The raw record is handled by the RawRecordRetriever.
            new ReflectionClass($rawRecord),
            // The record is handled by the DomainRecordRetriever.
            new ReflectionClass($record),
            // The content block data is handled by the ContentBlocksRetriever.
            new ReflectionClass($contentBlockData),
            // The settings are handled by the SettingsRetriever.
            new ReflectionClass($settings),
            // The trouble record does exactly this, causes trouble.
            new ReflectionClass($troubleRecord),
        ];

        $getter = new DynamicGetter(Krexx::$pool);
        /**
         * @var int $key
         * @var ReflectionClass $subject
         */
        foreach ($testSubjects as $key => $subject) {
            $callBack = new ThroughGetter(Krexx::$pool);
            Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

            $reflectionMethod = new ReflectionMethod(new ContainerFixture(), 'getSomething');
            $getters = [0 => $reflectionMethod];
            $callBack->setParameters([
                CallbackConstInterface::PARAM_REF => $subject,
                CallbackConstInterface::PARAM_NORMAL_GETTER => $getters
            ]);
            $getter->handle($callBack);
            $this->assertEquals(
                $getters,
                $callBack->getParameters()[CallbackConstInterface::PARAM_NORMAL_GETTER],
                $key . ' should have a getter.'
            );
            $result = Krexx::$pool->routing->model;

            $subjectClass = get_class($subject->getData());
            switch ($subjectClass) {
                case ContentBlockData::class:
                    $this->testResultContentBlockData($result);
                    break;
                case ComputedProperties::class:
                    $this->testResultComputedProperties($result);
                    break;
                case Settings::class:
                case Record::class:
                case RawRecord::class:
                    $this->testResultNormal($result);
                    break;
                default:
                    $this->fail('Unknown subject class: ' . $subjectClass);
            }
        }
    }

    protected function testResultContentBlockData(array $result): void
    {
        // The special additional payload from ContentBlockData
        $this->assertEquals('uid', $result[0]->getName());
        $this->assertEquals(1, $result[0]->getData());
        $this->assertEquals('pid', $result[1]->getName());
        $this->assertEquals(2, $result[1]->getData());

        // Our standard payload data
        $this->assertEquals('class', $result[2]->getName());
        $this->assertEquals(ContentBlockData::class, $result[2]->getData());
        $this->assertEquals('method', $result[3]->getName());
        $this->assertEquals('get', $result[3]->getData());
        $this->assertEquals('args', $result[4]->getName());
        $this->assertEquals([1], $result[4]->getData());

        // Additional stuff from the ContentBlockData.
        $this->assertEquals('_name', $result[5]->getName());
        $this->assertEquals('unit_test', $result[5]->getData());
        $this->assertEquals('_grids', $result[6]->getName());
        $this->assertEquals([], $result[6]->getData());

        $this->assertFalse(isset($result[7]), 'There should be no seventh element in the result.');
    }

    protected function testResultComputedProperties(array $result): void
    {
        $this->assertEmpty($result, 'The ComputedProperties ComputedProperties can not be handled.');
    }

    protected function testResultNormal(array $result): void
    {
        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $this->assertEquals('class', $result[0]->getName());
        $this->assertEquals(ContentBlockData::class, $result[0]->getData());
        $this->assertEquals('method', $result[1]->getName());
        $this->assertEquals('get', $result[1]->getData());
        $this->assertEquals('args', $result[2]->getName());
        $this->assertEquals([1], $result[2]->getData());

        if (empty($result[3])) {
            return;
        }

        // Handling the trouble record.
        $this->assertEquals('uid', $result[3]->getName());
        $this->assertEquals(1, $result[3]->getData());
        $this->assertEquals('pid', $result[4]->getName());
        $this->assertEquals(2, $result[4]->getData());
        $this->assertfalse(isset($result[5]), 'There should be no "error" property in the result. As well as no "someData" property.');
    }
}
