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

namespace Brainworxx\Includekrexx\Plugins\Typo3\EventHandlers\QueryParser;

use Brainworxx\Krexx\Krexx;
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser as OriginalParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Wrapper around the Typo3DbQueryParser.
 *
 * Since the object manager got himself deprecated, the DI has become somewhat
 * unstable (imho) across the LTS versions.
 */
class Typo3DbQueryParser extends OriginalParser
{
    /**
     * Short-circuiting the DI of 11.3 and beyond.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper|null $dataMapper
     */
    public function __construct(DataMapper $dataMapper = null)
    {
        if (!empty($dataMapper) && method_exists(OriginalParser::class, '__construct')) {
            parent::__construct($dataMapper);
        }
    }

    /**
     * Short-circuiting the convertQueryToDoctrineQueryBuilder to make sure that
     * it still works outside extbase.
     *
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws \Exception
     *
     * {@inheritDoc}
     */
    public function convertQueryToDoctrineQueryBuilder(QueryInterface $query)
    {
        if (empty($this->dataMapper)) {
            // Well, the service.yaml configuration got ignored.
            if (method_exists(ObjectManager::class, 'get')) {
                // Must be lower than TYPO3 10.
                // This means that the general utility is not able to inject
                // anything. We must use the ObjectManager to create the parser.
                return GeneralUtility::makeInstance(ObjectManager::class)
                    ->get(OriginalParser::class)
                    ->convertQueryToDoctrineQueryBuilder($query);
            }

            // Must be very early during the bootstrap in TYPO3 12 and beyond.
            // Dependency injection is not available yet.
            throw new Exception(Krexx::$pool->messages->getHelp('TYPO3DiNotReady'));
        }

        return parent::convertQueryToDoctrineQueryBuilder($query);
    }
}
