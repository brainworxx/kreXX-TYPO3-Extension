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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\DebugMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use TYPO3\CMS\Core\Package\MetaData;
use Aimeos\MW\DB\Statement\Base as StatementBase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Configuration::class, 'exec')]
#[CoversMethod(Configuration::class, 'getVersion')]
#[CoversMethod(Configuration::class, 'getName')]
class ConfigurationTest extends AbstractHelper
{
    use AimeosTestTrait;

    /**
     * @var \Brainworxx\Includekrexx\Plugins\AimeosDebugger\Configuration
     */
    protected $configuraton;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configuraton = new Configuration();
    }

    /**
     * Test the getting of the name of the Aimeos Debugger.
     */
    public function testGetName()
    {
        $this->skipIfAimeosIsNotInstalled();

        $this->assertNotEmpty($this->configuraton->getName());
    }

    /**
     * Test the getting of the version, which is the same as the extension.
     */
    public function testGetVersion()
    {
        $this->skipIfAimeosIsNotInstalled();

        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->once())
            ->method('getVersion')
            ->willReturn(AbstractHelper::TYPO3_VERSION);
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->willReturn($metaData);

        $this->assertEquals(AbstractHelper::TYPO3_VERSION, $this->configuraton->getVersion());
    }

    /**
     * Test the registering of the events and all the other stuff for this plugin.
     */
    public function testExec()
    {
        $this->skipIfAimeosIsNotInstalled();

        $this->simulatePackage(Bootstrap::EXT_KEY, 'A path/');
        $this->configuraton->exec();

        $expectedEvents = [
            'Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe::start' => [
                Properties::class => Properties::class
            ],
            'Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::retrievePropertyValue::resolving' => [
                Getter::class => Getter::class
            ],
            'Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe::start' => [
                Decorators::class => Decorators::class,
                ViewFactory::class => ViewFactory::class
            ],
            'Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::callMe::end' => [
                ThroughMethods::class => ThroughMethods::class
            ],
            'Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods::callMe::start' => [
                DebugMethods::class => DebugMethods::class
            ],
        ];
        $this->assertEquals($expectedEvents, SettingsGetter::getEventList());

        $expectedBlacklist = [
            StatementBase::class => [
                '__toString'
            ]
        ];
        $this->assertEquals($expectedBlacklist, SettingsGetter::getBlacklistDebugMethods());

        $expectedHelpFiles = [
            'A path/Resources/Private/Language/aimeos.kreXX.ini'
        ];
        $this->assertEquals($expectedHelpFiles, SettingsGetter::getAdditionalHelpFiles());
    }
}
