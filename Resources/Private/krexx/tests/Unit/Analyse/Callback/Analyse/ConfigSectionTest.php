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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\View\Skins\RenderHans;

class ConfigSectionTest extends AbstractTest
{
    /**
     * Testing if the configuration is rendered correctly.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection::generateOutput
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection::prepareValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\AbstractCallback::dispatchStartEvent
     */
    public function testCallMe()
    {
        // Prepare the fixture.
        $noRender = new Model();
        $renderEditable = new Model();
        $renderNotEditable = new Model();

        $sectionString = 'some Section';
        $sourceString = 'some source';
        $valueString = 'some value';

        $noRender->setSection($sectionString)
            ->setEditable(true)
            ->setSource($sourceString)
            ->setType(Fallback::RENDER_TYPE_NONE)
            ->setValue($valueString);

        $renderEditable->setSection($sectionString)
            ->setEditable(true)
            ->setSource($sourceString)
            ->setType(Fallback::RENDER_TYPE_INPUT)
            ->setValue(true);

        $renderNotEditable->setSection($sectionString)
            ->setEditable(false)
            ->setSource($sourceString)
            ->setType(Fallback::RENDER_TYPE_INPUT)
            ->setValue(false);

        $data = ['data' =>
            [
                'noRender' => $noRender,
                'renderEditable' => $renderEditable,
                'renderNotEditable' => $renderNotEditable
            ]
        ];

        $configSection = new ConfigSection(Krexx::$pool);

        $configSection->setParameters($data);
        // Test if start event has fired
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\ConfigSection::callMe::start', $configSection]
        );

        // Test Render Type None
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->exactly(6))
            ->method('getHelp')
            ->withConsecutive(
                ['metaHelp'],
                ['renderEditableHelp'],
                ['renderEditableReadable'],
                ['metaHelp'],
                ['renderNotEditableHelp'],
                ['renderNotEditableReadable']
            )
            ->will($this->returnValue('some help text'));
        Krexx::$pool->messages = $messageMock;

        // Test if editable or not
        $renderMock = $this->createMock(RenderHans::class);
        $renderMock->expects($this->once())
            ->method('renderSingleEditableChild')
            ->with($this->anything())
            ->will($this->returnValue('some string'));
        $renderMock->expects($this->once())
            ->method('renderExpandableChild')
            ->with($this->anything())
            ->will($this->returnValue('some string'));
        Krexx::$pool->render = $renderMock;

        // Run it!
        $configSection->callMe();
    }
}
