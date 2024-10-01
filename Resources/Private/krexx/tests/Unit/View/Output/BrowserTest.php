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
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\Chunks;

class BrowserTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Krexx\View\Output\Browser
     */
    protected $browser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser(Krexx::$pool);
    }

    /**
     * Test the initializing of the send-stuff-to-browser mechanism.
     *
     * @covers \Brainworxx\Krexx\View\Output\Browser::shutdownCallback
     * @covers \Brainworxx\Krexx\View\Output\AbstractOutput::destruct
     */
    public function testShutdownCallback()
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

        $this->browser->addChunkString($stringOne);
        $this->browser->addChunkString($stringTwo);
        $this->browser->addChunkString($stringThree);

        $cleanupMock = $this->createMock(Cleanup::class);
        $cleanupMock->expects($this->once())
            ->method('cleanupOldChunks');
        $this->setValueByReflection('cleanupService', $cleanupMock, $this->browser);

        $this->browser->shutdownCallback();
    }

    /**
     * Test the registration of this class in the php shutdown phase.
     */
    public function testFinalize()
    {
        $shutdownFunction = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\View\\Output\\',
            'register_shutdown_function'
        );
        $shutdownFunction->expects($this->once())
            ->with([$this->browser, 'shutdownCallback']);

        $this->browser->finalize();
    }
}
