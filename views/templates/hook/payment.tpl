<style>
{literal}
p.payment_module a.kyash{
	text-decoration:none;
{/literal}	
	background: url({$this_path}logo.png) 15px 15px no-repeat #fbfbfb;
{literal}	
}
{/literal}
</style>
<link href="{$this_path}css/payment.css" rel="stylesheet">
<p class="payment_module">
    <a class="kyash" id="kyash" href="javascript:void(0)" title="{l s='Kyash - Pay at a nearby Shop' mod='kyash'}">
        <span class="kyash_payment">{l s='Kyash - Pay at a nearby Shop' mod='kyash'}</span> 
        &nbsp;&nbsp;
        <span id="kyash_open">See nearby shops</span>
        
        <span id="kyash_postcode_payment_sub">
            <input type="text" class="input-text" id="kyash_postcode" value="{$postcode}" maxlength="13" />
            <button id="kyash_postcode_button" class="button" type="button">
                <span><span>See Nearby Shops</span></span>
            </button>
            <span id="kyash_close" style="float:right">
              X
            </span>
        </span>
    </a>
    
    <div style="display: none " id="see_nearby_shops_container"></div>
    
</p>

<script>
var url = "{$link->getModuleLink('kyash', 'paypoints')|escape:'html'}";
var validation_url = "{$link->getModuleLink('kyash', 'validation')|escape:'html'}";
var loader = "<img src='{$img_path}loader.gif' alt='Processing...' />";
</script>
<script src="{$this_path}js/payment.js" type="text/javascript"></script>