<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class chatwithme extends CRMEntity {
	public $table_name = 'vtiger_MODULE_NAME_LOWERCASE';
	public $table_index= 'MODULE_NAME_LOWERCASEid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	public $moduleIcon = array('library' => 'utility', 'containerClass' => 'slds-icon_container slds-icon-standard-user', 'class' => 'slds-icon', 'icon'=>'sync');
	public $tab_name = array();
	public $tab_name_index = array();

	/**
	 * Invoked when special actions are performed on the module.
	 * @param string Module name
	 * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		global $adb;
		if ($event_type == 'module.postinstall') {
			// Handle post installation actions
			$adb->query("INSERT INTO vtiger_notificationdrivers (type,path,functionname) VALUES ('CWM','modules/chatwithme/cwmapi.php','__cwmDoNothing')");
			include_once 'vtlib/Vtiger/Module.php';
			include_once 'modules/com_vtiger_workflow/VTTaskManager.inc';
			$defaultModules = array('include' => array(), 'exclude'=>array());
			$taskType= array(
				"name"=>"CBSendMMMSGTask",
				"label"=>"Send Message To MM",
				"classname"=>"VTSendMessage2MMTask",
				"classpath"=>"modules/chatwithme/workflow/VTSendMessage2MMTask.inc",
				"templatepath"=>"modules/chatwithme/workflow/VTSendMessage2MMTask.tpl",
				"modules"=>$defaultModules,
				"sourcemodule"=>'',
			);
			VTTaskType::registerTaskType($taskType);
			$modname = 'Users';
			$module = Vtiger_Module::getInstance($modname);
			$field = Vtiger_Field::getInstance('mmuserid', $module);
			if ($field) {
				$this->ExecuteQuery('update vtiger_field set presence=2 where fieldid=?', array($field->id));
			} else {
				$block = Vtiger_Block::getInstance('LBL_USER_ADV_OPTIONS', $module);
				$fieldInstance = new Vtiger_Field();
				$fieldInstance->name = 'mmuserid';
				$fieldInstance->label = 'mmuserid';
				$fieldInstance->columntype = 'varchar(100)';
				$fieldInstance->uitype = 1;
				$fieldInstance->displaytype = 1;
				$fieldInstance->typeofdata = 'V~O';
				$block->addField($fieldInstance);
			}
			$modname = 'cbCalendar';
			$module = Vtiger_Module::getInstance($modname);
			$field = Vtiger_Field::getInstance('activitytype', $module);
			if ($field) {
				$field->setPicklistValues(array('MMRemindMe'));
			}
			// workflows
			$workflowManager = new VTWorkflowManager($adb);
			$taskManager = new VTTaskManager($adb);
			// Send MM Reminder workflow
			$calendarWorkflow = $workflowManager->newWorkFlow('cbCalendar');
			$calendarWorkflow->test = '[{"fieldname":"activitytype","operation":"is","value":"MMRemindMe","valuetype":"rawtext","joincondition":"and","groupid":"0"},{"fieldname":"eventstatus","operation":"is not","value":"Completed","valuetype":"rawtext","joincondition":"and","groupid":"0"},{"fieldname":"dtstart","operation":"less than hours before","value":"72","valuetype":"expression","joincondition":"and","groupid":"0"}]';
			$calendarWorkflow->description = 'Send MM Reminder';
			$calendarWorkflow->executionCondition = VTWorkflowManager::$ON_SCHEDULE;
			$calendarWorkflow->defaultworkflow = 0;
			$calendarWorkflow->schtypeid = 8;
			$calendarWorkflow->schminuteinterval = 5;
			$calendarWorkflow->purpose = 'Send RemindMe message to mattermost';
			$workflowManager->save($calendarWorkflow);
			$task = $taskManager->createTask('CBSendMMMSGTask', $calendarWorkflow->id);
			$task->active = true;
			$task->summary = 'Send MM Reminder';
			$task->executeImmediately = true;
			$task->messageTitle = '$subject';
			$task->messageBody = '$description';
			$task->messageColor = 'blue';
			$task->button_title1 = 'Postpone';
			$task->button_url1 = '&text=remindmeaction&event=postpone';
			$task->button_title2 = 'Discard';
			$task->button_url2 = '&text=remindmeaction&event=discard';
			$task->button_title3 = '';
			$task->button_url3 = '';
			$task->ephemeral = '';
			$task->reevaluate = 0;
			$taskManager->saveTask($task);
			$url = 'index.php?module=chatwithme&action=chatwithmeAjax&file=syncmmuser&return_module=$MODULE$&return_action=DetailView&usrid=$RECORD$';
			BusinessActions::addLink(getTabid('Users'), 'DETAILVIEWBASIC', 'Sync with Mattermost', $url, '', 0, '', true, 0);
			$gvmoduleInstance = Vtiger_Module::getInstance('GlobalVariable');
			$gvfield = Vtiger_Field::getInstance('gvname', $gvmoduleInstance);
			if ($gvfield) {
				$gvfield->setPicklistValues(array('CWM_TC_ProjectTask', 'CWM_TC_ProjectSubTask'));
			}
		} elseif ($event_type == 'module.disabled') {
			// Handle actions when this module is disabled.
			$adb->query("DELETE FROM vtiger_notificationdrivers WHERE type='CWM' and path='modules/chatwithme/cwmapi.php' and functionname='__cwmDoNothing'");
		} elseif ($event_type == 'module.enabled') {
			// Handle actions when this module is enabled.
			$adb->query("INSERT INTO vtiger_notificationdrivers (type,path,functionname) VALUES ('CWM','modules/chatwithme/cwmapi.php','__cwmDoNothing')");
		} elseif ($event_type == 'module.preuninstall') {
			// Handle actions when this module is about to be deleted.
			$adb->query("DELETE FROM vtiger_notificationdrivers WHERE type='CWM' and path='modules/chatwithme/cwmapi.php' and functionname='__cwmDoNothing'");
		} elseif ($event_type == 'module.preupdate') {
			// Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// Handle actions after this module is updated.
		}
	}
}
?>
