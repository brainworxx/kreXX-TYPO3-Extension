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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\TimeStamp;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\ViewConstInterface;
use DateTime;

class TimeStampTest extends AbstractTest
{
    /**
     * Test something, that is always true.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\TimeStamp::isActive
     */
    public function testIsActive()
    {
        $this->assertTrue(TimeStamp::isActive());
    }

    /**
     * Test the handling of time stamps that got cast into a string.
     */
    public function testCanHandle()
    {
        $scalarTimeStamp = new TimeStamp(\Krexx::$pool);

        $fixture = 'xxx';
        $model = new Model(\Krexx::$pool);
        $this->assertFalse($scalarTimeStamp->canHandle($fixture, $model));
        $this->assertEmpty(
            $model->getJson(),
            'That is not a timestamp'
        );

        $fixture = '956681200 asdfsefsdf';
        $model = new Model(\Krexx::$pool);
        $this->assertFalse($scalarTimeStamp->canHandle($fixture, $model));
        $this->assertEmpty(
            $model->getJson(),
            'This is supposed to throw an error, that is actually handled.'
        );

        $fixture = (string) time();
        $model = new Model(\Krexx::$pool);
        $expectation = (new DateTime('@' . $fixture))->format('d.M Y H:i:s');
        $this->assertFalse($scalarTimeStamp->canHandle($fixture, $model));
        $result = $model->getJson();
        $this->assertArrayHasKey(ViewConstInterface::META_TIMESTAMP, $result);
        $this->assertEquals(
            $expectation,
            $result[ViewConstInterface::META_TIMESTAMP],
            'Test with a normal time stamp.'
        );

        $fixture = (string) microtime(true);
        $model = new Model(\Krexx::$pool);
        $expectation = (DateTime::createFromFormat('U.u', $fixture)->format('d.M Y H:i:s.u'));
        $this->assertFalse($scalarTimeStamp->canHandle($fixture, $model));
        $result = $model->getJson();
        $this->assertArrayHasKey(ViewConstInterface::META_TIMESTAMP, $result);
        $this->assertEquals(
            $expectation,
            $result[ViewConstInterface::META_TIMESTAMP],
            'Test with a micro time stamp.'
        );
    }
}
