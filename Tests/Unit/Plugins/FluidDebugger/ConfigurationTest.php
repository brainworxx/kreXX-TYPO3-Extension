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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterWithoutGet;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Connectors as FluidConnectors;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen as FluidCodegen;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\Fluid as CallerFinderFluid;

class ConfigurationTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $expectedRewrites = [
        Connectors::class => FluidConnectors::class,
        Codegen::class => FluidCodegen::class,
        CallerFinder::class => CallerFinderFluid::class,
    ];

    /**
     * @var array
     */
    protected $expectedEvents = [
        'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end' => [
            GetterWithoutGet::class => GetterWithoutGet::class
        ],
        'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end' => [
            VhsMethods::class => VhsMethods::class
        ]
    ];

    /**
     * @var array
     */
    protected $expectedHelpFiles = [
        'what/ever/Resources/Private/Language/fluid.kreXX.ini'
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = new Configuration();
    }

    /**
     * Simple string contains assertion.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration::getName
     */
    public function testGetName()
    {
        $this->assertNotEmpty($this->configuration->getName());
    }

    /**
     * Test the getting of the version, which is the same as the extension.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration::getVersion
     */
    public function testGetVersion()
    {
        $metaData = $this->createMock(MetaData::class);
        $metaData->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue(AbstractHelper::TYPO3_VERSION));
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaData));

        $this->assertEquals(AbstractHelper::TYPO3_VERSION, $this->configuration->getVersion());
    }

    /**
     * Test the registration of all necessary adjustments to the kreXX lib.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration::exec
     */
    public function testExec()
    {
        $this->simulatePackage(Bootstrap::EXT_KEY, 'A path/');
        $this->configuration->exec();

        $this->assertEquals($this->expectedRewrites, SettingsGetter::getRewriteList());
        $this->assertEquals($this->expectedEvents, SettingsGetter::getEventList());
        $expectedHelpFiles = [
            'A path/Resources/Private/Language/fluid.kreXX.ini'
        ];
        $this->assertEquals($expectedHelpFiles, SettingsGetter::getAdditionalHelpFiles());
    }
}