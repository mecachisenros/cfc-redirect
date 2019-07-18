<div class="crm-block crm-form-block crm-event-manage-cfcr-form-block">

	<div class="help">
		{ts}
			Redirect this event registration page to a page or post with a Caldera Forms form.
		{/ts}
		<a href="{$manageRedirects}">Manage Caldera Forms Redirects</a>
	</div>

	<table class="form-layout-compressed">
		{foreach from=$formElements item=element}
			<tr class="{$form.$element.name}">
				<td class="label-left">
					<label for="{$element}">
						{$form.$element.label}
					</label>
				</td>
				<td>
					{$form.$element.html}
				</td>
			</tr>
		{/foreach}
	</table>

	<div class="crm-submit-buttons">
		{include file="CRM/common/formButtons.tpl" location="bottom"}
	</div>

</div>
