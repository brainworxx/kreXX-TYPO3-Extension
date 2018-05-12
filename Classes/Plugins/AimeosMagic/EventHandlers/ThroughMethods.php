<?php
/**
 * Created by PhpStorm.
 * User: guelzow
 * Date: 12.05.2018
 * Time: 13:01
 */

namespace Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * In case we are using the factory method, we we use this one as a method name.
 *
 * @uses string $factoryName
 *   If present, we will use this as a function name.
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 */
class ThroughMethods implements EventHandlerInterface
{
    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

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
     * Replace the method name when we have a factory name set.
     *
     * @param AbstractCallback $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $params = $callback->getParameters();

        if (isset($params['factoryName'])) {
            $model->setName($params['factoryName']);
        }

        return '';
    }

}