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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;

class ProcessBooleanTest extends AbstractTest
{
    /**
     * Testing the processing of booleans.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean::process
     */
    public function testProcess()
    {
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;
        $fixture = true;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessBoolean(Krexx::$pool);
        $processor->process($model);

        $fixture = false;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessBoolean(Krexx::$pool);
        $processor->process($model);

        $models = $renderNothing->model['renderSingleChild'];

        $this->assertEquals('TRUE', $models[0]->getData());
        $this->assertEquals('TRUE', $models[0]->getNormal());
        $this->assertEquals(ProcessBoolean::TYPE_BOOL, $models[0]->getType());
        $this->assertEquals('FALSE', $models[1]->getData());
        $this->assertEquals('FALSE', $models[1]->getNormal());
        $this->assertEquals(ProcessBoolean::TYPE_BOOL, $models[1]->getType());
    }
}