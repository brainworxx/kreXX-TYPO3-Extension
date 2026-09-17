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
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Hosting all the none-actions for the backend module, making the
 * IndexController a bit more readable.
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
        protected PageRenderer $pageRenderer,
        protected IconFactory $iconFactory,
    ) {
        Pool::createPool();
        $this->pool = Krexx::$pool;
    }

    /**
     * Initialize the action, create the module template and add the controls.
     *
     * @return void
     */
    public function initializeAction(): void
    {
        parent::initializeAction();
        // The only reason why we do not inject the ModuleTemplateFactory is,
        // because it is final and can not be mocked in the unit tests. So we create it here.
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplateFactory::class)
            ->create(request: $this->request);
        $this->addControls();
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
                messageBody: static::translate(key:'debugpreset.warning.message'),
                messageTitle: static::translate(key: 'debugpreset.warning.title'),
                severity: ContextualFeedbackSeverity::WARNING
            );
        }
    }

    /**
     * Move all messages from kreXX to the flash messages.
     */
    protected function retrieveKrexxMessages(): void
    {
        // Get the keys and the args.
        foreach ($this->pool->messages->getMessages() as $message) {
            // And translate them.
            $this->addFlashMessage(
                messageBody: static::translate(
                    key: $message->getKey(),
                    arguments: $message->getArguments()
                ) ?? $message->getText(),
                messageTitle: static::translate('general.error.title'),
                severity: ContextualFeedbackSeverity::ERROR
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
        if (is_readable(filename: $path)) {
            header(header: 'Content-Type: text/html; charset=utf-8');
            header(header: 'Content-length: ' . filesize(filename: $path));

            $size = 1024 * 1024;
            $res = fopen(filename: $path, mode: "rb");
            while (!feof(stream: $res)) {
                echo fread(stream: $res, length: $size);
                ob_flush();
                flush();
            }
            fclose(stream: $res);
        }
    }

    /**
     * Add the buttons to the button bar.
     */
    protected function addControls(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        /** @var GenericButton $button */
        $saveButton = GeneralUtility::makeInstance(GenericButton::class);
        $saveTitle = static::translate('save');
        $saveButton->setLabel(label: $saveTitle)
            ->setTitle(title: $saveTitle)
            ->setShowLabelText(showLabelText: true)
            ->setAttributes(attributes:['form' => 'save-form'])
            ->setIcon(icon: $this->iconFactory->getIcon(identifier: 'actions-save', size: IconSize::SMALL));
        $buttonBar->addButton(button: $saveButton);

        /** @var DropDownButton $dropdown */
        $dropdown = GeneralUtility::makeInstance(DropDownButton::class);
        $dropdown->setShowLabelText(showLabelText: true)
            ->setLabel(label: static::translate(key: 'simple'));

        /** @var DropDownToggle $simpleItem */
        $simpleItem = GeneralUtility::makeInstance(DropDownToggle::class);
        $simpleItem->setAttribute(name: 'data-mode', value: 'simple')
            ->setActive(active: true)
            ->setLabel(label: static::translate(key: 'simple'));

        /** @var DropDownToggle $expertItem */
        $expertItem = GeneralUtility::makeInstance(DropDownToggle::class);
        $expertItem->setAttribute(name: 'data-mode', value: 'expert')
            ->setActive(active: false)
            ->setLabel(label: static::translate(key: 'expert'));
        $dropdown->addItem(item: $simpleItem)->addItem(item: $expertItem);
        $buttonBar->addButton(button: $dropdown);

        $cookieButton = GeneralUtility::makeInstance(GenericButton::class);
        $cookieText = static::translate('clear.cookies');
        $cookieButton->setLabel(label: $cookieText)
            ->setTitle(title: $cookieText)
            ->setShowLabelText(showLabelText: true)
            ->setIcon(icon: $this->iconFactory->getIcon(identifier: 'actions-cookie', size: IconSize::SMALL))
            ->setClasses(classes: 'cookies');
        $buttonBar->addButton(button: $cookieButton);
    }

    /**
     * Compatibility wrapper around the rendering of the module template.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function moduleTemplateRender(): ResponseInterface
    {
        $this->pageRenderer->addInlineLanguageLabelArray(array: [
            'deletefile' => static::translate(key: 'ajax.delete.file'),
            'in' => static::translate(key: 'ajax.in'),
            'line' => static::translate(key: 'ajax.line'),
            'updatedLoglist' => static::translate(key: 'ajax.updated.loglist'),
            'deletedCookies' => static::translate(key: 'ajax.deleted.cookies'),
            'ajaxSuccess' => static::translate(key: 'ajax.success'),
            'ajaxError' => static::translate(key: 'ajax.error'),
            'parsingError' => static::translate(key: 'ajax.parsing.error'),
            'warning' => static::translate(key: 'ajax.warning'),
            'yes' => static::translate(key: 'ajax.yes'),
            'no' => static::translate(key: 'ajax.no'),
        ]);

        $this->configuration->assignData(view: $this->moduleTemplate);
        $this->formConfiguration->assignData(view: $this->moduleTemplate);
        return $this->moduleTemplate->renderResponse(templateFileName: 'Index/Index');
    }
}
