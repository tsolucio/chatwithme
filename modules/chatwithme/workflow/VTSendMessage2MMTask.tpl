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
<h2>{'LBL_MSG'|@getTranslatedString:'com_vtiger_workflow'}</h2>
<table border="0" cellpadding="0" cellspacing="0" width="100%" class="small">
	<tr>
		<td style='padding-top: 10px;'>
			<b>{$MOD.LBL_SELECT}&nbsp; Meta Variables</b>
		</td>
		<td style='padding-top: 10px;'>
			<select class="small" id="task_timefields">
				<option value="">{'Select Meta Variables'|@getTranslatedString:$MODULE_NAME}</option>
				{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
				<option value="{$META_VALUE}">{$META_LABEL|@getTranslatedString:$MODULE_NAME}</option>
				{/foreach}
			</select>
		</td>
	</tr>
</table><br/>
<input type="checkbox" name="ephemeral" value="ephemeral"/>Msg is ephemeral<br/><br/>
<label>Color</label>
<select class="small" id="messageColor" name="messageColor">
	<option value="">Select Color</option>
	<option value="yellow">Yellow</option>
	<option value="blue">Blue</option>
	<option value="green">Green</option>
</select>  <br/>
<label>Title</label><br/>
<input type ="text" name ="messageTitle"/><br/>
<label>Body</label><br/><br/>
<textarea name ="messageBody"> </textarea>

<table border="0" cellpadding="1" cellspacing="0" width="100%" class="small">
	<tr>
		<b>Buttons</b>(optional)
	</tr>
	<tr>
		<td><label>First Button Title</label></td>
		<td><input type="text" name="button_title1"/></td>
		<td><label>First Action Url</label></td>
		<td><input type="text" name="button_url1"/></td>
	</tr>
	<tr>
		<td><label>Second Button Title</label></td>
		<td><input type="text" name="button_title2"/></td>
		<td><label>Second Action Url</label></td>
		<td><input type="text" name="button_url2"/></td>
	</tr>
	<tr>
		<td><label>Third Button Title</label></td>
		<td><input type="text" name="button_title3"/></td>
		<td><label>Third Action Url</label></td>
		<td><input type="text" name="button_url3"/></td>
	</tr>
</table> 
<br>
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
<script>
	CKEDITOR.replace('messageBody',
	{ldelim}
		extraPlugins : 'uicolor',
		uiColor: '#dfdff1',
			on : {ldelim}
				instanceReady : function( ev ) {ldelim}
					this.dataProcessor.writer.setRules( 'p',  {ldelim}
					indent : false,
					breakBeforeOpen : false,
					breakAfterOpen : false,
					breakBeforeClose : false,
					breakAfterClose : false
				{rdelim});
			{rdelim}
		{rdelim}
	{rdelim});
	var oCKeditor{'messageBody'} = CKEDITOR.instances['messageBody'];
</script>
