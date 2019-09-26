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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use phpmock\phpunit\PHPMock;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\UnitTestPackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\AbstractController;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use StdClass;

abstract class AbstractTest extends UnitTestCase
{
    use PHPMock;

    /**
     * Make sure, that we always havbe a working pool.
     */
    public function setUp()
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        Pool::createPool();
    }


    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->setValueByReflection('packageManager', null, ExtensionManagementUtility::class);
        // Reset the possible mocks in the general utility.
        $this->setValueByReflection('finalClassNameCache', [], GeneralUtility::class);
        $this->setValueByReflection('singletonInstances', [], GeneralUtility::class);

        $this->setValueByReflection('cli', null, Environment::class);

        // Reset the pool, just in case.
        Krexx::$pool = null;
    }

    /**
     * Inject a mock into the general utility.
     *
     * @param $className
     * @param $mock
     */
    protected function injectIntoGeneralUtility($className, $mock)
    {
        $finalClassNameCache = $this->retrieveValueByReflection('finalClassNameCache', GeneralUtility::class);
        $finalClassNameCache[$className] = $className;
        $this->setValueByReflection('finalClassNameCache', $finalClassNameCache, GeneralUtility::class);

        $singletonInstances = $this->retrieveValueByReflection('singletonInstances', GeneralUtility::class);
        $singletonInstances[$className] = $mock;
        $this->setValueByReflection('singletonInstances', $singletonInstances, GeneralUtility::class);
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
     * Setting a protected value in the class we are testing.
     *
     * @param string $name
     *   The name of the value.
     * @param mixed $value
     *   The value we want to set.
     * @param object|string $object
     *   The instance where we want to set the value. Or the class name, when
     *   setting static values.
     */
    protected function setValueByReflection($name, $value, $object)
    {
        try {
            $reflectionClass = new \ReflectionClass($object);
            $reflectionProperty = $reflectionClass->getProperty($name);
            $reflectionProperty->setAccessible(true);
            if (is_object($object)) {
                $reflectionProperty->setValue($object, $value);
            } else {
                $reflectionProperty->setValue($value);
            }
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Getting a protected/private value by reflection.
     *
     * @param string $name
     *   The name of the property.
     * @param object|string $object
     *   The instance from where we want to get the value. Or the class name,
     *   when getting static values.
     *
     * @return mixed
     *   The value.
     */
    protected function retrieveValueByReflection($name, $object)
    {
        try {
            $reflectionClass = new \ReflectionClass($object);
            $reflectionProperty = $reflectionClass->getProperty($name);
            $reflectionProperty->setAccessible(true);
            if (is_object($object)) {
                return $reflectionProperty->getValue($object);
            } else {
                return $reflectionProperty->getValue();
            }
        } catch (\ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        return null;
    }

    /**
     * @var FlashMessageQueue
     */
    protected $flashMessageQueue;

    /**
     * Short circuiting the flash messages.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController $controller
     */
    protected function initFlashMessages(AbstractController $controller)
    {
        $this->flashMessageQueue = new FlashMessageQueue();

        $controllerContextMock = $this->createMock(ControllerContext::class);
        $controllerContextMock->expects($this->any())
            ->method('getFlashMessageQueue')
            ->will($this->returnValue($this->flashMessageQueue));
        $this->setValueByReflection('controllerContext', $controllerContextMock, $controller);
    }

    /**
     * Mock a backend user and inject it.
     */
    protected function mockBeUser()
    {
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->expects($this->any())
            ->method('check')
            ->with('modules', AbstractCollector::PLUGIN_NAME)
            ->will($this->returnValue(true));

        $userMock->uc = [];
        $GLOBALS['BE_USER'] = $userMock;
    }

    /**
     * The tings you do, to have a simple redirect . . .
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController $controller
     */
    protected function prepareRedirect(AbstractController $controller)
    {
        $request = new StdClass();
        $this->setValueByReflection('request', $request, $controller);
    }
}
