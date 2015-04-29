{if !$payments}
	<div class="notice">No shops available</div>
{else}
    <table width="100%" border="0" cellspacing="0" cellpadding="0" id="payment_points" class="data-table">
    	<colgroup>
            <col style="width:200px;">
            <col>
        </colgroup>
         <thead>
            <tr>
                <th style="text-align:left;height:25px; border-bottom:1px solid #ccc">Shop Name</th>
                <th style="text-align:left;height:25px; border-bottom:1px solid #ccc">Address</th>
            </tr>
        </thead>
        <tbody>
		{foreach $payments as $payment}
            <tr>
                <td  style="height:25px">{$payment['shop_name']}</td>
                <td>{$payment['address']}</td>
            </tr>
        {/foreach}
        <tbody>
    </table>
{/if}