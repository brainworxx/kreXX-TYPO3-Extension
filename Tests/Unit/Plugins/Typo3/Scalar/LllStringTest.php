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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\Typo3\Scalar;

use Brainworxx\Includekrexx\Plugins\Typo3\Scalar\LllString;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Tests\Helpers\LocalizationUtility;
use Brainworxx\Includekrexx\Tests\Helpers\LocalizationUtility12;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Information\Typo3Version;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(LllString::class, 'canHandle')]
#[CoversMethod(LllString::class, 'resolveExtPath')]
#[CoversMethod(LllString::class, '__construct')]
#[CoversMethod(LllString::class, 'handle')]
#[CoversMethod(LllString::class, 'isActive')]
class LllStringTest extends AbstractHelper
{
    protected const TSFE = 'TSFE';
    protected const KREXX_DEBUGGER = 'kreXX Debugger';

    protected $originalLang;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        // We need to replace this one, because we mock the living hell out of it.
        if (isset($GLOBALS[static::TSFE])) {
            $this->originalLang = $GLOBALS[static::TSFE];
        }

        // Load the TYPO3 language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 't3.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        // Restore the language service to it's former "glory".
        if (isset($this->originalLang)) {
           $GLOBALS[static::TSFE] = $this->originalLang;
        }
    }

    /**
     * We test if the LocalizationUtility::translate still exists.
     */
    public function testIsActive()
    {
        $this->assertTrue(LllString::isActive());
    }

    /**
     * Testing a method that only exists for the sake of the interface . . .
     */
    public function testHandle()
    {
        $lllString = new LllString(\Krexx::$pool);
        $this->assertEquals('', $lllString->callMe());
    }

    /**
     * Testing the "glue" to the TYPO3 translation handling.
     */
    public function testCanHandle()
    {
        $payload = 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:mlang_tabs_tab';
        $model = new Model(\Krexx::$pool);
        $lllString = new LllString(\Krexx::$pool);

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 11) {
            $lllString->setLocalisationUtility(new LocalizationUtility12());
            LocalizationUtility12::$values[$payload] = static::KREXX_DEBUGGER;
        } else {
            $lllString->setLocalisationUtility(new LocalizationUtility());
            LocalizationUtility::$values[$payload] = static::KREXX_DEBUGGER;
        }



        $this->simulatePackage('includekrexx', 'some path');

        $lllString->canHandle($payload, $model);
        $result = $model->getJson();
        $this->assertEquals(static::KREXX_DEBUGGER, $result['Translation']);
        $this->assertEquals('The file or directory does not exist.', $result['Error']);

        // Do it again, with an early return this time.
        $payload = 'Just a string, nothing special';
        $model = new Model(\Krexx::$pool);
        $lllString->canHandle($payload, $model);
        $this->assertEmpty($model->getJson(), 'Expecting an empty array.');
    }
}
