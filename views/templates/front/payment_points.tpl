{if !$payments}
	<div class="notice">No shops available</div>
{else}
    <iframe src="{$payments['widget']}" frameborder="0" style="border: none; width: 100%;"></iframe>
{/if}