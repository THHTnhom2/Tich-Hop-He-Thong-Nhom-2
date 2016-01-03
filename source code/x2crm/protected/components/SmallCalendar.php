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

Yii::import('application.modules.calendar.controllers.CalendarController');
Yii::import('application.modules.calendar.models.X2Calendar');

/**
 * Widget class for the chat portlet.
 *
 * @package application.components
 */
class SmallCalendar extends X2Widget {

    public $visibility;
    public function init() {
        parent::init();
    }

    public function run() {
        // Prevent the small calendar from showing when using the larger calendar
        if(Yii::app()->controller->modelClass == 'X2Calendar' &&
           Yii::app()->controller->action->getId () == 'index'){
            return;
        }

        // Fetch the calendars to display
        $user = User::model()->findByPk(Yii::app()->user->getId());
        if (is_null($user->showCalendars))
            $user->initCheckedCalendars();
        $showCalendars = $user->showCalendars;

        // Possible urls for the calendar to call
        $urls = X2Calendar::getCalendarUrls();

        $widgetSettingUrl = $this->controller->createUrl('/site/widgetSetting');;

        $justMe = Profile::getWidgetSetting('SmallCalendar','justMe');

        Yii::app()->clientScript->registerCssFile(
            Yii::app()->baseUrl .'/js/fullcalendar-1.6.1/fullcalendar/fullcalendar.css');
        
        Yii::app()->clientScript->registerCssFile(
            Yii::app()->theme->baseUrl .'/css/components/smallCalendar.css');
        
        Yii::app()->clientScript->registerScriptFile(
            Yii::app()->baseUrl.'/js/fullcalendar-1.6.1/fullcalendar/fullcalendar.js');
        
        Yii::app()->clientScript->registerScriptFile(
            Yii::app()->getModule('calendar')->assetsUrl.'/js/calendar.js', CClientScript::POS_END);

        $this->render(
            'smallCalendar',
            array(
                'showCalendars' => $showCalendars,
                'urls' => $urls,
                'user' => $user->username,
                'widgetSettingUrl' => $widgetSettingUrl,
                'justMe' => $justMe
            ));
    }
}

// This tab needs a new name
class PublisherSmallCalendarEventTab extends PublisherEventTab {
    public $tabId ='new-small-calendar-event';
}


?>
