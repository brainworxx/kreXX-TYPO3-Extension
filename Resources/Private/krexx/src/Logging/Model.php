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
     * This one is public, so the dev can see the actual message right away.
     *
     * @var string
     */
    public $message = '';

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
     * Setter for the backtrace.
     *
     * @param array $backtrace
     *   The backtrace.
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
     * Getter for the backtrace
     *
     * @return array
     *   The backtrace.
     */
    public function getTrace(): array
    {
        return $this->backtrace;
    }

    /**
     * Setter for the log message.
     *
     * @param string $message
     *   The log message.
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
     * Setter for the line where the logging was triggered.
     *
     * @param int $line
     *   The line number.
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
     * Setter for the file name, where the logging was triggered.
     *
     * @param string $file
     *   The file name with the path.
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
     * Setter for the logging code.
     *
     * @param string $code
     *   The logging code.
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
     * Getter for the logging code.
     *
     * @return string
     *   The logging code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Getter for the file name, where the logging was triggered.
     *
     * @return string
     *   The file name.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Getter for the line, where the logging was triggered.
     *
     * @return int
     *   The line number.
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
