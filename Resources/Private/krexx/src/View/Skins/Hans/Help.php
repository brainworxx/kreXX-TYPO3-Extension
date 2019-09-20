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

trait Help
{
     /**
     * Renders the helptext.
     *
     * @param Model $model
     *   The ID of the helptext.
     *
     * @see Usage
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderHelp(Model $model)
    {
        $data = $model->getJson();

        // Test if we have anything to display at all.
        if (empty($data) === true) {
            return '';
        }

        // We have at least something to display here.
        $helpRow = $this->getTemplateFileContent(static::FILE_HELPROW);
        $helpContent = '';

        // Add the stuff from the json after the help text, if any.
        foreach ($data as $title => $text) {
            $helpContent .= str_replace(
                [static::MARKER_HELP_TITLE, static::MARKER_HELP_TEXT],
                [$title, $text],
                $helpRow
            );
        }

        // Add it into the wrapper.
        return str_replace(static::MARKER_HELP, $helpContent, $this->getTemplateFileContent(static::FILE_HELP));
    }
}
