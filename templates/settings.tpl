<table width="100%" cellpadding="2" cellspacing="0" border="0" class="detailview_wrapper_table">
	<tr>
		<td class="detailview_wrapper_cell">
			{include file='Buttons_List.tpl'}
		</td>
	</tr>
</table>
<div class="slds-page-header" role="banner">
	<div class="slds-grid">
		<div class="slds-col slds-has-flexi-truncate">
			<div class="slds-media slds-no-space slds-grow">
				<div class="slds-media__figure">
					<svg aria-hidden="true" class="slds-icon slds-icon-standard-user">
						<use xlink:href="include/LD/assets/icons/utility-sprite/svg/symbols.svg#sync"></use>
					</svg>
				</div>
				<div class="slds-media__body">
					<h1 class="slds-page-header__title slds-m-right_small slds-align-middle slds-truncate"
						title="{$TITLE_MESSAGE}">{$TITLE_MESSAGE}</h1>
				</div>
			</div>
		</div>
	</div>
</div>
<br>
{if $ISADMIN}
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
<div class="slds-m-top_large">
	<button type="submit" class="slds-button slds-button_brand">{'LBL_SAVE_BUTTON_LABEL'|@getTranslatedString:$MODULE}</button>
</div>
</form>
{/if}