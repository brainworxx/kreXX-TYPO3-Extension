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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\EventHandlers\QueryParser;

use Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser\Typo3DbQueryParser;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser as OriginalParser;

class Typo3DbQueryParserTest extends AbstractTest
{
    /**
     * Test our compatibility hack for the DI.
     *
     * We are actually somewhat supposed to test the part where the DI works.
     * The bad thing here is that this part has changed much since 8.7.
     * And testing the parent method across all LTS versions is a very bad idea.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser\Typo3DbQueryParser::convertQueryToDoctrineQueryBuilder
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser\Typo3DbQueryParser::__construct
     */
    public function testConvertQueryToDoctrineQueryBuilderNoDi()
    {
        $fixture = $this->createMock(Query::class);
        $parser = new Typo3DbQueryParser();

        $originalParserMock = $this->createMock(OriginalParser::class);
        $originalParserMock->expects($this->once())
            ->method('convertQueryToDoctrineQueryBuilder')
            ->with($fixture)
            ->will($this->returnValue('some sql'));
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->once())
            ->method('get')
            ->with(OriginalParser::class)
            ->will($this->returnValue($originalParserMock));
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerMock);

        $parser->convertQueryToDoctrineQueryBuilder($fixture);
    }
}
