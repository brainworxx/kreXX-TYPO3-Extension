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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\View\Message;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Skins\RenderHans;

class MessagesTest extends AbstractHelper
{

    const KEY_VARIABLE_NAME = 'messages';
    const PARAMS = 'params';

    /**
     * @var Messages
     */
    protected $messagesClass;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->messagesClass = new Messages(Krexx::$pool);
        $this->messagesClass->readHelpTexts();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->setValueByReflection('additionalLanguages', [], Registration::class);
    }

    /**
     * Test the initializing of the messages class.
     *
     * @covers \Brainworxx\Krexx\View\Messages::__construct
     */
    public function testConstruct()
    {
        $this->assertSame(Krexx::$pool->messages, $this->messagesClass);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->messagesClass));
    }

    /**
     * Test the removing od message keys.
     *
     * @covers \Brainworxx\Krexx\View\Messages::removeKey
     */
    public function testRemoveKey()
    {
        $messageKey = 'key 2';
        $this->setValueByReflection(static::KEY_VARIABLE_NAME, [$messageKey => 'whatever'], $this->messagesClass);

        $this->messagesClass->removeKey($messageKey);
        $this->assertEquals([], $this->messagesClass->getMessages());
    }

    /**
     * Testing the outputting of messages.
     *
     * @covers \Brainworxx\Krexx\View\Messages::outputMessages
     */
    public function testOutputMessages()
    {
        // Simulate a cli request. Actually, it already is a cli request, we
        // test the checking of it.
        $sapiMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View', 'php_sapi_name');
        $sapiMock->expects($this->once())->will($this->returnValue('cli'));

        // We pretend as if this is not a test.
        $definedMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View', 'defined');
        $definedMock->expects($this->once())->will($this->returnValue(false));

        $messages = [];
        $message = new Message(\Krexx::$pool);
        $message->setText('qwer');
        $messages[] = $message;
        $message = new Message(\Krexx::$pool);
        $message->setText('asdf');
        $messages[] = $message;
        $message = new Message(\Krexx::$pool);
        $message->setText('yxcv');
        $messages[] = $message;

        $rendermock = $this->createMock(RenderHans::class);
        $rendermock->expects($this->once())
            ->method('renderMessages')
            ->with($messages)
            ->will($this->returnValue(''));
        Krexx::$pool->render = $rendermock;
        $this->setValueByReflection('messages', $messages, $this->messagesClass);

        $expected = "\n\nkreXX messages\n" .
            "==============\n" .
            "qwer\n" .
            "asdf\n" .
            "yxcv\n" .
            "\n\n";

        $this->expectOutputString($expected);

        $this->messagesClass->outputMessages();
    }

    /**
     * Seriously, get help!
     *
     * @covers \Brainworxx\Krexx\View\Messages::getHelp
     */
    public function testGetHelp()
    {
        $helpArray = ['doctor' => 'Some %s string.'];
        $this->setValueByReflection('helpArray', $helpArray, $this->messagesClass);

        $this->assertEquals('', $this->messagesClass->getHelp('unknown key'));
        $this->assertEquals('Some stupid string.', $this->messagesClass->getHelp('doctor', ['stupid']));
    }

    /**
     * Test a simple getter.
     *
     * @covers \Brainworxx\Krexx\View\Messages::getMessages
     * @covers \Brainworxx\Krexx\View\Messages::addMessage
     */
    public function testGetMessages()
    {
        $fixture = [
            ['california' => []],
            ['house' => ['keeper']],
            ['of the' => ['seven', 'keys']]
        ];
        foreach ($fixture as $arguments) {
            $key = array_keys($arguments)[0];
            $this->messagesClass->addMessage($key, $arguments[$key]);
        }

        $count = 0;
        foreach ($this->messagesClass->getMessages() as $key => $message) {
            $this->assertEquals(array_keys($fixture[$count])[0], $key);
            $this->assertEquals($key, $message->getKey());
            $this->assertEquals($fixture[$count][$key], $message->getArguments());
            ++$count;
        }
    }

    /**
     * Purging of the already read stuff, and read it again.
     *
     * @covers \Brainworxx\Krexx\View\Messages::readHelpTexts
     */
    public function testReadHelpTexts()
    {
        $iniContents = '[text]' . "\n" .
            'someKey = "a string"';

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method('getFileContents')
            ->with(KREXX_DIR . 'resources/language/Help.ini')
            ->will($this->returnValue($iniContents));
        Krexx::$pool->fileService = $fileServiceMock;

        $this->messagesClass->readHelpTexts();
        $this->assertEquals(
            ['someKey' => 'a string'],
            $this->retrieveValueByReflection('helpArray', $this->messagesClass)
        );
    }

    /**
     * Test the assignment of the language key.
     *
     * @covers \Brainworxx\Krexx\View\Messages::setLanguageKey
     * @covers \Brainworxx\Krexx\View\Messages::readHelpTexts
     * @covers \Brainworxx\Krexx\View\Messages::getHelp
     */
    public function testSetLanguageKey()
    {
        $this->messagesClass->setLanguageKey('de');
        Registration::addLanguage('anykey', 'Any Key');
        Registration::registerAdditionalHelpFile(KREXX_DIR . 'tests/Fixtures/Language.ini');
        $this->messagesClass->readHelpTexts();

        $this->assertEquals(
            'a string',
            $this->messagesClass->getHelp('someKey'),
            'Test the usage of the language key above.'
        );

        $this->assertEquals(
            'Gesamtzeit',
            $this->messagesClass->getHelp('metaTotalTime'),
            'Test if the original language is still available'
        );
    }
}
