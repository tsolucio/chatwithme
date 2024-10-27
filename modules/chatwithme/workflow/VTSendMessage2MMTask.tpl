{*<!--
/*************************************************************************************************
 * Copyright 2019 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
 * Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
 * file except in compliance with the License. You can redistribute it and/or modify it
 * under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
 * granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
 * applicable law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing
 * permissions and limitations under the License. You may obtain a copy of the License
 * at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
 *************************************************************************************************
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************//
-->*}
<script src="modules/com_vtiger_workflow/resources/vtigerwebservices.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
	var moduleName = '{$entityName}';
</script>
<script src="modules/chatwithme/workflow/chatwithmetask.js" type="text/javascript" charset="utf-8"></script>
<br/>
<article class="slds-m-around_x-small">
<div class="slds-grid slds-gutters">
	<div class="slds-form-element slds-col">
		<div class="slds-form-element__control">
			<label class="slds-checkbox__label" for="messageColor"><b>{'Color'|@getTranslatedString:'chatwithme'}</b></label>
			<select class="slds-select" id="messageColor" name="messageColor">
				<option value="green" {if $task->messageColor=='green'}selected{/if}>{'Green'|@getTranslatedString:'chatwithme'}</option>
				<option value="blue" {if $task->messageColor=='blue'}selected{/if}>{'Blue'|@getTranslatedString:'chatwithme'}</option>
				<option value="yellow" {if $task->messageColor=='yellow'}selected{/if}>{'Yellow'|@getTranslatedString:'chatwithme'}</option>
				<option value="red" {if $task->messageColor=='red'}selected{/if}>{'Red'|@getTranslatedString:'chatwithme'}</option>
			</select>
		</div>
	</div>
	<div class="slds-form-element slds-col">
		<div class="slds-form-element__control slds-m-top_medium">
			<div class="slds-checkbox">
				<input type="checkbox" name="ephemeral" id="ephemeral" value="ephemeral" {if $task->ephemeral=='ephemeral'}checked{/if} />
				<label class="slds-checkbox__label" for="ephemeral">
				<span class="slds-checkbox_faux"></span>
				<span class="slds-form-element__label"><b>{'Msg is ephemeral'|@getTranslatedString:'chatwithme'}</b></span>
				</label>
			</div>
		</div>
	</div>
</div>
<br/>
<legend class="slds-form-element__label"><b>{'Title'|@getTranslatedString:'chatwithme'}</b></legend><br/>
<div class="slds-form-element slds-col">
	<div class="slds-form-element__control">
		<input id="messageTitle" name="messageTitle" class="slds-input" type="text" value="{$task->messageTitle}" />
	</div>
</div>
<br/>
<legend class="slds-form-element__label"><b>{'Body'|@getTranslatedString:'chatwithme'}</b></legend><br/>
<div class="slds-grid slds-gutters">
	<div class="slds-form-element slds-col">
		<span id="task-fieldnames-busyicon"><b>{$MOD.LBL_LOADING}</b><img src="{vtiger_imageurl('vtbusy.gif', $THEME)}" border="0"></span>
		<select id='task-fieldnames' class="slds-select" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
	</div>
	<div class="slds-form-element slds-col">
		<select class="slds-select" id="task_timefields">
			<option value="">{'Select Meta Variables'|@getTranslatedString:$MODULE_NAME}</option>
			{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
			<option value="{$META_VALUE}">{$META_LABEL|@getTranslatedString:$MODULE_NAME}</option>
			{/foreach}
		</select>
	</div>
</div>
<textarea id ="messageBody" name ="messageBody">{$task->messageBody}</textarea>
<br/>
<legend class="slds-form-element__label"><b>{'Buttons'|@getTranslatedString:'chatwithme'}</b>&nbsp;({'optional'|@getTranslatedString:'chatwithme'})</legend><br/>
<div class="slds-grid slds-gutters">
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_title1">{'First Button Title'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_title1" name="button_title1" class="slds-input" type="text" value="{$task->button_title1}" />
		</div>
	</div>
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_url1">{'First Button Parameters'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_url1" name="button_url1" class="slds-input" type="text" value="{$task->button_url1}" />
		</div>
	</div>
</div>
<div class="slds-grid slds-gutters">
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_title2">{'Second Button Title'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_title2" name="button_title2" class="slds-input" type="text" value="{$task->button_title2}" />
		</div>
	</div>
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_url2">{'Second Button Parameters'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_url2" name="button_url2" class="slds-input" type="text" value="{$task->button_url2}" />
		</div>
	</div>
</div>
<div class="slds-grid slds-gutters">
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_title3">{'Third Button Title'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_title3" name="button_title3" class="slds-input" type="text" value="{$task->button_title3}" />
		</div>
	</div>
	<div class="slds-form-element slds-col">
		<label class="slds-form-element__label" for="button_url3">{'Third Button Parameters'|@getTranslatedString:'chatwithme'}</label>
		<div class="slds-form-element__control">
			<input id="button_url3" name="button_url3" class="slds-input" type="text" value="{$task->button_url3}" />
		</div>
	</div>
</div>
</article>
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
<script>
	CKEDITOR.replace('messageBody', {ldelim}
		customConfig: '../../modules/chatwithme/workflow/ckeditor_config.js'
	{rdelim});
	var oCKeditor = CKEDITOR.instances['messageBody'];
</script>
