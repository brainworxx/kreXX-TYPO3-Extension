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

declare(strict_types=1);

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Messaging system.
 */
class Messages
{
    /**
     * Here we store all messages, which gets send to the output.
     *
     * @var Message[]
     */
    protected array $messages = [];

    /**
     * A simple array to hold the values.
     *
     * @var string[]
     */
    protected array $helpArray = [];

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * The language key where the texts are stored.
     *
     * @var string
     */
    protected string $languageKey = 'en';

    /**
     * Injects the pool and reads the language file.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $pool->messages = $this;
    }

    /**
     * Setter for the language key.
     *
     * Gets set by the configuration class after it was loaded.
     *
     * @param string $languageKey
     *   The language key.
     *
     * @return $this
     *   For chaining.
     */
    public function setLanguageKey(string $languageKey): Messages
    {
        $this->languageKey = $languageKey;
        $this->readHelpTexts();
        return $this;
    }

    /**
     * The message we want to add. It will be displayed in the output.
     *
     * @param string $key
     *   The message itself.
     * @param array $args
     *   The parameters for vsprintf().
     * @param bool $isThrowAway
     *   will this message remove itself after display?
     */
    public function addMessage(string $key, array $args = [], bool $isThrowAway = false): void
    {
        // We will only display these messages once.
        if (!isset($this->messages[$key])) {
            // Add it to the keys, so the CMS can display it.
            $this->messages[$key] = $this->pool->createClass(Message::class)
                ->setKey($key)
                ->setArguments($args)
                ->setText($this->getHelp($key, $args))
                ->setIsThrowAway($isThrowAway);
        }
    }

    /**
     * Removes a key from the key array.
     *
     * @param string $key
     *   The key we want to remove
     */
    public function removeKey(string $key): void
    {
        unset($this->messages[$key]);
    }

    /**
     * Getter for the messages.
     *
     * @return Message[]
     *   The current list of message classes.
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Renders the output of the messages.
     *
     * @return string
     *   The rendered html output of the messages.
     */
    public function outputMessages(): string
    {
        // Simple Wrapper for OutputActions::$render->renderMessages
        if (
            php_sapi_name() === 'cli' &&
            !empty($this->messages) &&
            !defined('KREXX_TEST_IN_PROGRESS')
        ) {
            // Output the messages on the shell.
            $result = "\n\nkreXX messages\n";
            $result .= "==============\n";
            foreach ($this->messages as $message) {
                $result .= $message->getText() . "\n";
            }

            echo $result . "\n\n";
        }

        // Return the rendered messages.
        return $this->pool->render->renderMessages($this->messages);
    }

    /**
     * Returns the help text when found, otherwise returns an empty string.
     *
     * @param string $key
     *   The help ID from the array above.
     * @param  array $args
     *   THe replacement arguments for vsprintf().
     *
     * @return string
     *   The help text.
     */
    public function getHelp(string $key, array $args = []): string
    {
        return vsprintf($this->helpArray[$key] ?? '', $args);
    }

    /**
     * Reset the read help texts to factory settings.
     */
    public function readHelpTexts(): void
    {
        $helpArray = [];

        $fileList = [KREXX_DIR . 'resources/language/Help.ini', ...SettingsGetter::getAdditionalHelpFiles()];

        foreach ($fileList as $filename) {
            $helpArray = array_replace_recursive(
                $helpArray,
                (array)parse_ini_string($this->pool->fileService->getFileContents($filename, false), true)
            );
        }

        $this->helpArray = array_merge($helpArray['en'], $helpArray[$this->languageKey] ?? []);
    }
}
