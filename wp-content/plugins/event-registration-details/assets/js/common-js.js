function event_validate(){
	
	if(jQuery('#first_name').val() == '')
	{
		alert("Please Enter First Name");
		jQuery('#first_name').focus();
		return false;
	}
	if(jQuery('#last_name').val() == '')
	{
		alert("Please Enter Last Name");
		jQuery('#last_name').focus();
		return false;
	}
	if(jQuery('#email_address').val() == '')
	{
		alert("Please Enter Email");
		jQuery('#email_address').focus();
		return false;
	}
	// if(jQuery('#email_address').val() != '' && !emailValidator(jQuery('#email_address'),"Please Enter Valid Email"))
	// {
	// 	return false;
	// }
	if(jQuery('#billing_address').val() == '')
	{
		alert("Please Enter Billing Address");
		jQuery('#billing_address').focus();
		return false;
	}
	if(jQuery('#billing_city').val() == '')
	{
		alert("Please Enter Billing City");
		jQuery('#billing_city').focus();
		return false;
	}
	if(jQuery('#billing_state').val() == '')
	{
		alert("Please Enter Billing State");
		jQuery('#billing_state').focus();
		return false;
	}
	if(jQuery('#billing_postalcode').val() == '')
	{
		alert("Please Enter Billing Postal Code");
		jQuery('#billing_postalcode').focus();
		return false;
	}	
	if(jQuery('#event_id').val() == '')
	{
		alert("Please Select Event Name");
		jQuery('#event_id').focus();
		return false;
	}	
	var response = grecaptcha.getResponse();
	if(response.length == 0)
	{
	alert("please verify you are humann!");
	return false;
	}
}


function emailValidator(elem, helperMsg){
	var emailExp = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
	if(elem.value.match(emailExp)){
		return true;
	}else{
		alert(helperMsg);
		elem.focus();
		return false;
	}
}