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

namespace Brainworxx\Krexx\Tests\View\Skins;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use Brainworxx\Krexx\View\Skins\RenderHans;

abstract class AbstractRenderHans extends AbstractTest
{
    const PATH_TO_SKIN = '/some path/';
    const GET_NAME = 'getName';
    const GET_DOMID = 'getDomid';
    const GET_NORMAL = 'getNormal';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const GET_JSON = 'getJson';
    const GET_HAS_EXTRAS = 'getHasExtra';
    const GET_DATA = 'getData';
    const GET_IS_CALLBACK = 'getIsCallback';
    const GET_TYPE = 'getType';
    const RENDER_ME = 'renderMe';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fileServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $modelMock;

    /**
     * @var \Brainworxx\Krexx\View\Skins\RenderHans
     */
    protected $renderHans;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->renderHans = new RenderHans(Krexx::$pool);
        $this->setValueByReflection('skinPath', static::PATH_TO_SKIN, $this->renderHans);
        $this->mockTemplate();
    }

    /**
     * Short circuiting the existence of a specific template file.
     * Nice, huh?
     *
     * @see \Brainworxx\Krexx\View\AbstractRender::getTemplateFileContent
     *
     */
    protected function mockTemplate()
    {
        $fileSuffix = '.html';
        $this->fileServiceMock = $this->createMock(File::class);
        $this->fileServiceMock->expects($this->any())
            ->method('getFileContents')
            ->will($this->returnValueMap([
                // connectorLeft.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CONNECTOR_LEFT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CONNECTOR
                ],
                // connectorRight.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CONNECTOR_RIGHT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CONNECTOR
                ],
                // helprow.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HELPROW . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_HELP_TITLE . $this->renderHans::MARKER_HELP_TEXT
                ],
                // help.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HELP . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_HELP
                ],
                // recursion.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_RECURSION . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_DOM_ID .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_HELP
                ],
                // header.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_HEADER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VERSION .
                    $this->renderHans::MARKER_KREXX_COUNT .
                    $this->renderHans::MARKER_HEADLINE .
                    $this->renderHans::MARKER_CSS_JS .
                    $this->renderHans::MARKER_KREXX_ID .
                    $this->renderHans::MARKER_SEARCH .
                    $this->renderHans::MARKER_MESSAGES .
                    $this->renderHans::MARKER_ENCODING
                ],
                // search.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SEARCH . $fileSuffix,
                    true,
                    ''
                ],
                // footer.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FOOTER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CALLER .
                    $this->renderHans::MARKER_CONFIG_INFO.
                    $this->renderHans::MARKER_PLUGINS
                ],
                // caller.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CALLER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CALLER_FILE .
                    $this->renderHans::MARKER_CALLER_DATE .
                    $this->renderHans::MARKER_CALLER_LINE
                ],
                // singlePlugin.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_PLUGIN . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_PLUGIN_ACTIVE_CLASS .
                    $this->renderHans::MARKER_PLUGIN_ACTIVE_TEXT .
                    $this->renderHans::MARKER_PLUGIN_TEXT
                ],
                // cssJs.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_CSSJS . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CSS .
                    $this->renderHans::MARKER_JS
                ],
                // singleChild.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_GEN_SOURCE .
                    $this->renderHans::MARKER_SOURCE_BUTTON .
                    $this->renderHans::MARKER_EXPAND .
                    $this->renderHans::MARKER_CALLABLE .
                    $this->renderHans::MARKER_EXTRA .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_TYPE_CLASSES .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderHans::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderHans::MARKER_HELP,
                ],
                // singelChildCallable.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD_CALL . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_NORMAL
                ],
                // singleChildExtra.html
                [
                  static::PATH_TO_SKIN . $this->renderHans::FILE_SI_CHILD_EX . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_DATA
                ],
                // sourceButton.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SOURCE_BUTTON . $fileSuffix,
                    true,
                    'sourcebutton'
                ],
                // expandableChildNormal.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_EX_CHILD_NORMAL . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_GEN_SOURCE .
                    $this->renderHans::MARKER_CODE_WRAPPER_LEFT .
                    $this->renderHans::MARKER_CODE_WRAPPER_RIGHT .
                    $this->renderHans::MARKER_IS_EXPANDED .
                    $this->renderHans::MARKER_K_TYPE .
                    $this->renderHans::MARKER_CONNECTOR_LEFT .
                    $this->renderHans::MARKER_CONNECTOR_RIGHT .
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_SOURCE_BUTTON .
                    $this->renderHans::MARKER_HELP .
                    $this->renderHans::MARKER_NEST
                ],
                // nest.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_NEST . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_STYLE .
                    $this->renderHans::MARKER_MAIN_FUNCTION .
                    $this->renderHans::MARKER_DOM_ID
                ],
                // singleEditableChild.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_EDIT_CHILD . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_NAME .
                    $this->renderHans::MARKER_NORMAL .
                    $this->renderHans::MARKER_SOURCE .
                    $this->renderHans::MARKER_HELP
                ],
                // singleInput.html
                [
                    static::PATH_TO_SKIN . 'singleInput' . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ID .
                    $this->renderHans::MARKER_VALUE .
                    '<input'
                ],
                // singleSelect.html
                [
                    static::PATH_TO_SKIN . 'single' . Fallback::RENDER_TYPE_SELECT . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ID .
                    $this->renderHans::MARKER_OPTIONS
                ],
                // singleSelectOption.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_SELECT_OPTIONS . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VALUE .
                    $this->renderHans::MARKER_SELECTED .
                    $this->renderHans::MARKER_TEXT
                ],
                // singleButton.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_BUTTON . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_TYPE_CLASSES .
                    $this->renderHans::MARKER_CLASS .
                    $this->renderHans::MARKER_TEXT .
                    $this->renderHans::MARKER_HELP
                ],
                // fatalMain.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FATAL_MAIN . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_ERROR_STRING .
                    $this->renderHans::MARKER_FILE .
                    $this->renderHans::MARKER_LINE .
                    $this->renderHans::MARKER_SOURCE
                ],
                // fatalHeader.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_FATAL_HEADER . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_VERSION .
                    $this->renderHans::MARKER_ENCODING .
                    $this->renderHans::MARKER_CSS_JS .
                    $this->renderHans::MARKER_SEARCH .
                    $this->renderHans::MARKER_TYPE .
                    $this->renderHans::MARKER_KREXX_ID
                ],
                // messages.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_MESSAGE . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_MESSAGE
                ],
                // backtraceSourceLine
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_BACKTRACE_SOURCELINE . $fileSuffix,
                    true,
                    $this->renderHans::MARKER_CLASS_NAME .
                    $this->renderHans::MARKER_LINE_NO .
                    $this->renderHans::MARKER_SOURCE_CODE
                ],
                // singleChildHr.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_SI_HR . $fileSuffix,
                    true,
                    'HR does not mean human resources'
                ],
                // br.html
                [
                    static::PATH_TO_SKIN . $this->renderHans::FILE_BR . $fileSuffix,
                    true,
                    'Breaking the line! Breaking the line!'
                ]
            ]));

        Krexx::$pool->fileService = $this->fileServiceMock;
    }

    /**
     * The great Moddelmock is not a wizard from Harry Potter.
     *
     * @param $methodName
     * @param $returnValue
     */
    protected function mockModel($methodName, $returnValue)
    {
        if (empty($this->modelMock)) {
            $this->modelMock = $this->createMock(Model::class);
        }
        $this->modelMock->expects($this->once())
            ->method($methodName)
            ->will($this->returnValue($returnValue));
    }
}
