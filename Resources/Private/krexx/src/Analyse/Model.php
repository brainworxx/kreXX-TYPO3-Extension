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

namespace Brainworxx\Krexx\Analyse;

use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Model\AdditionalType;
use Brainworxx\Krexx\Analyse\Model\Callback;
use Brainworxx\Krexx\Analyse\Model\CodeGenType;
use Brainworxx\Krexx\Analyse\Model\ConnectorService;
use Brainworxx\Krexx\Analyse\Model\Data;
use Brainworxx\Krexx\Analyse\Model\DomId;
use Brainworxx\Krexx\Analyse\Model\HasExtra;
use Brainworxx\Krexx\Analyse\Model\IsCallback;
use Brainworxx\Krexx\Analyse\Model\IsMetaConstants;
use Brainworxx\Krexx\Analyse\Model\IsPublic;
use Brainworxx\Krexx\Analyse\Model\Json;
use Brainworxx\Krexx\Analyse\Model\KeyType;
use Brainworxx\Krexx\Analyse\Model\MultiLineCodeGen;
use Brainworxx\Krexx\Analyse\Model\Name;
use Brainworxx\Krexx\Analyse\Model\Normal;
use Brainworxx\Krexx\View\ViewConstInterface;

/**
 * Model for the view rendering
 */
class Model implements ViewConstInterface, CodegenConstInterface
{
    use ConnectorService;
    use Callback;
    use Json;
    use Data;
    use Name;
    use Normal;
    use AdditionalType;
    use DomId;
    use HasExtra;
    use CodeGenType;
    use KeyType;

    use MultiLineCodeGen;
    use IsPublic;
    use IsMetaConstants;
    use IsCallback;
}
