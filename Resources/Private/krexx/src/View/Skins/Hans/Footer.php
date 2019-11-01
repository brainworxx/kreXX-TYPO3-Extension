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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

trait Footer
{
     /**
     * {@inheritdoc}
     */
    public function renderFooter(array $caller, Model $model, $configOnly = false)
    {
        if (isset($caller[static::TRACE_FILE]) === true) {
            $callerString = $this->renderCaller($caller);
        } else {
             // When we have no caller, we will not render it.
            $callerString = '';
        }

        return str_replace(
            [
                static::MARKER_CONFIG_INFO,
                static::MARKER_CALLER,
                static::MARKER_PLUGINS,
            ],
            [
                $this->renderExpandableChild($model, $configOnly),
                $callerString,
                $this->renderPluginList(),
            ],
            $this->getTemplateFileContent(static::FILE_FOOTER)
        );
    }

    /**
     * Renders the footer part, where we display from where krexx was called.
     *
     * @param array $caller
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderCaller(array $caller)
    {
        return str_replace(
            [
                static::MARKER_CALLER_FILE,
                static::MARKER_CALLER_LINE,
                static::MARKER_CALLER_DATE,
                static::MARKER_CALLER_URL,
            ],
            [
                $caller[static::TRACE_FILE],
                $caller[static::TRACE_LINE],
                $caller[static::TRACE_DATE],
                $caller[static::TRACE_URL],
            ],
            $this->getTemplateFileContent(static::FILE_CALLER)
        );
    }
}
