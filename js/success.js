// JavaScript Document
var pincodePlaceHolder = "Enter Pincode";
var old_postcode = "";
var errorMessage = "Due to some unexpected errors, this is not available at the moment. We are working on fixing it.";
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
});

preparePullShops();
function preparePullShops()
{
	var postcode = document.getElementById("postcode").value;
	pullNearByShops(postcode,url);
}

function pullNearByShops(postcode,url)
{
	if(postcode.length == 0)
	{
		alert("Enter your post code to retrieve the shops");
	}
	else
	{
		if(old_postcode != postcode)
		{
			$("#see_nearby_shops_container").show();
			$("#see_nearby_shops_container").html(loader);
			
			$.ajax({
				 url: url+"?postcode="+postcode+"&success=1", 
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
				 },
				 error:function(textStatus, xhr){
					  $("#see_nearby_shops_container").html(errorMessage);
				 }
			});
			
		}
	}
}