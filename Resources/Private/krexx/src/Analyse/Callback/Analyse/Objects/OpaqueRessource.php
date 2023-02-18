<?php

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Analyse the so-called opaque ressource classes of PHP 8, if possible.
 */
class OpaqueRessource extends AbstractCallback implements CallbackConstInterface
{
    /**
     * The classnames and their analysis callback.
     *
     * All the callbacks are supposed to return an array.
     *
     * @var string[]
     */
    protected $analysesCallbacks  = [
        \CurlHandle::class => 'curl_getinfo',
        \OpenSSLCertificate::class => 'openssl_x509_parse',
        \AddressInfo::class => 'socket_addrinfo_explain'
    ];

    /**
     * Analyse the sc called opaque ressource classes of PHP 8
     *
     * @return string
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            // Wrong PHP version.
            return $output;
        }

        $this->pool->codegenHandler->setCodegenAllowed(false);
        $data = $this->parameters[self::PARAM_DATA];

        // We iterate through the class list.
        // When we get the right instance, we trigger the analysis callback.
        // Every analysis callback is supposed to return an array.
        foreach ($this->analysesCallbacks as $className => $callback) {
            if ($data instanceof $className) {
                $output .= $this->pool->render->renderExpandableChild(
                    $this->dispatchEventWithModel(
                        static::EVENT_MARKER_ANALYSES_END,
                        $this->pool->createClass(Model::class)
                            ->setName($this->pool->messages->getHelp('metaRessourceAnalysis'))
                            ->setType(static::TYPE_INTERNALS)
                            ->addParameter(static::PARAM_DATA, (array)$callback($data))
                            ->injectCallback($this->pool->createClass(ThroughMeta::class))
                    )
                );
                break;
            }
        }

        $this->pool->codegenHandler->setCodegenAllowed(true);
        return $output;
    }
}
