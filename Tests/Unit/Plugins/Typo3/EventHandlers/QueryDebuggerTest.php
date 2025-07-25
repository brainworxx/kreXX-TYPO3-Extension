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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\EventHandlers;

use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser\Typo3DbQueryParser;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use StdClass;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(QueryDebugger::class, 'handle')]
#[CoversMethod(QueryDebugger::class, 'retrieveSql')]
#[CoversMethod(QueryDebugger::class, '__construct')]
#[CoversMethod(QueryDebugger::class, 'replaceParameter')]
class QueryDebuggerTest extends AbstractHelper implements CallbackConstInterface
{
    protected const FINAL_CLASS_NAME_CACHE = 'finalClassNameCache';
    protected const SINGLETON_INSTANCES = 'singletonInstances';

    protected $expectation = 'SELECT * FROM whatever WHERE uid=&#039;nothing&#039;';

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockStrLen()
    {
        return $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Plugins\\Typo3\\EventHandlers\\', 'strlen');
    }

    /**
     * Subscribing our class to test to the right event.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Subscribing.
        Registration::registerEvent(
            Objects::class . Configuration::START_EVENT,
            QueryDebugger::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        // Reset the possible mocks in the general utility.
        $this->setValueByReflection(static::FINAL_CLASS_NAME_CACHE, [], GeneralUtility::class);
        $this->setValueByReflection(static::SINGLETON_INSTANCES, [], GeneralUtility::class);
    }

    /**
     * Test the assigning of the pool.
     */
    public function testConstruct()
    {
        $debugMethod = new QueryDebugger(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $debugMethod));
    }

    /**
     * Test the debugging with a query interface.
     */
    public function testHandleNormalObject()
    {
        $fixture = [
            static::PARAM_DATA => new StdClass(),
            static::PARAM_NAME => 'whatever'
        ];

        $this->mockStrLen()->expects($this->never());

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();
    }

    /**
     * Test the debugging with a query builder.
     */
    public function testHandleQueryBuilder()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $fixture = [
            static::PARAM_DATA => $this->createQueryBuilderMock(),
            static::PARAM_NAME => 'queryBuilder'
        ];

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($this->expectation, $model->getData());
    }

    /**
     * Test the debugging with a query builder, with ten or more parameters.
     */
    public function testHandleQueryBuilderWithMorThanTenParameters()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('getSQL')
            ->willReturn(
                'SELECT * FROM whatever WHERE `uid` IN (:stuff1, :stuff2, :stuff3, :stuff4, :stuff5, :stuff6, :stuff7, :stuff8, :stuff9, :stuff10)'
            );
        $queryBuilderMock->expects($this->once())
            ->method('getParameters')
            ->willReturn(
                [
                    'stuff1' => 'nothing1',
                    'stuff2' => 'nothing2',
                    'stuff3' => 'nothing3',
                    'stuff4' => 'nothing4',
                    'stuff5' => 'nothing5',
                    'stuff6' => 'nothing6',
                    'stuff7' => 'nothing7',
                    'stuff8' => 'nothing8',
                    'stuff9' => 'nothing9',
                    'stuff10' => 'nothing10'
                ]
            );
        $fixture = [
            static::PARAM_DATA => $queryBuilderMock,
            static::PARAM_NAME => 'queryBuilder'
        ];
        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        $expectation = 'SELECT * FROM whatever WHERE `uid` IN (&#039;nothing1&#039;, &#039;nothing2&#039;, &#039;nothing3&#039;, &#039;nothing4&#039;, &#039;nothing5&#039;, &#039;nothing6&#039;, &#039;nothing7&#039;, &#039;nothing8&#039;, &#039;nothing9&#039;, &#039;nothing10&#039;)';

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($expectation, $model->getData());
    }

    /**
     * Test the debugging with a query interface.
     */
    public function testHandleQueryInterface()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $queryMock = $this->createMock(Query::class);
        $fixture = [
            static::PARAM_DATA => $queryMock,
            static::PARAM_NAME => 'queryBuilder'
        ];

        $queryParserMock = $this->createMock(Typo3DbQueryParser::class);
        $queryParserMock->expects($this->once())
            ->method('convertQueryToDoctrineQueryBuilder')
            ->with($queryMock)
            ->willReturn($this->createQueryBuilderMock());
        $this->injectIntoGeneralUtility(Typo3DbQueryParser::class, $queryParserMock);

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($this->expectation, $model->getData());
    }

    /**
     * Test the exception handling of the SQL retrieval.
     */
    public function testHandleException()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;
        $queryMock = $this->createMock(QueryBuilder::class);
        $expectation = 'Error while retrieving SQL: This is a test exception';
        $queryMock->expects($this->once())
            ->method('getSQL')
            ->willThrowException(new \RuntimeException($expectation));
        $fixture = [
            static::PARAM_DATA => $queryMock,
            static::PARAM_NAME => 'queryBuilder'
        ];

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertEquals($expectation, $model->getData());
    }

    /**
     * What the method name says.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createQueryBuilderMock()
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('getSQL')
            ->willReturn('SELECT * FROM whatever WHERE uid=:stuff');
        $queryBuilderMock->expects($this->once())
            ->method('getParameters')
            ->willReturn(['stuff' => 'nothing']);

        return $queryBuilderMock;
    }

    /**
     * Inject a mock into the general utility.
     *
     * @param $className
     * @param $mock
     */
    protected function injectIntoGeneralUtility($className, $mock)
    {
        $finalClassNameCache = $this->retrieveValueByReflection(static::FINAL_CLASS_NAME_CACHE, GeneralUtility::class);
        $finalClassNameCache[$className] = $className;
        $this->setValueByReflection(static::FINAL_CLASS_NAME_CACHE, $finalClassNameCache, GeneralUtility::class);

        $singletonInstances = $this->retrieveValueByReflection(static::SINGLETON_INSTANCES, GeneralUtility::class);
        $singletonInstances[$className] = $mock;
        $this->setValueByReflection(static::SINGLETON_INSTANCES, $singletonInstances, GeneralUtility::class);
    }
}
