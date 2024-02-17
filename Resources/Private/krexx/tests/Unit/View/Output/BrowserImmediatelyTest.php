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

namespace Brainworxx\Krexx\Tests\Unit\View\Output;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Cleanup;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\View\Output\BrowserImmediately;
use Brainworxx\Krexx\View\Output\Chunks;

class BrowserImmediatelyTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Krexx\View\Output\Browser
     */
    protected $browserImmediately;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->browserImmediately = new BrowserImmediately(Krexx::$pool);
    }

    /**
     * Since this is a wrapper around the Browser::shutdownCallback()
     * we recycle the same test.
     *
     * @covers \Brainworxx\Krexx\View\Output\BrowserImmediately::finalize
     */
    public function testFinalize()
    {
        $stringOne = '$stringOne';
        $stringTwo = '$stringTwo';
        $stringThree = '$stringTwo';

        $chunks = $this->createMock(Chunks::class);
        $chunks->expects($this->exactly(3))
            ->method('sendDechunkedToBrowser')
            ->with(...$this->withConsecutive(
                [$stringOne],
                [$stringTwo],
                [$stringThree]
            ));
        Krexx::$pool->chunks = $chunks;

        $this->browserImmediately->addChunkString($stringOne);
        $this->browserImmediately->addChunkString($stringTwo);
        $this->browserImmediately->addChunkString($stringThree);

        $cleanupMock = $this->createMock(Cleanup::class);
        $cleanupMock->expects($this->once())
            ->method('cleanupOldChunks');
        $this->setValueByReflection('cleanupService', $cleanupMock, $this->browserImmediately);

        $this->browserImmediately->finalize();
    }
}