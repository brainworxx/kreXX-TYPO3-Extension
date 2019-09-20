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

trait Recursion
{
    /**
     * {@inheritdoc}
     */
    public function renderRecursion(Model $model)
    {
        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_DOM_ID,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_HELP,
            ],
            [
                $model->getName(),
                $model->getDomid(),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight()),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_SOURCE,
                    $this->pool->codegenHandler->generateSource($model)
                ),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_RECURSION)
        );
    }
}
