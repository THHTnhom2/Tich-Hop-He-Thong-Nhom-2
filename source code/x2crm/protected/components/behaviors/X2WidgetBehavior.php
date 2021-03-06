<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2015 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

/**
 * Adds namespace mechanism, packages, and JS class instantiation to children of CWidget 
 */

class X2WidgetBehavior extends CBehavior {

    const NAMESPACE_KEY = '_x2widget_namespace';

    /**
     * @var string $element
     */
    public $element; 

    /**
     * @var string $JSClass
     */
    public $JSClass = 'Widget'; 

    /**
     * @var bool $instantiateJSClassOnInit
     */
    public $instantiateJSClassOnInit = false;

    public $checkIfJSClassIsDefined = false;

    /**
     * @var string $namespace
     */
    public $namespace = ''; 

    public function __construct () {
    }

    public function attach ($owner) {
        if (!($owner instanceof CWidget)) {
            throw new CException ('owner must be an instance of CWidget'); 
        }
        return parent::attach ($owner);
    }

    public function initNamespace () {
        if ($this->owner->namespace === '' && isset ($_POST[self::NAMESPACE_KEY])) {
            $this->owner->namespace = $_POST[self::NAMESPACE_KEY];
        }
    }

    public function resolveIds ($selector) {
        return preg_replace ('/#/', '#'.$this->owner->namespace, $selector);
    }

    public function resolveId ($id) {
        return $this->owner->namespace.$id;
    }

    public function getJSObjectName () {
        return "x2.".$this->owner->namespace.lcfirst ($this->owner->JSClass);
    }

    public function registerPackages () {
        Yii::app()->clientScript->registerPackages ($this->owner->getPackages (), true);
    }

    protected $_packages;
    public function getPackages () {
        if (!isset ($this->_packages)) {
            $this->_packages = array (
                'X2Widget' => array(
                    'baseUrl' => Yii::app()->request->baseUrl,
                    'js' => array(
                        'js/X2Widget.js',
                    ),
                ),
            );
        }
        return $this->_packages;
    }

    /**
     * @param bool $onReady whether or not JS class should be instantiated after page is ready
     */
    public function instantiateJSClass ($onReady=true) {
        $jsObjName = $this->owner->getJSObjectName ();
        Yii::app()->clientScript->registerScript (
            $this->owner->getId ().get_class ($this->owner).'JSClassInstantiation', 
            ($onReady ? "$(function () {" : "").
                ($this->checkIfJSClassIsDefined ? "if (typeof $jsObjName === 'undefined') {" : '').
                "$jsObjName = new x2.{$this->owner->JSClass} (".
                        CJSON::encode ($this->owner->getJSClassParams ()).
                    ");".
                ($this->checkIfJSClassIsDefined ? "}" : '').
            ($onReady ? "});" : ""), CClientScript::POS_END);

        Yii::app()->clientScript->registerScript('X2WidgetSetup',"
        x2.Widget.NAMESPACE_KEY = '".self::NAMESPACE_KEY."';
        ", CClientScript::POS_READY);
    }

    protected $_translations;
    protected function getTranslations () {
        if (!isset ($this->_translations)) {
            $this->_translations = array ();
        }
        return $this->_translations;
    }

    protected $_JSClassParams;
    public function getJSClassParams () {
        if (!isset ($this->_JSClassParams)) {
            $this->_JSClassParams = array (
                'element' => isset ($this->owner->element) ? 
                    $this->owner->element : '#'.$this->owner->id,
                'translations' => $this->owner->getTranslations (),
                'namespace' => $this->owner->namespace,
            );
        }
        return $this->_JSClassParams;
    }

}

?>
