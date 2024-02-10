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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Code;

use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;

class ConnectorsTest extends AbstractHelper
{
    const TEST_STRING = 'test me';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectors = new Connectors();
    }

    /**
     * @var \Brainworxx\Krexx\Analyse\Code\Connectors
     */
    protected $connectors;

    /**
     * Test the setting of the parameters
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::setParameters
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::getParameters
     */
    public function testSetParameters()
    {
        $this->connectors->setParameters(static::TEST_STRING);
        $this->assertEquals(static::TEST_STRING, $this->connectors->getParameters());
    }

    /**
     * Test the seter of the type
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::setType
     */
    public function testSetType()
    {
        $this->connectors->setType(static::TEST_STRING);
        $this->assertEquals(static::TEST_STRING, $this->retrieveValueByReflection('type', $this->connectors));
    }

    /**
     * Test the getter for the left connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::getConnectorLeft
     */
    public function testGetConnectorLeft()
    {
        // Without custom connector.
        $this->connectors->setType($this->connectors::CONNECTOR_ASSOCIATIVE_ARRAY);
        $this->assertEquals('[\'', $this->connectors->getConnectorLeft());

        // With custom connectors.
        $this->connectors->setCustomConnectorLeft(static::TEST_STRING);
        $this->assertEquals(static::TEST_STRING, $this->connectors->getConnectorLeft());
    }

    /**
     * Test the getter for the right connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::getConnectorRight
     */
    public function testGetConnectorRight()
    {
        // Test with methods and without parameters
        $this->connectors->setType($this->connectors::CONNECTOR_STATIC_METHOD);
        $this->assertEquals('()', $this->connectors->getConnectorRight(0));
        $this->connectors->setType($this->connectors::CONNECTOR_METHOD);
        $this->assertEquals('()', $this->connectors->getConnectorRight(0));

        // Test with methods and parameters.
        $this->connectors->setParameters('some parameter');
        $this->connectors->setType($this->connectors::CONNECTOR_STATIC_METHOD);
        $this->assertEquals('(some parameter)', $this->connectors->getConnectorRight(0));
        $this->connectors->setType($this->connectors::CONNECTOR_METHOD);
        $this->assertEquals('(some parameter)', $this->connectors->getConnectorRight(0));
        $this->assertEquals('(some  . . . )', $this->connectors->getConnectorRight(5));

        // Test with some other type
        $this->connectors->setType($this->connectors::CONNECTOR_CONSTANT);
        $this->assertEmpty($this->connectors->getConnectorRight(0));
    }

    /**
     * Test the setter for a custom connector left.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::setCustomConnectorLeft
     */
    public function testSetCustomconnectorLeft()
    {
        $this->connectors->setCustomConnectorLeft(static::TEST_STRING);
        $this->assertEquals(static::TEST_STRING, $this->connectors->getConnectorLeft());
    }

    /**
     * Test the getLanguage
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::getLanguage
     */
    public function testGetLanguage()
    {
        $this->assertEquals('php', $this->connectors->getLanguage());
    }
}
