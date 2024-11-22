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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use stdClass;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Response as MvcResponse;
use TYPO3\CMS\Install\Configuration\Context\LivePreset;

/**
 * Hosting all those ugly workarounds to keep this extension compatible back to
 * 10.4. This  makes the other controllers (hopefully) more readable.
 */
abstract class AbstractController extends ActionController implements ConstInterface, ControllerConstInterface
{
    use LanguageTrait;

    /**
     * @var string
     */
    protected const SAVE_FAIL_TITLE = 'save.fail.title';

    /**
     * @var string
     */
    protected const SAVE_SUCCESS_TEXT = 'save.success.text';

    /**
     * @var string
     */
    protected const SAVE_SUCCESS_TITLE = 'save.success.title';

    /**
     * @var string
     */
    protected const FILE_NOT_WRITABLE = 'file.not.writable';

    /**
     * The kreXX framework.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

     /**
     * @var \Brainworxx\Includekrexx\Collectors\Configuration
     */
    protected $configuration;

    /**
     * @var \Brainworxx\Includekrexx\Collectors\FormConfiguration
     */
    protected $formConfiguration;

    /**
     * @var \Brainworxx\Includekrexx\Domain\Model\Settings
     */
    protected $settingsModel;

    /**
     * @var LivePreset
     */
    protected $livePreset;

    /**
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var int|\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity
     */
    protected $flashMessageWarning;

    /**
     * @var int|\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity
     */
    protected $flashMessageError;

    /**
     * @var int|\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity
     */
    protected $flashMessageOk;

    /**
     * The TYPO3 major version.
     *
     * @var int
     */
    protected $typo3Version;


    /**
     * Set the pool and do the parent constructor.
     */
    public function __construct(
        Configuration $configuration,
        FormConfiguration $formConfiguration,
        Settings $settings,
        PageRenderer $pageRenderer,
        Typo3Version $typo3Version
    ) {
        $this->configuration = $configuration;
        $this->formConfiguration = $formConfiguration;
        $this->settingsModel = $settings;
        $this->pageRenderer = $pageRenderer;
        $this->typo3Version = $typo3Version->getMajorVersion();

        Pool::createPool();
        $this->pool = Krexx::$pool;

        if ($this->typo3Version > 11) {
            $this->flashMessageError = ContextualFeedbackSeverity::ERROR;
            $this->flashMessageOk = ContextualFeedbackSeverity::OK;
            $this->flashMessageWarning = ContextualFeedbackSeverity::WARNING;
        } else {
            $this->flashMessageError = AbstractMessage::ERROR;
            $this->flashMessageOk = AbstractMessage::OK;
            $this->flashMessageWarning = AbstractMessage::WARNING;
        }
    }

    /**
     * Trying to get the ModuleTemplate from TYPO3 7 to 12.
     *
     * @return void
     */
    public function initializeAction(): void
    {
        parent::initializeAction();

        if ($this->typo3Version > 11) {
            // 12'er style
            $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplateFactory::class)
                ->create($this->request);
        } else {
            // 11'er style
            $this->moduleTemplate = $this->objectManager->get(ModuleTemplate::class);
        }
    }

    /**
     * Inject the private LivePreset.
     *
     * @param \TYPO3\CMS\Install\Configuration\Context\LivePreset $livePreset
     */
    public function injectLivePreset(LivePreset $livePreset): void
    {
        $this->livePreset = $livePreset;
    }

    /**
     * We check if we are running with a productive preset. If we do, we
     * will display a warning.
     */
    protected function checkProductiveSetting(): void
    {
        if ($this->livePreset->isActive()) {
            //Display a warning, if we are in Productive / Live settings.
            $this->addFlashMessage(
                static::translate('debugpreset.warning.message'),
                static::translate('debugpreset.warning.title'),
                $this->flashMessageWarning
            );
        }
    }

    /**
     * Move all messages from kreXX to the flash messages.
     */
    protected function retrieveKrexxMessages(): void
    {
        // Get the keys and the args.
        $messages = $this->pool->messages->getMessages();

        foreach ($messages as $message) {
            // And translate them.
            $this->addFlashMessage(
                static::translate($message->getKey(), $message->getArguments()) ?? $message->getText(),
                static::translate('general.error.title'),
                $this->flashMessageError
            );
        }
    }

    /**
     * Dispatches a file, using output buffering.
     *
     * @param string $path
     *   The path of the file we want to dispatch to the browser.
     */
    protected function dispatchFile(string $path): void
    {
        if (is_readable($path)) {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-length: ' . filesize($path));

            $size = 1024 * 1024;
            $res = fopen($path, "rb");
            while (!feof($res)) {
                echo fread($res, $size);
                ob_flush();
                flush();
            }
            fclose($res);
        }
    }

    /**
     * Additional check, if the current Backend user has access to the extension.
     *
     * @return bool
     *   The result of the check.
     */
    protected function hasAccess(): bool
    {
        return isset($GLOBALS[static::BE_USER]) &&
            $GLOBALS[static::BE_USER]->check(static::BE_MODULES, AbstractCollector::PLUGIN_NAME);
    }

    /**
     * Create the response, depending on the calling context.
     *
     * @deprecated
     *   Since 5.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated functions.
     *
     * @return MvcResponse|NullResponse
     */
    protected function createResponse()
    {
        if (class_exists(NullResponse::class)) {
            return GeneralUtility::makeInstance(NullResponse::class);
        }

        return GeneralUtility::makeInstance(MvcResponse::class);
    }

    /**
     * With 10.0, the backend container became somewhat unable to handle css and
     * js files. The path needs to be different, depending on the debug settings.
     *
     * @example
     *   /typo3_100/typo3conf/ext/includekrexx/Resources/Public/Css/Index.css
     *   --> with debug settings
     *   /typo3conf/ext/includekrexx/Resources/Public/Css/Index.css
     *   --> with productive settings.
     *
     * Imho the best way to deal with this is to (again) assign the css and js
     * inline.
     */
    protected function assignCssJs(): void
    {
        if (method_exists($this->pageRenderer, 'loadJavaScriptModule')) {
            // Doing this the TYPO3 12 way.
            $this->pageRenderer->loadJavaScriptModule('@brainworxx/includekrexx/Index.js');
            $this->pageRenderer->addJsInlineCode(
                'krexxajaxtrans',
                $this->generateAjaxTranslations(),
                false,
                false,
                true
            );
        } else {
            // @deprecated
            // Will be removed as soon as we drop TYPO3 11 support.
            $jsPath = GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Public/JavaScript/Index.js');
            $this->pageRenderer->addJsInlineCode('krexxjs', file_get_contents($jsPath));
            $this->pageRenderer->addJsInlineCode('krexxajaxtrans', $this->generateAjaxTranslations());
        }

        $cssPath = GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Private/Css/Index.css');
        $this->pageRenderer->addCssInlineBlock('krexxcss', file_get_contents($cssPath));
        $this->moduleTemplate->setModuleName('tx_includekrexx');
    }

    /**
     * Generate the translation JS object manually in PHP, because I do not
     * trust Fluid enough to do this across all versions from 7.6 to 11.5.
     *
     * @return string
     *   The generated javascript variable with the translations.
     */
    protected function generateAjaxTranslations(): string
    {
        $translation = new stdClass();
        $translation->deletefile = static::translate('ajax.delete.file');
        $translation->error = static::translate('ajax.error');
        $translation->in = static::translate('ajax.in');
        $translation->line = static::translate('ajax.line');
        $translation->updatedLoglist = static::translate('ajax.updated.loglist');
        $translation->deletedCookies = static::translate('ajax.deleted.cookies');

        return 'window.ajaxTranslate = ' .  json_encode($translation) . ';';
    }

    /**
     * Compatibility wrapper around the rendering of the module template.
     *
     * @return \Psr\Http\Message\ResponseInterface|string
     */
    protected function moduleTemplateRender()
    {
        $this->assignCssJs();

        if ($this->typo3Version > 11) {
            // @deprecated
            // Hardcode these as soon as we drop TYPO3 10 compatibility.
            $this->assignMultiple(['compatibilityClasses' => 'module-body t3js-module-body']);

            // 12'er style.
            $this->configuration->assignData($this->moduleTemplate);
            $this->formConfiguration->assignData($this->moduleTemplate);
            return $this->moduleTemplate->renderResponse();
        }

        // 10'er and 11'er style.
        $this->configuration->assignData($this->view);
        $this->formConfiguration->assignData($this->view);
        $this->moduleTemplate->setContent($this->view->render());

        if ($this->typo3Version > 10) {
            // 11'er style.
            return GeneralUtility::makeInstance(
                HtmlResponse::class,
                $this->moduleTemplate->renderContent()
            );
        }

        // 10'er style.
        // We let the view render the template.
        return $this->moduleTemplate->renderContent();
    }

    /**
     * Compatibility wrapper around the assignment of multiple values.
     *
     * @param array $values
     */
    protected function assignMultiple(array $values): void
    {
        if ($this->typo3Version > 11) {
            $this->moduleTemplate->assignMultiple($values);
            return;
        }

        $this->view->assignMultiple($values);
    }
}
