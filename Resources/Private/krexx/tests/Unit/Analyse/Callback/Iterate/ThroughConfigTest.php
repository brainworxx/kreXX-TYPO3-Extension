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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ThroughConfig::class, 'callMe')]
#[CoversMethod(ThroughConfig::class, 'renderAllSections')]
#[CoversMethod(ThroughConfig::class, 'hasSomethingToRender')]
class ThroughConfigTest extends AbstractHelper
{
    /**
     * Test the configuration iteration.
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();

        // Create settings mock fixture.
        $methodName = 'getSection';
        $settingOne = $this->createMock(Model::class);
        $settingOne->expects($this->once())
            ->method($methodName)
            ->willReturn('section one');
        $settingTwo = $this->createMock(Model::class);
        $settingTwo->expects($this->once())
            ->method($methodName)
            ->willReturn('section one');
        $settingThree = $this->createMock(Model::class);
        $settingThree->expects($this->once())
            ->method($methodName)
            ->willReturn('section two');
        $settingFour = $this->createMock(Model::class);
        $settingFour->expects($this->once())
            ->method($methodName)
            ->willReturn('section two');
        $settingFive = $this->createMock(Model::class);
        $settingFive->expects($this->once())
            ->method($methodName)
            ->willReturn('section three');
        $settingFive->expects($this->once())
            ->method('getType')
            ->willReturn(ConfigConstInterface::RENDER_TYPE_NONE);

        $fixture = [
            'settingOne' => $settingOne,
            'settingTwo' => $settingTwo,
            'settingThree' => $settingThree,
            'settingFour' => $settingFour,
            'settingFive' => $settingFive
        ];

        // Inject the fixture
        Krexx::$pool->config->settings = $fixture;

        // Inject CallbackCounter for ConfigSection
        Krexx::$pool->rewrite = [
            ConfigSection::class => CallbackCounter::class
        ];

        // Test start event
        $throughConfig = new ThroughConfig(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConfig::callMe::start', $throughConfig]
        );

        // Run the test.
        $throughConfig->callMe();

        $this->assertEquals(2, CallbackCounter::$counter);
        // The setting five should be completely ignored, because it is not
        // renderable. Hence, there is no section involved.
        $expectation = [
            ['data' => [
                'settingOne' => $settingOne,
                'settingTwo' => $settingTwo
            ]],
            ['data' => [
                'settingThree' => $settingThree,
                'settingFour' => $settingFour
            ]]
        ];
        $this->assertEquals($expectation, CallbackCounter::$staticParameters);
    }
}
