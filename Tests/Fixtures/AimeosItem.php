<?php


namespace Brainworxx\Includekrexx\Tests\Fixtures;

use Aimeos\MShop\Product\Item\Standard as StandardProduct;

class AimeosItem extends StandardProduct
{
    /**
     * Short circuiting the original method, because I'm to lazy to really fill
     * it the fixture with "meaningfull" stuff for the tests.
     *
     * {@inheritDoc}
     */
    public function getListItems($domain = null, $listtype = null, $type = null, $active = true)
    {
        return [
            new \StdClass(),
            new \DateTime()
        ];
    }

    /**
     * More short circuiting.
     *
     * {@inheritDoc}
     */
    public function getRefItems($domain = null, $type = null, $listtype = null, $active = true)
    {
        return [
            new \StdClass(),
            new \DateTime()
        ];
    }
}