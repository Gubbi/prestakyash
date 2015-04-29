<link href="{$this_path}css/success.css" rel="stylesheet">
{if $status == 'ok'}
<p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='kyash'}
		<br /><br />
		<strong>{l s='Your order will be confirmed as soon as you pay at one of the authorized payment shops' mod='kyash'}</strong>
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod=''} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='kyash'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='kyash'} 
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='kyash'}</a>.
	</p>
{/if}

<div class="kyash_succcess_instructions" style="border-top:1px solid #ededed;">
    <span class="kyash_heading">Kyash Code: {$kyash_code}</span>
</div>
<div class="kyash_succcess_instructions2">
    <p>{$instructions|nl2br}</p>
</div>
<div class="kyash_succcess_instructions2">
    <input type="text" class="input-text" id="postcode" value="{$postcode}" maxlength="13" style="width:120px; text-align:center"  />
    <input type="button" class="button" id="kyash_postcode_button" value="See nearby shops" onclick="preparePullShops()">
</div>
<div style="display: none" id="see_nearby_shops_container" class="content">
</div>
<script>
var url = "{$link->getModuleLink('kyash', 'paypoints')|escape:'html'}";
var loader = "<img src='{$img_path}loader.gif' alt='Processing...' />";
</script>
<script src="{$this_path}js/success.js" type="text/javascript"></script>