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

namespace Brainworxx\Includekrexx\Tests\Unit\Controller;

use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Includekrexx\Tests\Helpers\ModuleTemplate;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;
use TYPO3\CMS\Extbase\Mvc\Response;

class IndexControllerTest extends AbstractHelper
{
    const NO_MORE_MESSAGES = 'No more messages here.';
    const CONTROLLER_NAMESPACE = '\\Brainworxx\\Includekrexx\\Controller\\';
    const REDIRECT_MESSAGE = 'We did have an redirect here.';

    protected $indexController;

    /**
     * Creating a new controller instance.
     */
    public function setUp(): void
    {
        parent::setUp();

        $configMock = $this->createMock(Configuration::class);
        $formConfigMock = $this->createMock(FormConfiguration::class);
        $settings = $this->createMock(Settings::class);
        $pageRenderer = $this->createMock(PageRenderer::class);
        $typo3Version = new Typo3Version();

        $this->indexController = new IndexController($configMock, $formConfigMock, $settings, $pageRenderer, $typo3Version);
    }

    /**
     * Test the index action, without access.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::indexAction
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::hasAccess
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::moduleTemplateRender
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::assignMultiple
     */
    public function testIndexActionNoAccess()
    {
        $this->initFlashMessages($this->indexController);
        if (method_exists($this->indexController, 'injectResponseFactory')) {
            $this->indexController->injectResponseFactory(new ResponseFactory());
        }
        $this->indexController->indexAction();

        $this->assertEquals(
            'accessDenied',
            $this->flashMessageQueue->getMessages()[0]->getMessage(),
            'We did not mock a BE session, hence no access for you!'
        );
        $this->assertArrayNotHasKey(1, $this->flashMessageQueue->getMessages(), static::NO_MORE_MESSAGES);
    }

    /**
     * Normal test of the index action.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::indexAction
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::hasAccess
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::checkProductiveSetting
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::retrieveKrexxMessages
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::assignCssJs
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::generateAjaxTranslations
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::moduleTemplateRender
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::assignMultiple
     */
    public function testIndexActionNormal()
    {
        $jsCssFileContent = 'file content';
        $templateContent = 'template content';
        $translationContent = 'window.ajaxTranslate = {"deletefile":"ajax.delete.file","error":"ajax.error","in":"ajax.in","line":"ajax.line","updatedLoglist":"ajax.updated.loglist","deletedCookies":"ajax.deleted.cookies"};';
        $typo3Version = new Typo3Version();

        $fileGetContents =  $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'file_get_contents');
        $fileGetContents->expects($this->any())
            ->will($this->returnValue($jsCssFileContent));

        // Prepare a BE user.
        $this->mockBeUser();

        // Prepare a productive setting.
        $presetMock = $this->createMock(LivePreset::class);
        $presetMock->expects($this->once())
            ->method('isActive')
            ->will($this->returnValue(true));

        // Prepare a message from kreXX.
        $messageFromKrexx = 'some key';
        Krexx::$pool->messages->addMessage($messageFromKrexx);

        // Prepare the model
        $settingsModel = new Settings();

        // Mock the view.
        $viewMock = $this->createMock(AbstractTemplateView::class);
        if ($typo3Version->getMajorVersion() < 11) {
            $viewMock->expects($this->exactly(1))
                ->method('assignMultiple')
                ->with(['settings', $settingsModel]);
            $viewMock->expects($this->once())
                ->method('render')
                ->will($this->returnValue($templateContent));
        }

        $moduleTemplateMock = $this->createMock(ModuleTemplate::class);
        $moduleTemplateMock->expects($this->once())
            ->method('setModuleName')
            ->with('tx_includekrexx');

        if ($typo3Version->getMajorVersion() > 11) {
            $moduleTemplateMock->expects($this->exactly(2))
                ->method('assignMultiple');
        }
        if ($typo3Version->getMajorVersion() < 11) {
            $moduleTemplateMock->expects($this->once())
                ->method('setContent')
                ->with($templateContent);
            $moduleTemplateMock->expects($this->once())
                ->method('renderContent')
                ->will($this->returnValue($templateContent));
        }

        // Prepare the collectors
        $configurationMock = $this->createMock(Configuration::class);
        $configFeMock = $this->createMock(FormConfiguration::class);
        if ($typo3Version->getMajorVersion() > 11) {
            $configurationMock->expects($this->once())
                ->method('assignData')
                ->with($moduleTemplateMock);
            $configFeMock->expects($this->once())
                ->method('assignData')
                ->with($moduleTemplateMock);
        } else {
            $configurationMock->expects($this->once())
                ->method('assignData')
                ->with($viewMock);
            $configFeMock->expects($this->once())
                ->method('assignData')
                ->with($viewMock);
        }

        $pageRenderer = $this->createMock(PageRenderer::class);

        if (method_exists(PageRenderer::class, 'loadJavaScriptModule')) {
            $pageRenderer->expects($this->any())
                ->method('addJsInlineCode')
                ->with(...$this->withConsecutive(['krexxajaxtrans', $translationContent, false, false, true]));
        } else {
            $pageRenderer->expects($this->any())
                ->method('addJsInlineCode')
                ->with(...$this->withConsecutive(
                    ['krexxjs', $jsCssFileContent],
                    ['krexxajaxtrans', $translationContent]
                ));
        }

        $pageRenderer->expects($this->once())
            ->method('addCssInlineBlock')
            ->with('krexxcss', $jsCssFileContent);

        // Inject it, like there is no tomorrow.
        $this->indexController = new IndexController($configurationMock, $configFeMock, $settingsModel, $pageRenderer, $typo3Version);
        $this->indexController->injectLivePreset($presetMock);
        $this->setValueByReflection('moduleTemplate', $moduleTemplateMock, $this->indexController);

        if (method_exists($this->indexController, 'injectResponseFactory')) {
            $this->indexController->injectResponseFactory(new ResponseFactory());
        }
        $this->initFlashMessages($this->indexController);
        $this->setValueByReflection('view', $viewMock, $this->indexController);

        // Run it through like a tunnel on a marathon route.
        $this->simulatePackage('includekrexx', 'includekrexx/');
        $this->indexController->indexAction();

        // Test for the kreXX messages.
        $this->assertEquals(
            'debugpreset.warning.message',
            $this->flashMessageQueue->getMessages()[0]->getMessage(),
            'Simulation productive settings.'
        );
        $this->assertEquals(
            $messageFromKrexx,
            $this->flashMessageQueue->getMessages()[1]->getMessage(),
            'A message from kreXX'
        );
        $this->assertArrayNotHasKey(2, $this->flashMessageQueue->getMessages(), static::NO_MORE_MESSAGES);
    }

    /**
     * Test the redirect when having no access for the save action.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::saveAction
     */
    public function testSaveActionNoAccess()
    {
        $this->initFlashMessages($this->indexController);
        $this->prepareRedirect($this->indexController);

        $settingsModel = new Settings();

        try {
            $exceptionWasThrown = !empty($this->indexController->saveAction($settingsModel));
        } catch (UnsupportedRequestTypeException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        } catch (StopActionException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        }
        $this->assertTrue($exceptionWasThrown, static::REDIRECT_MESSAGE);

        $this->assertEquals(
            'accessDenied',
            $this->flashMessageQueue->getMessages()[0]->getMessage(),
            'We did not mock a BE session, hence no access for you!'
        );
        $this->assertArrayNotHasKey(1, $this->flashMessageQueue->getMessages(), static::NO_MORE_MESSAGES);
    }

    /**
     * Testing the saving of the ini file.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::saveAction
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::retrieveKrexxMessages
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::prepareFileName
     */
    public function testSaveActionNormal()
    {
        $this->mockBeUser();

        $this->initFlashMessages($this->indexController);
        $this->prepareRedirect($this->indexController);

        $iniContent = 'oh joy, even more settings . . .';
        $pathParts = pathinfo(Krexx::$pool->config->getPathToConfigFile());
        $configFilePath = $pathParts['dirname'] . DIRECTORY_SEPARATOR . $pathParts['filename'] . '.json';

        $settingsMock = $this->createMock(Settings::class);
        $settingsMock->expects($this->once())
            ->method('generateContent')
            ->will($this->returnValue($iniContent));
        $settingsMock->expects($this->once())
            ->method('prepareFileName')
            ->will($this->returnValue($configFilePath));



        $filePutContentsMock = $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'file_put_contents');
        $filePutContentsMock->expects($this->once())
            ->with($configFilePath, $iniContent)
            ->will($this->returnValue(true));

        try {
            $exceptionWasThrown = !empty($this->indexController->saveAction($settingsMock));
        } catch (UnsupportedRequestTypeException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        } catch (StopActionException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        }
        $this->assertTrue($exceptionWasThrown, static::REDIRECT_MESSAGE);

        $this->assertEquals(
            'save.success.text',
            $this->flashMessageQueue->getMessages()[0]->getMessage(),
            'Expecting the success message here.'
        );
        $this->assertArrayNotHasKey(1, $this->flashMessageQueue->getMessages(), static::NO_MORE_MESSAGES);
    }

    /**
     * Testing the saving of the ini file.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::saveAction
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::retrieveKrexxMessages
     * @covers \Brainworxx\Includekrexx\Domain\Model\Settings::prepareFileName
     */
    public function testSaveActionNoWriteAccess()
    {
        $this->mockBeUser();

        $this->initFlashMessages($this->indexController);
        $this->prepareRedirect($this->indexController);

        $iniContent = 'oh joy, even more settings . . .';
        $pathParts = pathinfo(Krexx::$pool->config->getPathToConfigFile());
        $configFilePath = $pathParts['dirname'] . DIRECTORY_SEPARATOR . $pathParts['filename'] . '.json';

        $settingsMock = $this->createMock(Settings::class);
        $settingsMock->expects($this->once())
            ->method('generateContent')
            ->will($this->returnValue($iniContent));
        $settingsMock->expects($this->once())
            ->method('prepareFileName')
            ->will($this->returnValue($configFilePath));

        $filePutContentsMock = $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'file_put_contents');
        $filePutContentsMock->expects($this->once())
            ->with(Krexx::$pool->config->getPathToConfigFile(), $iniContent)
            ->will($this->returnValue(false));

        try {
            $exceptionWasThrown = !empty($this->indexController->saveAction($settingsMock));
        } catch (UnsupportedRequestTypeException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        } catch (StopActionException $e) {
            // We expect this one.
            $exceptionWasThrown = true;
        }

        $this->assertTrue($exceptionWasThrown, static::REDIRECT_MESSAGE);

        $this->assertEquals(
            'file.not.writable',
            $this->flashMessageQueue->getMessages()[0]->getMessage(),
            'Expecting the failure message here.'
        );
        $this->assertArrayNotHasKey(1, $this->flashMessageQueue->getMessages(), static::NO_MORE_MESSAGES);
    }

    /**
     * Testing the dispatching without access.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::dispatchAction
     */
    public function testDispatchActionNoAccess()
    {
        $serverRequestMock = $this->createMock(ServerRequest::class);
        // Never, because we have no access.
        $serverRequestMock->expects($this->never())
            ->method('getQueryParams');

        $headerMock = $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'header');
        $headerMock->expects($this->never());

        $this->indexController->dispatchAction($serverRequestMock);
    }

    /**
     * Testing the normal dispatching of a file.
     *
     * @covers \Brainworxx\Includekrexx\Controller\IndexController::dispatchAction
     * @covers \Brainworxx\Includekrexx\Controller\AbstractController::dispatchFile
     */
    public function testDispatchActionNormal()
    {
        $this->mockBeUser();

        // Use the files inside the fixture folder.
        $this->setValueByReflection(
            'directories',
            ['log' => __DIR__ . '/../../Fixtures/'],
            \Krexx::$pool->config
        );


        $this->expectOutputString('Et dico vide nec, sed in mazim phaedrum voluptatibus. Eum clita meliore tincidunt ei, sed utinam pertinax theophrastus ad. Porro quodsi detracto ea pri. Et vis mollis voluptaria. Per ut saperet intellegam.');

        // Prevent the dispatcher from doing something stupid.
        $headerMock = $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'header');
        $headerMock->expects($this->exactly(2));
        $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'ob_flush');
        $this->getFunctionMock(static::CONTROLLER_NAMESPACE, 'flush');

        $serverRequestMock = $this->createMock(ServerRequest::class);
        $request = [
            'tx_includekrexx_tools_includekrexxkrexxconfiguration' => ['id' => 123458]
        ];
        $serverRequestMock->expects($this->once())
            ->method('getQueryParams')
            ->will($this->returnValue($request));

        $this->indexController->dispatchAction($serverRequestMock);
    }
}
