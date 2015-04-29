// JavaScript Document
var pincodePlaceHolder = "Enter Pincode";
var old_postcode = "";
var errorMessage = "<span class='kyash_error'>Due to some unexpected errors, this is not available at the moment. We are working on fixing it.</span>";
$(document).ready(function(){
	$("#kyash_postcode").focus(function(){
		if($(this).val() == pincodePlaceHolder)
		{
			$(this).val("");
		}
	});
	
	$("#kyash_postcode").blur(function(){
		if($(this).val().length == 0)
		{
			$(this).val(pincodePlaceHolder);
		}
	});
	
	$("#kyash_postcode").click(function(event){
		event.stopPropagation();
	});
	
	$("#kyash_open").click(function(event ){
		openShops();
		event.stopPropagation();
	});
	
	$("#kyash_close").click(function(event ){
		closeShops();
		event.stopPropagation();
	});
	
	$("#kyash_postcode_button").click(function(event ){
		pullNearByShops();
		event.stopPropagation();
	});
	
	$("#kyash").click(function(){
		location.href = validation_url;
	});
	
	
});
function openShops()
{
	$("#kyash_postcode_payment_sub").show();
	$("#see_nearby_shops_container").hide();
	$("#kyash_open").hide();
	pullNearByShops();
}
function closeShops()
{
	$("#kyash_postcode_payment_sub").show();
	$("#see_nearby_shops_container").hide();

	$("#kyash_open").hide();
	pullNearByShops();
}

function closeShops()
{
	$("#see_nearby_shops_container").hide();
	$("#kyash_close").hide();
}

function pullNearByShops()
{
	closeShops();
	postcode = $("#kyash_postcode").val();
	if(postcode.length == 0 || postcode == pincodePlaceHolder)
	{
		alert("Enter your post code to retrieve the shops");
	}
	else
	{
		if(old_postcode == postcode)
		{
			$("#see_nearby_shops_container").show();
			$("#kyash_close").show();
		}
		else
		{
			$("#see_nearby_shops_container").show();
			$("#see_nearby_shops_container").html(loader);
			$.ajax({
				 url: url+"?postcode="+postcode, 
				 success: function(output, textStatus, xhr){
					 if(xhr.status == 400 || xhr.status == 200)
					 {
						$("#see_nearby_shops_container").html(output);
						old_postcode = postcode;
					 }
					 else
					 {
						 $("#see_nearby_shops_container").html(errorMessage);
					 }
					 $("#kyash_close").show();
				 },
				 error:function(textStatus, xhr){
					  $("#see_nearby_shops_container").html(errorMessage);
					  $("#kyash_close").show();
				 }
			});
		}
	}
}