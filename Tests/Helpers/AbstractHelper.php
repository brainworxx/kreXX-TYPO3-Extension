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

namespace Brainworxx\Includekrexx\Tests\Helpers;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper as KrexxAbstractHelper;
use phpmock\phpunit\PHPMock;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\CacheService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

abstract class AbstractHelper extends KrexxAbstractHelper
{
    use PHPMock;

    const FINAL_CLASS_NAME_CACHE = 'finalClassNameCache';
    const SINGLETON_INSTANCES = 'singletonInstances';
    const TYPO3_VERSION = '1.2.3';

    /**
     * Make sure, that we always have a working pool.
     */
    protected function setUp(): void
    {
        // Reset the pool, just in case.
        Krexx::$pool = null;
        Pool::createPool();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        // Reset the possible mocks in the general utility.
        $this->setValueByReflection(static::FINAL_CLASS_NAME_CACHE, [], GeneralUtility::class);
        $this->setValueByReflection(static::SINGLETON_INSTANCES, [], GeneralUtility::class);

        if (class_exists(Environment::class)) {
            $this->setValueByReflection('cli', null, Environment::class);
        }

        // Reset the registered plugins.
        $this->setValueByReflection('logFolder', '', Registration::class);
        $this->setValueByReflection('chunkFolder', '', Registration::class);
        $this->setValueByReflection('configFile', '', Registration::class);
        $this->setValueByReflection('blacklistDebugMethods', [], Registration::class);
        $this->setValueByReflection('blacklistDebugClass', [], Registration::class);
        $this->setValueByReflection('additionalHelpFiles', [], Registration::class);
        $this->setValueByReflection('eventList', [], Registration::class);
        $this->setValueByReflection('rewriteList', [], Registration::class);
        $this->setValueByReflection('additionalSkinList', [], Registration::class);
        $this->setValueByReflection('plugins', [], Registration::class);
        $this->setValueByReflection('context', null, Environment::class);

        unset($GLOBALS['BE_USER']);
        GeneralUtility::flushInternalRuntimeCaches();
        GeneralUtility::purgeInstances();

        ConfigSupplier::$overwriteValues = [];

        Config::$disabledByPhp = false;
    }

    /**
     * Inject a mock into the general utility.
     *
     * @param $className
     * @param $mock
     */
    protected function injectIntoGeneralUtility($className, $mock)
    {
        $finalClassNameCache = $this->retrieveValueByReflection(static::FINAL_CLASS_NAME_CACHE, GeneralUtility::class);
        $finalClassNameCache[$className] = $className;
        $this->setValueByReflection(static::FINAL_CLASS_NAME_CACHE, $finalClassNameCache, GeneralUtility::class);

        $singletonInstances = $this->retrieveValueByReflection(static::SINGLETON_INSTANCES, GeneralUtility::class);
        $singletonInstances[$className] = $mock;
        $this->setValueByReflection(static::SINGLETON_INSTANCES, $singletonInstances, GeneralUtility::class);
    }

    /**
     * Mock the existence of a package and set some values in the package object.
     *
     * @param string $extensionKey
     * @param string $path
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     *   The package mock.
     */
    protected function simulatePackage($extensionKey, $path)
    {
        $packageManagerMock = $this->createMock(UnitTestPackageManager::class);
        $packageManagerMock->expects($this->any())
            ->method('isPackageActive')
            ->with($extensionKey)
            ->will($this->returnValue(true));
        $this->setValueByReflection('packageManager', $packageManagerMock, ExtensionManagementUtility::class);

        $packageMock = $this->createMock(Package::class);
        $packageMock->expects($this->any())
            ->method('getPackagePath')
            ->will($this->returnValue($path));

        $packageManagerMock->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($packageMock));

        return $packageMock;
    }

    /**
     * @var FlashMessageQueue
     */
    protected $flashMessageQueue;

    /**
     * Short circuiting the flash messages.
     *
     * @param $controller
     */
    protected function initFlashMessages($controller)
    {
        if (method_exists($controller, 'injectInternalExtensionService')) {
            // Doing this 11.0 style.
            // Doing this 11 and 12 style.
            if (method_exists($controller, 'injectObjectManager')) {
                // 11
                $this->flashMessageQueue = new FlashMessageQueueV11('identifyer');
            } else {
                // 12
                $this->flashMessageQueue = new FlashMessageQueueV12('identifyer');
            }

            $extensionServiceMock = $this->createMock(ExtensionService::class);
            $extensionServiceMock->expects($this->any())
                ->method('getPluginNamespace')
                ->will($this->returnValue('\\Brainworxx\\Includekrexx\\'));
            $controller->injectInternalExtensionService($extensionServiceMock);

            $requestMock = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Request::class);
            $requestMock->expects($this->any())
                ->method('getControllerExtensionName')
                ->will($this->returnValue('ControllerExtensionName'));
            $requestMock->expects($this->any())
                ->method('getPluginName')
                ->will($this->returnValue('PluginName'));
            $this->setValueByReflection('request', $requestMock, $controller);

            $flashMessageService = $this->createMock(FlashMessageService::class);
            $flashMessageService->expects($this->any())
                ->method('getMessageQueueByIdentifier')
                ->will($this->returnValue($this->flashMessageQueue));
            $controller->injectInternalFlashMessageService($flashMessageService);
        } else {
            // Doing this 8.7 till 10.4 style.
            $this->flashMessageQueue = new FlashMessageQueue();
            $controllerContextMock = $this->createMock(ControllerContext::class);
            $controllerContextMock->expects($this->any())
                ->method('getFlashMessageQueue')
                ->will($this->returnValue($this->flashMessageQueue));
            $this->setValueByReflection('controllerContext', $controllerContextMock, $controller);
        }
    }

    /**
     * Mock a backend user and inject it.
     */
    protected function mockBeUser()
    {
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->expects($this->any())
            ->method('check')
            ->with('modules', 'tools_IncludekrexxKrexxConfiguration')
            ->will($this->returnValue(true));

        $userMock->uc = [];
        $GLOBALS['BE_USER'] = $userMock;
    }

    /**
     * The things you do, to have a simple redirect . . .
     *
     * I once read that mocking is a code smell. If this is true, than this
     * method is a cesspool.
     *
     * @param $controller
     */
    protected function prepareRedirect($controller)
    {
        if (class_exists(ObjectManager::class)) {
            $cacheManagerMock = $this->createMock(CacheManager::class);
            $cacheManagerMock->expects($this->any())
                ->method('flushCachesInGroup')
                ->with('system');

            $cacheServiceMock = $this->createMock(CacheService::class);
            $cacheServiceMock->expects($this->any())
                ->method('clearCachesOfRegisteredPageIds');

            $objectManagerMock = $this->createMock(ObjectManager::class);
            $objectManagerMock->expects($this->any())
                ->method('get')
                ->willReturnMap([
                    [CacheManager::class, $cacheManagerMock],
                    [CacheService::class, $cacheServiceMock]
                ]);

            $this->setValueByReflection('objectManager', $objectManagerMock, $controller);
        }

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getControllerName')
            ->will($this->returnValue('meier'));
        $this->setValueByReflection('request', $request, $controller);

        $uriBuilder = $this->createMock(UriBuilder::class);
        $uriBuilder->expects($this->any())
            ->method('reset')
            ->will($this->returnValue($uriBuilder));
        $uriBuilder->expects($this->any())
            ->method('setCreateAbsoluteUri')
            ->will($this->returnValue($uriBuilder));
        $uriBuilder->expects($this->any())
            ->method('setTargetPageUid')
            ->will($this->returnValue($uriBuilder));
        $uriBuilder->expects($this->any())
            ->method('setAbsoluteUriScheme')
            ->will($this->returnValue($uriBuilder));
        $uriBuilder->expects($this->any())
            ->method('uriFor')
            ->will($this->returnValue('https:\\\\google.de'));
        $this->setValueByReflection('uriBuilder', $uriBuilder, $controller);

        if (class_exists(Response::class) === true) {
            $response = $this->createMock(Response::class);
            $response->expects($this->any())->method('setContent');
            if (method_exists(Response::class, 'setStatus')) {
                $response->expects($this->any())->method('setStatus');
            }
            if (method_exists(Response::class, 'setHeader')) {
                $response->expects($this->any())->method('setHeader');
            }
            $this->setValueByReflection('response', $response, $controller);
        }

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() < 11) {
            $contentObject = $this->createMock(ContentObjectRenderer::class);
            $contentObject->expects($this->any())
                ->method('getUserObjectType')
                ->will($this->returnValue(''));
            $configurationManager = $this->createMock(ConfigurationManager::class);
            $configurationManager->expects($this->any())
                ->method('getContentObject')
                ->will($this->returnValue($contentObject));
            $this->setValueByReflection('configurationManager', $configurationManager, $controller);
        }
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called between test and tearDown().
     */
    protected function krexxertPostConditions()
    {
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called between setUp() and test.
     */
    protected function krexxertPreConditions()
    {
    }
}
