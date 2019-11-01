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

namespace Brainworxx\Includekrexx\Unit\Plugins\Typo3\EventHandlers;

use Brainworxx\Includekrexx\Plugins\Typo3\Configuration;
use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use StdClass;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;

class QueryDebuggerTest extends AbstractTest implements ConstInterface
{
    protected $expectation = 'SELECT * FROM whatever WHERE uid=\'nothing\'';

    /**
     * Subscribing our class to test to the right event.
     *
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        // Subscribing.
        Registration::registerEvent(
            Objects::class . Configuration::START_EVENT,
            QueryDebugger::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);
    }

    public function tearDown()
    {
        parent::tearDown();

        // Reset the possible mocks in the general utility.
        $this->setValueByReflection('finalClassNameCache', [], GeneralUtility::class);
        $this->setValueByReflection('singletonInstances', [], GeneralUtility::class);
    }

    /**
     * Test the assigning of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::__construct
     */
    public function testConstruct()
    {
        $debugMethod = new QueryDebugger(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $debugMethod));
    }

    /**
     * Test the debugging with a query interface.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::handle
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::retrieveSql
     */
    public function testHandleNormalObject()
    {
        $fixture = [
            static::PARAM_DATA => new StdClass(),
            static::PARAM_NAME => 'whatever'
        ];

        $strLenMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Plugins\\Typo3\\EventHandlers\\', 'strlen');
        $strLenMock->expects($this->never());

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();
    }

    /**
     * Test the debugging with a query builder.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::handle
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::retrieveSql
     */
    public function testHandleQueryBuilder()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $fixture = [
            static::PARAM_DATA => $this->createQueryBuilderMock(),
            static::PARAM_NAME => 'queryBuilder'
        ];

        $strLenMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Plugins\\Typo3\\EventHandlers\\', 'strlen');
        $strLenMock->expects($this->once())
            ->with($this->expectation);

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderSingleChild'][0];
        $this->assertEquals($this->expectation, $model->getData());
    }

    /**
     * Test the debugging with a query interface.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::handle
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryDebugger::retrieveSql
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
            ->will($this->returnValue($this->createQueryBuilderMock()));

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Typo3DbQueryParser::class)
            ->will($this->returnValue($queryParserMock));
        $this->injectIntoGeneralUtility(ObjectManager::class, $objectManagerMock);

        $strLenMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Plugins\\Typo3\\EventHandlers\\', 'strlen');
        $strLenMock->expects($this->once())
            ->with($this->expectation)
            ->will($this->returnValue(500));

        $objectAnalyser = new Objects(Krexx::$pool);
        $objectAnalyser->setParameters($fixture)->callMe();

        /** @var \Brainworxx\Krexx\Analyse\Model $model */
        $model = $renderNothing->model['renderSingleChild'][0];
        $this->assertEquals($this->expectation, $model->getData());
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
            ->will($this->returnValue('SELECT * FROM whatever WHERE uid=:stuff'));
        $queryBuilderMock->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue(['stuff' => 'nothing']));

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
        $finalClassNameCache = $this->retrieveValueByReflection('finalClassNameCache', GeneralUtility::class);
        $finalClassNameCache[$className] = $className;
        $this->setValueByReflection('finalClassNameCache', $finalClassNameCache, GeneralUtility::class);

        $singletonInstances = $this->retrieveValueByReflection('singletonInstances', GeneralUtility::class);
        $singletonInstances[$className] = $mock;
        $this->setValueByReflection('singletonInstances', $singletonInstances, GeneralUtility::class);
    }
}