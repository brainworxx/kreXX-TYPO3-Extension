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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Collectors\FormConfiguration;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Hosting all those ugly workarounds to keep this extension compatible back to
 * 13.4. This  makes the other controllers (hopefully) more readable.
 */
abstract class AbstractController extends ActionController implements ConstInterface, ControllerConstInterface
{
    use LanguageTrait;
    use AccessTrait;

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
    protected Pool $pool;

    /**
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Set the pool and do the parent constructor.
     */
    public function __construct(
        protected Configuration $configuration,
        protected FormConfiguration $formConfiguration,
        protected Settings $settingsModel,
        protected PageRenderer $pageRenderer
    ) {
        Pool::createPool();
        $this->pool = Krexx::$pool;
    }

    /**
     * Trying to get the ModuleTemplate from TYPO3 10 to 13.
     *
     * @return void
     */
    public function initializeAction(): void
    {
        parent::initializeAction();
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplateFactory::class)
            ->create($this->request);
    }

    /**
     * We check if we are running with a productive preset. If we do, we
     * will display a warning.
     */
    protected function checkProductiveSetting(): void
    {
        if (Environment::getContext()->isProduction()) {
            // Display a warning, if we are in Productive / Live settings.
            $this->addFlashMessage(
                static::translate('debugpreset.warning.message'),
                static::translate('debugpreset.warning.title'),
                ContextualFeedbackSeverity::WARNING
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
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * Dispatches a file, using output buffering.
     *
     * We can not (ab)use the TYPO3 for dispatching, because we have inline
     * JS in that file.
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
        $this->pageRenderer->loadJavaScriptModule('@brainworxx/includekrexx/Index.js');
        $this->pageRenderer->addJsInlineCode(
            'krexxajaxtrans',
            $this->generateAjaxTranslations(),
            false,
            false,
            true
        );

        $cssPath = GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Private/Css/Index.css');
        $this->pageRenderer->addCssInlineBlock('krexxBeCss', file_get_contents($cssPath), false, false, true);
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
        $translation = [
            'deletefile' => static::translate('ajax.delete.file'),
            'error' => static::translate('ajax.error'),
            'in' => static::translate('ajax.in'),
            'line' => static::translate('ajax.line'),
            'updatedLoglist' => static::translate('ajax.updated.loglist'),
            'deletedCookies' => static::translate('ajax.deleted.cookies'),
        ];

        return 'window.ajaxTranslate = ' .  json_encode($translation) . ';';
    }

    /**
     * Compatibility wrapper around the rendering of the module template.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function moduleTemplateRender(): ResponseInterface
    {
        $this->assignCssJs();
        $this->assignMultiple(['compatibilityClasses' => 'module-body t3js-module-body']);
        $this->configuration->assignData($this->moduleTemplate);
        $this->formConfiguration->assignData($this->moduleTemplate);
        return $this->moduleTemplate->renderResponse('Index/Index');
    }

    /**
     * Compatibility wrapper around the assignment of multiple values.
     *
     * @param array $values
     */
    protected function assignMultiple(array $values): void
    {
        $this->moduleTemplate->assignMultiple($values);
    }
}
