<?php

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use CurlHandle;
use GdImage;
use OpenSSLCertificate;
use AddressInfo;

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
    protected array $analysesCallbacks  = [
        CurlHandle::class => 'curlHandler',
        OpenSSLCertificate::class => 'openSslCertHandler',
        AddressInfo::class => 'socketAddressHandler',
        GdImage::class => 'gdImageHandler',
    ];

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Retrieve information about a GD image resource.
     *
     * @param \GdImage $image
     *   The GD image resource to get information about.
     *
     * @return array
     *   An associative array with all available information.
     */
    protected function gdImageHandler(GdImage $image): array
    {
        if (!function_exists(function: 'image_type_to_mime_type')) {
            return ['error' => static::UNKNOWN_VALUE];
        }
        return [
            'imagesx' => imagesx($image),
            'imagesy' => imagesy($image),
            'imageresolution' => imageresolution($image),
            'imageistruecolor' => imageistruecolor($image),
            'imagecolorstotal' => imagecolorstotal($image),
            'imagecolortransparent' => imagecolortransparent($image) !== -1,
            'imageinterlace' => imageinterlace($image),
            'imagepalettetotruecolor' => imagepalettetotruecolor($image) ? 'truecolor' : 'palette',
        ];
    }

    /**
     * Retrieve information about a socket address resource.
     * @param \AddressInfo $address
     *   The AddressInfo resource to get information about.
     *
     * @return array
     *   An associative array with all available information.
     */
    protected function socketAddressHandler(AddressInfo $address): array
    {
        if (!function_exists(function: 'socket_addrinfo_explain')) {
            return ['error' => static::UNKNOWN_VALUE];
        }
        return socket_addrinfo_explain($address);
    }

    /**
     * Retrieve information about an OpenSSL certificate.
     *
     * @param \OpenSSLCertificate $certificate
     *   The OpenSSL certificate to get information about.
     *
     * @return array
     *   An associative array with all available information.
     */
    protected function openSslCertHandler(OpenSSLCertificate $certificate): array
    {
        if (!function_exists(function: 'openssl_x509_parse')) {
            return ['error' => static::UNKNOWN_VALUE];
        }
        return openssl_x509_parse($certificate);
    }

    /**
     * Retrieve information about a cURL handle.
     *
     * @param \CurlHandle $curlHandle
     *   The cURL handle to get information about.
     *
     * @return array
     *   An associative array with all available information.
     */
    protected function curlHandler(CurlHandle $curlHandle): array
    {
        if (!function_exists(function: 'curl_getinfo')) {
            return ['error' => static::UNKNOWN_VALUE];
        }
        return curl_getinfo(handle: $curlHandle);
    }

    /**
     * Analyse the sc called opaque ressource classes of PHP 8
     *
     * @return string
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        $this->pool->codegenHandler->setCodegenAllowed(bool: false);
        $data = $this->parameters[static::PARAM_DATA];

        // We iterate through the class list.
        // When we get the right instance, we trigger the analysis callback.
        // Every analysis callback is supposed to return an array.
        foreach ($this->analysesCallbacks as $className => $callback) {
            if ($data instanceof $className) {
                $output .= $this->pool->render->renderExpandableChild(
                    model: $this->dispatchEventWithModel(
                        name: static::EVENT_MARKER_ANALYSES_END,
                        model: $this->pool->createClass(classname: Model::class)
                            ->setName(name: $this->pool->messages->getHelp(key: 'metaRessourceAnalysis'))
                            ->setType(type: $this->pool->messages->getHelp(key: 'classInternals'))
                            ->addParameter(name: static::PARAM_DATA, value: $this->$callback($data))
                            ->injectCallback(object: $this->pool->createClass(classname: ThroughMeta::class))
                    )
                );
            }
        }

        $this->pool->codegenHandler->setCodegenAllowed(bool: true);
        return $output;
    }
}
