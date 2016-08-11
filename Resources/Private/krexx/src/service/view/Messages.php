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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\View;

use Brainworxx\Krexx\Service\Storage;

/**
 * Messaging system.
 *
 * @package Brainworxx\Krexx\Service\View
 */
class Messages
{

    /**
     * Here we store all relevant data.
     *
     * @var Storage
     */
    protected $storage;

    /**
     * Here we store all messages, which gets send to the output.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * The translatable keys for backend integration.
     *
     * @var array
     */
    protected $keys = array();

    /**
     * Injects the storage.
     *
     * @param Storage $storage
     *   The storage, where we store the classes we need.
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * The message we want to add. It will be displayed in the output.
     *
     * @param string $message
     *   The message itself.
     * @param string $class
     *   The class of the message.
     */
    public function addMessage($message, $class = 'normal')
    {
        $this->messages[$message] = array(
            'message' => $message,
            'class' => $class
        );
    }

    /**
     * Adds message keys to the key array.
     *
     * The same as the addMessage, but we add language keys for a potential
     * backend integration (includekrexx for example).
     *
     * @param string $key
     *   The key for the translation function.
     * @param NULL|array $params
     *   The parameters for the string replacements inside the translation.
     */
    public function addKey($key, $params = null)
    {
        $this->keys[$key] = array('key' => $key, 'params' => $params);
    }

    /**
     * Removes a key from the key array.
     *
     * @param string $key
     *   The key we want to remove
     */
    public function removeKey($key)
    {
        unset($this->keys[$key]);
    }

    /**
     * Getter for the language key array.
     *
     * @return array
     *   The language keys we added beforehand.
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Renders the output of the messages.
     *
     * @return string
     *   The rendered html output of the messages.
     */
    public function outputMessages()
    {
        // Simple Wrapper for OutputActions::$render->renderMessages
        if (php_sapi_name() == "cli") {
            if (count($this->messages)) {
                $result = "\n\nkreXX messages\n";
                $result .= "==============\n";
                foreach ($this->messages as $message) {
                    $message = $message['message'];
                    $result .= "$message\n";
                }
                $result .= "\n\n";
                return $result;
            }
        } else {
            return $this->storage->render->renderMessages($this->messages);
        }
        // Still here?
        return '';
    }
}
