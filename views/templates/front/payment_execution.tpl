{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='bankwire'}">{l s='Checkout' mod='flooz'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Kyash - Pay at a nearby Shop' mod='flooz'}
{/capture}

<h2>{l s='Kyash - Pay at a nearby Shop' mod='flooz'}</h2>

{if isset($errors2) && $errors2}
	<div class="alert alert-danger">
		<ul style="list-style:none">
		{foreach from=$errors2 key=k item=error}
			<li>{$error}</li>
		{/foreach}
		</ul>
	</div>
{/if}


<p class="cart_navigation" id="cart_navigation">
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='flooz'}</a>
</p>

