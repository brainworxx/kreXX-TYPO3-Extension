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

namespace Brainworxx\Krexx\Tests\Unit\View\Skins\Hans;

use Brainworxx\Krexx\Tests\Unit\View\Skins\AbstractRenderHans;
use Brainworxx\Krexx\View\Message;
use Brainworxx\Krexx\View\Skins\Hans\Messages;
use Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Messages::class, 'renderMessages')]
class MessagesTest extends AbstractRenderHans
{
    /**
     * Test the message rendering.
     */
    public function testRenderMessages()
    {
        $fixture = [
            'How do I activate SMS?',
            'How can I readSMS?',
            'What is a messager?',
            'Why am I writing this?'
        ];

        $messages = [];
        foreach ($fixture as $text) {
            $message = new Message(Krexx::$pool);
            $message->setText($text);
            $messages[] = $message;
        }

        $result = $this->renderHans->renderMessages($messages);
        foreach ($fixture as $text) {
            $this->assertStringContainsString($text, $result);
        }
    }
}
