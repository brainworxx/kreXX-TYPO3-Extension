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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterWithoutGet;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use TYPO3\CMS\Core\Package\MetaData;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Connectors as FluidConnectors;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen as FluidCodegen;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\Fluid as CallerFinderFluid;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\FluidOld as OldCallerFinderFluid;

class ConfigurationTest extends AbstractTest
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
    public function setUp()
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
            ->will($this->returnValue('1.2.3'));
        $packageMock = $this->simulatePackage(Bootstrap::EXT_KEY, 'whatever');
        $packageMock->expects($this->once())
            ->method('getPackageMetaData')
            ->will($this->returnValue($metaData));

        $this->assertEquals('1.2.3', $this->configuration->getVersion());
    }

    /**
     * Test the registration of all neccessary adjustments to the kreXX lib.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration::exec
     */
    public function testExecHighVersion()
    {
        $versionCompMock = $this->getFunctionMock(
            '\\Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\',
            'version_compare'
        );
        $versionCompMock->expects($this->exactly(1))
            ->will($this->returnValue(true));

        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');
        $this->configuration->exec();

        $this->assertEquals($this->expectedRewrites, SettingsGetter::getRewriteList());
        $this->assertEquals($this->expectedEvents, SettingsGetter::getEventList());
        $this->assertEquals($this->expectedHelpFiles, SettingsGetter::getAdditionalHelpFiles());
    }
    /**
     * Same as the testExecHighVersion, but with a lower TYPO3 version.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration::exec
     */
    public function testExecLowVersion()
    {
        $versionCompMock = $this->getFunctionMock(
            '\\Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\',
            'version_compare'
        );
        $versionCompMock->expects($this->exactly(1))
            ->will($this->returnValue(false));

        $this->simulatePackage(Bootstrap::EXT_KEY, 'what/ever/');
        $this->configuration->exec();

        $this->expectedRewrites[CallerFinder::class] = OldCallerFinderFluid::class;
        $this->assertEquals($this->expectedRewrites, SettingsGetter::getRewriteList());
    }
}