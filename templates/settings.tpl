{include file='Buttons_List.tpl'}
<br>
{if $ISADMIN}
<article class="slds-card slds-m-left_x-large slds-m-right_x-large slds-m-bottom_x-large slds-p-around_small">
<form role="form" style="margin:0 100px;" method="post">
<input type="hidden" name="module" value="chatwithme">
<input type="hidden" name="action" value="ListView">
<input type="hidden" name="_op" value="setmmconfig">
<div class="slds-form-element">
	<label class="slds-checkbox_toggle slds-grid">
	<span class="slds-form-element__label slds-m-bottom_none">{'_active'|@getTranslatedString:$MODULE}</span>
	<input type="checkbox" name="mm_active" aria-describedby="toggle-desc" {if $isActive}checked{/if} />
	<span id="toggle-desc" class="slds-checkbox_faux_container" aria-live="assertive">
		<span class="slds-checkbox_faux"></span>
		<span class="slds-checkbox_on">{'LBL_ENABLED'|@getTranslatedString:'Settings'}</span>
		<span class="slds-checkbox_off">{'LBL_DISABLED'|@getTranslatedString:'Settings'}</span>
	</span>
	</label>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_cmdlang">{'_cmdlang'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<select class="slds-select" id="mm_cmdlang" name="mm_cmdlang">
		{html_options values=$cmdlangs output=$cmdlangs selected=$command_language}
		</select>
	</div>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_username">{'_username'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<input type="text" id="mm_username" name="mm_username" class="slds-input" value="{$username}" />
	</div>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_icon_url">{'_icon_url'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<input type="text" id="mm_icon_url" name="mm_icon_url" class="slds-input" value="{$icon_url}" />
	</div>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_posturl">{'_posturl'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<input type="text" id="mm_posturl" name="mm_posturl" class="slds-input" value="{$posturl}" />
	</div>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_tokens">{'_tokens'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<input type="text" id="mm_tokens" name="mm_tokens" class="slds-input" value="{$tokens}" />
	</div>
</div>
<div class="slds-form-element slds-m-top_small">
	<label class="slds-form-element__label" for="mm_userpasswd">{'_mmuserpasswd'|@getTranslatedString:$MODULE}</label>
	<div class="slds-form-element__control">
		<input type="password" id="mm_userpasswd" name="mm_userpasswd" class="slds-input" value="{$mmuserpasswd}" />
	</div>
</div>
<div class="slds-m-top_large">
	<button type="submit" class="slds-button slds-button_brand">{'LBL_SAVE_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
</div>
</form>
</article>
{/if}
