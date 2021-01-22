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

declare(strict_types=1);

namespace Brainworxx\Krexx\Logging;

/**
 * Logger model for kreXX.
 *
 * It's being analysed like an exception, but it's not a \Throwable.
 * If you want to log something with an analysed backtrace and source display,
 * use or extend this one.
 *
 * @package Brainworxx\Krexx\Logging
 */
class Model
{
    /**
     * The saved backtrace.
     *
     * We must not name it "trace" to prevent the getter analysis to coax the
     * actual output from this class.
     *
     * @var array
     */
    protected $backtrace = [];

    /**
     * The message to log.
     *
     * @var string
     */
    protected $message = '';

    /**
     * The line, where the logging occurred.
     *
     * @var int
     */
    protected $line = 0;

    /**
     * The file, where the logging occurred.
     *
     * @var string
     */
    protected $file = '';

    /**
     * The error code.
     *
     * @var string
     */
    protected $code = '';

    /**
     * Getter for the message.
     *
     * We must let this one be the first getter in the class, so the getter
     * analysis displays this one on top.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param array $backtrace
     *
     * @return Model
     *   Return this for chaining.
     */
    public function setTrace(array $backtrace): Model
    {
        // Use it is several lines, to prevent the getter analysis to
        // coax the actual output out of the class.
        $this
            ->backtrace = $backtrace;
        return $this;
    }

    /**
     * @return array
     */
    public function getTrace(): array
    {
        return $this->backtrace;
    }

    /**
     * @param string $message
     *
     * @return Model
     *   Return this for chaining.
     */
    public function setMessage(string $message): Model
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param int $line
     *
     * @return Model
     *   Return this for chaining.
     */
    public function setLine(int $line): Model
    {
        $this->line = $line;
        return $this;
    }

    /**
     * @param string $file
     *
     * @return Model
     *   Return this for chaining.
     */
    public function setFile(string $file): Model
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param string $code
     *
     * @return Model
     *   Return this for chaining.
     */
    public function setCode(string $code): Model
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
