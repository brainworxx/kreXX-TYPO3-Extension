<?php

namespace Brainworxx\Includekrexx\Tests\Fixtures;

use Brainworxx\Includekrexx\Tests\Fixtures\Aimeos20Item;

class Aimeos24Item extends Aimeos20Item
{
    /**
     * Aimeos 24.10 has a different constructor than the others.
     *
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct('.product', []);
    }
}
