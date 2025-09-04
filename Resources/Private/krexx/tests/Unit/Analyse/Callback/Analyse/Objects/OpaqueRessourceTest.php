<?php

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\OpaqueRessource;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(OpaqueRessource::class, 'callMe')]
#[CoversMethod(OpaqueRessource::class, 'gdImageHandler')]
#[CoversMethod(OpaqueRessource::class, 'socketAddressHandler')]
#[CoversMethod(OpaqueRessource::class, 'openSslCertHandler')]
#[CoversMethod(OpaqueRessource::class, 'curlHandler')]
class OpaqueRessourceTest extends AbstractHelper implements CallbackConstInterface
{
     /**
     * What the method name says. Call it with a simulated wrong php version.
     */
    public function testCallMeWrongPhpVersion()
    {
        $versionCompareMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects',
            'version_compare'
        );
        $versionCompareMock->expects($this->once())
            ->willReturn(true);

        // We only expect the start event, nothing more.
        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque]
        );

        $this->assertEquals('', $opaque->callMe());
    }

    /**
     * Test the analysis of the so-called opaque ressource class analysis.
     */
    public function testCallMeCurl()
    {
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $this->mockEmergencyHandler();

        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::analysisEnd', $opaque]
        );

        $fixture = [self::PARAM_DATA => curl_init()];
        $opaque->setParameters($fixture);
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $opaque->callMe();

        $result = $renderNothing->model['renderExpandableChild'][0]->getParameters()[static::PARAM_DATA];
        // Getting a quick glance at the results.
        $this->assertEquals('', $result['url']);
        $this->assertEquals(0, $result['http_code']);
        $this->assertEquals(0, $result['redirect_count']);
    }

    public function testCallMeAddressInfo()
    {
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $this->mockEmergencyHandler();

        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::analysisEnd', $opaque]
        );

        $fixture = [self::PARAM_DATA => socket_addrinfo_lookup('localhost')[0]];
        $opaque->setParameters($fixture);
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $opaque->callMe();

        $result = $renderNothing->model['renderExpandableChild'][0]->getParameters()[static::PARAM_DATA];
        // Getting a quick glance at the results.
        // Other servers might return different values here.
        $this->assertArrayHasKey('ai_flags', $result);
        $this->assertArrayHasKey('ai_family', $result);
        $this->assertArrayHasKey('ai_socktype', $result);
        $this->assertArrayHasKey('ai_protocol', $result);
        $this->assertArrayHasKey('ai_addr', $result);
    }

    public function testCallMeSslCert()
    {
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $this->mockEmergencyHandler();

        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::analysisEnd', $opaque]
        );

        // A sample certificate.
        // Generated with:
        // openssl req -x509 -newkey rsa:515 -nodes -out cert.pem -keyout key.pem -days 365
        // and pressing return for all questions.
        $certificate = '-----BEGIN CERTIFICATE-----
MIIB4jCCAYugAwIBAgIUZhJKtdI3+THXbGfFJtkUd7j8OU4wDQYJKoZIhvcNAQEL
BQAwRTELMAkGA1UEBhMCQVUxEzARBgNVBAgMClNvbWUtU3RhdGUxITAfBgNVBAoM
GEludGVybmV0IFdpZGdpdHMgUHR5IEx0ZDAeFw0yNTA5MDQxMjQyMTNaFw0yNjA5
MDQxMjQyMTNaMEUxCzAJBgNVBAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEw
HwYDVQQKDBhJbnRlcm5ldCBXaWRnaXRzIFB0eSBMdGQwXDANBgkqhkiG9w0BAQEF
AANLADBIAkEF9EimbiXHEddtNvYmShGvca63Uzx2Ab8IpLLFmtMrFyEi3ZlHUAx9
0ePAhU/pFQO9AoBWmARc8aPmeI5KIG2b/wIDAQABo1MwUTAdBgNVHQ4EFgQUlgSr
xXmablTchtb+T9QZ5HYLNTUwHwYDVR0jBBgwFoAUlgSrxXmablTchtb+T9QZ5HYL
NTUwDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAANCAAIM70prf5wfoFQr
5xjK7HorFvtD0tBC6RIWeVHeMfE8i5OYu1K+nBIxwGaJVT8twYU7erleKe4UzgNL
hjJuL9EH
-----END CERTIFICATE-----';

        $fixture = [self::PARAM_DATA => openssl_x509_read($certificate)];
        $opaque->setParameters($fixture);
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $opaque->callMe();

        $result = $renderNothing->model['renderExpandableChild'][0]->getParameters()[static::PARAM_DATA];

        // Getting a quick glance at the results.
        $this->assertEquals('/C=AU/ST=Some-State/O=Internet Widgits Pty Ltd', $result['name']);
        $this->assertEquals('0x66124AB5D237F931D76C67C526D91477B8FC394E', $result['serialNumber']);
        $this->assertEquals('250904124213Z', $result['validFrom']);
        $this->assertEquals('Some-State', $result['subject']['ST']);
    }

    public function testCallMeGlImage()
    {
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $this->mockEmergencyHandler();

        $opaque = new OpaqueRessource(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::callMe::start', $opaque],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\OpaqueRessource::analysisEnd', $opaque]
        );

        $fixture = [self::PARAM_DATA => imagecreatetruecolor(100, 100)];
        $opaque->setParameters($fixture);
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $opaque->callMe();

        $result = $renderNothing->model['renderExpandableChild'][0]->getParameters()[static::PARAM_DATA];

        // Getting a quick glance at the results.
        $this->assertEquals(100, $result['imagesx']);
        $this->assertEquals(100, $result['imagesx']);
        $this->assertEquals(true, $result['imageistruecolor']);
        $this->assertEquals(0, $result['imagecolorstotal']);
    }
}
