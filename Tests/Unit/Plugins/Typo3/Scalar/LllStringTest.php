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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\Scalar;

use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Analyse\Model;
use TYPO3\CMS\Core\Localization\LocalizationFactory;

class LllStringTest extends AbstractTest
{
    /**
     * We test if the LocalizationUtility::translate still exists.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString::isActive
     */
    public function testIsActive()
    {
        $this->assertTrue(LllString::isActive());
    }

    /**
     * Testing a method that only exists for the sake of the interface . . .
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString::handle
     */
    public function testHandle()
    {
        $lllString = new LllString(\Krexx::$pool);
        $this->assertEquals('', $lllString->callMe());
    }

    /**
     * Testing the "glue" to the TYPO3 translation handling.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString::canHandle
     */
    public function testCanHandle()
    {
        $this->simulatePackage('includekrexx', 'includekrexx/');

        // LocalizationFactory mit getParsedData mocken
        $parsedData = [
            'default' => [
                'mlang_tabs_tab' => [
                    ['target' => 'kreXX Debugger']
                ]
            ]
        ];
        $locFacMock = $this->createMock(LocalizationFactory::class);
        $locFacMock->expects($this->once())
            ->method('getParsedData')
            ->will($this->returnValue($parsedData));
        $this->injectIntoGeneralUtility(LocalizationFactory::class, $locFacMock);

        $payload = 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:mlang_tabs_tab';
        $model = new Model(\Krexx::$pool);
        $lllString = new LllString(\Krexx::$pool);
        $lllString->canHandle($payload, $model);

        $this->assertEquals('kreXX Debugger', $model->getJson()['Translation']);
    }
}
