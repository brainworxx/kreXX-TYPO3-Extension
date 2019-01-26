<?php
/**
 * Created by PhpStorm.
 * User: guelzow
 * Date: 08.01.2019
 * Time: 17:22
 */

namespace Brainworxx\Krexx\Tests\Fixtures;


class ComplexPropertiesInheritanceFixture
{

    /**
     * My private Property
     *
     * @var string
     */
    private $myProperty = 'my property';

    /**
     * Inherited protected property.
     *
     * @var \stdClass
     */
    protected $inheritedProtected;

    public $inheritedPublic = 'inherited public';

    /**
     * @var null
     */
    protected $inheritedNull;
}
