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

namespace Brainworxx\Krexx\Tests\Unit\View;

use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\View\Message;
use Brainworxx\Krexx\View\Messages;
use Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Message::class, '__construct')]
#[CoversMethod(Message::class, 'getKey')]
#[CoversMethod(Message::class, 'setKey')]
#[CoversMethod(Message::class, 'setIsThrowAway')]
#[CoversMethod(Message::class, 'setArguments')]
#[CoversMethod(Message::class, 'getArguments')]
#[CoversMethod(Message::class, 'setText')]
#[CoversMethod(Message::class, 'getText')]
class MessageTest extends AbstractHelper
{
    /**
     * Test the setting of the pool
     */
    public function testConstruct()
    {
        $message = new Message(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $message));
    }

    /**
     * Test the setter / getter of the key.
     */
    public function testSetGetKey()
    {
        $message = new Message(Krexx::$pool);
        $message->setKey('house key');
        $this->assertEquals('house key', $message->getKey());
    }

    /**
     * Test the setter of the trow away status.
     */
    public function testSetIsThrowAway()
    {
        $message = new Message(Krexx::$pool);
        $message->setIsThrowAway(true);
        $this->assertTrue($this->retrieveValueByReflection('isThrowAway', $message));
    }

    /**
     * Test the setter/getter for the arguments.
     */
    public function testSetGetArguments()
    {
        $fixture = ['my', 'little', 'fixture'];
        $message = new Message(Krexx::$pool);
        $message->setArguments($fixture);
        $this->assertEquals($fixture, $message->getArguments());
    }

    /**
     * Test the setter/getter of the text. We also take a look at the message
     * removal
     */
    public function testSetGetText()
    {
        $text = 'You can lorem my ipsum';
        $key = 'car keys.';
        $message = new Message(Krexx::$pool);
        $message->setText($text)->setKey($key);
        $this->assertEquals($text, $message->getText(), 'Normal getter setter test');

        // And now for the throw away messages . . .
        $message = new Message(Krexx::$pool);
        $message->setText($text)->setKey($key)->setIsThrowAway(true);
        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->expects($this->once())
            ->method('removeKey')
            ->with($key);
        Krexx::$pool->messages = $messagesMock;

        $message->getText();
    }
}
