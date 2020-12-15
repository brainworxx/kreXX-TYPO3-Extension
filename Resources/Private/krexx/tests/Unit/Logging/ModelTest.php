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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Logging;

use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

/**
 * Test for the logger model.
 *
 * @package Brainworxx\Krexx\Tests\Unit\Logging
 */
class ModelTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Logging\Model
     */
    protected $model;

    protected function krexxUp()
    {
        parent::krexxUp();

        $this->model = new \Brainworxx\Krexx\Logging\Model();
    }

    /**
     * Test the getter and setter of the message.
     *
     * @covers \Brainworxx\Krexx\Logging\Model::getMessage
     * @covers \Brainworxx\Krexx\Logging\Model::setMessage
     */
    public function testSetGetMessage()
    {
        $message = 'some message';

        $this->model->setMessage($message);
        $this->assertEquals($message, $this->model->getMessage());
    }

    /**
     * Test the getter and setter of the trace.
     *
     * @covers \Brainworxx\Krexx\Logging\Model::getTrace
     * @covers \Brainworxx\Krexx\Logging\Model::setTrace
     */
    public function testSetGetTrace()
    {
        $trace = [
          'what' => 'ever',
          'bib' => 'bub'
        ];

        $this->model->setTrace($trace);
        $this->assertEquals($trace, $this->model->getTrace());
    }

    /**
     * Test the getter and setter of the line.
     *
     * @covers \Brainworxx\Krexx\Logging\Model::getLine
     * @covers \Brainworxx\Krexx\Logging\Model::setLine
     */
    public function testSetGetLine()
    {
        $line = 42;

        $this->model->setLine($line);
        $this->assertEquals($line, $this->model->getLine());
    }

    /**
     * Test the getter and setter of the file.
     *
     * @covers \Brainworxx\Krexx\Logging\Model::getFile
     * @covers \Brainworxx\Krexx\Logging\Model::setFile
     */
    public function testSetGetFile()
    {
        $file = 'autoexec.bat';

        $this->model->setFile($file);
        $this->assertEquals($file, $this->model->getFile());
    }

    /**
     * Test the getter and setter of the code.
     *
     * @covers \Brainworxx\Krexx\Logging\Model::getCode
     * @covers \Brainworxx\Krexx\Logging\Model::setCode
     */
    public function testSetGetCode()
    {
        $code = '1234 asdf';

        $this->model->setCode($code);
        $this->assertEquals($code, $this->model->getCode());
    }
}