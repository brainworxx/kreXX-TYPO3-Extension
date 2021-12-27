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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Simple model for the storage of a message.
 */
class Message
{
    /**
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * The key under which this message is stored.
     *
     * @var string
     */
    protected $key = '';

    /**
     * The raw arguments.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The text of this message.
     *
     * @var string
     */
    protected $text = '';

    /**
     * Will this message remove itself after its display?
     *
     * @var bool
     */
    protected $isThrowAway = false;

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Setter for the key.
     *
     * @param string $key
     *   The key.
     *
     * @return $this
     *   For chaining.
     */
    public function setKey(string $key): Message
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Setter force the throwaway.
     *
     * @param bool $isThrowAway
     *   Will remove itself after display.
     *
     * @return $this
     *   For chaining.
     */
    public function setIsThrowAway(bool $isThrowAway): Message
    {
        $this->isThrowAway = $isThrowAway;
        return $this;
    }

    /**
     * Setter for the raw arguments.
     *
     * @param array $arguments
     *
     * @return $this
     *   For chaining.
     */
    public function setArguments(array $arguments): Message
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Setter for the message text.
     *
     * @param string $text
     *
     * @return $this
     *   For chaining.
     */
    public function setText(string $text): Message
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Getter for the raw arguments.
     *
     * @return array
     *   The raw arguments.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get the text of the message.
     *
     * @return string
     *   The message, what else?
     */
    public function getText(): string
    {
        if ($this->isThrowAway === true) {
            // Removes itself, if it is a throwaway message.
            $this->pool->messages->removeKey($this->key);
        }

        return $this->text;
    }

    /**
     * Getter for the key.
     *
     * @return string
     *   The key.
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
