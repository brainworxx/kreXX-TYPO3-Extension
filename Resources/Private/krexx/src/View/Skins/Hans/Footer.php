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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Renders the footer with the configuration options.
 */
trait Footer
{
    /**
     * @var string[]
     */
    private $markerFooter = [
        '{configInfo}',
        '{caller}',
        '{pluginList}',
        '{plugins}',
    ];

    /**
     * @var string[]
     */
    private $markerCaller = [
        '{calledFromTxt}',
        '{calledFromLine}',
        '{calledFromAt}',
        '{calledFromUrl}',
        '{callerFile}',
        '{callerLine}',
        '{date}',
        '{callerUrl}',
    ];

     /**
     * {@inheritdoc}
     */
    public function renderFooter(array $caller, Model $model, bool $configOnly = false): string
    {
        if (isset($caller[static::TRACE_FILE]) === true) {
            $callerString = $this->renderCaller($caller);
        } else {
             // When we have no caller, we will not render it.
            $callerString = '';
        }

        return str_replace(
            $this->markerFooter,
            [
                $this->renderExpandableChild($model, $configOnly),
                $callerString,
                $this->pool->messages->getHelp('pluginList'),
                $this->renderPluginList(),
            ],
            $this->getTemplateFileContent(static::FILE_FOOTER)
        );
    }

    /**
     * Renders the footer part, where we display from where krexx was called.
     *
     * @param string[] $caller
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderCaller(array $caller): string
    {
        $messages = $this->pool->messages;

        return str_replace(
            $this->markerCaller,
            [
                $messages->getHelp('calledFromTxt'),
                $messages->getHelp('calledFromLine'),
                $messages->getHelp('calledFromAt'),
                $messages->getHelp('calledFromUrl'),
                $caller[static::TRACE_FILE],
                $caller[static::TRACE_LINE],
                $caller[static::TRACE_DATE],
                $caller[static::TRACE_URL],
            ],
            $this->getTemplateFileContent(static::FILE_CALLER)
        );
    }

    /**
     * Getter of the footer for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerFooter(): array
    {
        return $this->markerFooter;
    }

    /**
     * Getter of the caller for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerCaller(): array
    {
        return $this->markerCaller;
    }
}
