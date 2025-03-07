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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Protected helper methods for the real render class.
 */
abstract class AbstractRender implements RenderInterface
{
    /**
     * Css class name.
     *
     * @var string
     */
    protected const STYLE_HIDDEN = 'khidden';

    /**
     * Css class name.
     *
     * @var string
     */
    protected const STYLE_ACTIVE = 'kactive';

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * Caching the content fo the template files.
     *
     * @var string[]
     */
    protected array $fileCache = [];

    /**
     * Inject the pool and inject $this into the concrete render object of the
     * pool.
     *
     * @param Pool $pool
     *   The pool.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->pool->render = $this;

        // Prepare the template file cache.
        foreach (glob(($this->pool->config->getSkinDirectory()) . '*.html') as $filePath) {
            $this->fileCache[basename($filePath, '.html')] = $pool->fileService->getFileContents($filePath);
        }
    }

    /**
     * Some special escaping for the json output
     *
     * @param string[] $array
     *   The string we want to special-escape
     * @return string
     *   The json from the array.
     */
    protected function encodeJson(array $array): string
    {
        // No data, no json!
        if (empty($array)) {
            return '';
        }

        $encodingService = $this->pool->encodingService;
        foreach ($array as &$entry) {
            $entry = $encodingService->encodeString($entry);
        }

        return json_encode($array);
    }

    /**
     * Generates a data attribute, to be inserted into the HTML tags.
     * If no value is in the data, we return an empty string.
     * Double quotes gets replaced by &#34;
     *
     * @param string $name
     *   The name of the attribute without the 'data-' in front
     * @param string $data
     *   The value. Must be string.
     *
     * @return string
     *   The generated data attribute.
     */
    protected function generateDataAttribute(string $name, string $data): string
    {
        return ' data-' . $name . '="' . str_replace('"', '&#34;', $data) . '" ';
    }

    /**
     * Retrieve the css type classes form the model.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The css classes.
     */
    protected function retrieveTypeClasses(Model $model): string
    {
        $typeClasses = $model->isExpandable() ? 'kexpand ' : ' ';

        foreach (explode(' ', $model->getType()) as $typeClass) {
            $typeClasses .= 'k' . $typeClass . ' ';
        }

        return strtolower($typeClasses);
    }
}
