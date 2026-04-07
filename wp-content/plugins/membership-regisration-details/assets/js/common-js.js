function membership_validate(){
	
  var first_name=document.getElementById('first_name');
  var last_name=document.getElementById('last_name');
  //var company_name=document.getElementById('company_name');
  var email=document.getElementById('email_address'); 
  var address1=document.getElementById('address1');
  var city=document.getElementById('city');
  var state=document.getElementById('state');
  var postal_code=document.getElementById('postal_code');
  var country=document.getElementById('country');  
  var college_university=document.getElementById('college_university');  	  
  var date_education=document.getElementById('date_education');  
  var degree_expected=document.getElementById('degree_expected');
  var company_address=document.getElementById('company_address');
  var county=document.getElementById('county');
  var experience_real_estate = document.getElementById('experience_real_estate');
  var real_estate_industry_professional=document.getElementById('real_estate_industry_professional');
  var comme_real_estate_sector=document.getElementById('comme_real_estate_sector');
  var card_name=document.getElementById('card_name');
  var card_type=document.getElementById('card_type');  
  var card_number=document.getElementById('card_number');
  var card_cvc=document.getElementById('card_cvc');

  var form_data = new FormData(document.querySelector("form"));


  if(first_name.value=="")
  {
    alert("Please Enter First Name");
    first_name.focus();
    return false;
  }
  if(last_name.value=="")
  {
    alert("Please Enter Last Name");
    last_name.focus();
    return false;
  }
  // if(company_name.value=="")
  // {
  //   alert("Please Enter Company Name");
  //   company_name.focus();
  //   return false;
  // } 
  if(email.value=="")
  {
    alert("Please Enter Email Address");
    email.focus();
    return false;
  }
//   else if(validateEmail(email) == false)
//   {
//     alert("Please Enter Valid Email Address");
//     email.focus();
//     return false;
//   }
  if(address1.value=="")
  {
    alert("Please Enter Address");
    address1.focus();
    return false;
  }
  if(city.value=="")
  {
    alert("Please Enter City");
    city.focus();
    return false;
  }
  if(state.value=="")
  {
    alert("Please Enter State");
    state.focus();
    return false;
  }
  if(postal_code.value=="")
  {
    alert("Please Enter Postal Code");
    postal_code.focus();
    return false;
  }
  if(country.value=="")
  {
    alert("Please Select Country");
    country.focus();
    return false;
  }
  if( jQuery('#young_professional_student').is(':checked') ){
	  if(college_university.value=="")
	  {
	    alert("Please Enter College/University");
	    college_university.focus();
	    return false;
	  }
	  if(date_education.value=="")
	  {
	    alert("Please Enter Date of Graduation");
	    date_education.focus();
	    return false;
	  }
	  if(degree_expected.value=="")
	  {
	    alert("Please Select Degree Expected");
	    degree_expected.focus();
	    return false;
	  }
  }  
  if(company_address.value=="")
  {
    alert("Please Enter Billing Address");
    company_address.focus();
    return false;
  }

  if(billing_city.value=="")
  {
    alert("Please Enter Billing City");
    billing_city.focus();
    return false;
  }

  if(billing_state.value=="")
  {
    alert("Please Enter Billing State");
    billing_state.focus();
    return false;
  }

  if(billing_postal_code.value=="")
  {
    alert("Please Enter Billing Postal Code");
    billing_postal_code.focus();
    return false;
  }

  if(experience_real_estate.value=="")
  {
    alert("Please Enter Years of experience in real estate industry");
    experience_real_estate.focus();
    return false;
  }

  if(real_estate_industry_professional.value=="")
  {
    alert("Please Select Professional Level");
    real_estate_industry_professional.focus();
    return false;
  }
 
  if(comme_real_estate_sector.value=="")
  {
    alert("Please Select Real Estate Sector");
    comme_real_estate_sector.focus();
    return false;
  }
  if(!form_data.has("committee_preference[]"))
  {
    alert("Please Select at Lease One Committee Preference Option");
    jQuery('#co_events').focus();
    return false;
  }

  var response = grecaptcha.getResponse();
	if(response.length == 0)
	{
	alert("please verify you are humann!");
	return false;
	}

}
jQuery( document ).ready(function() {
	jQuery('.student_section').hide();

  jQuery('#general_membership').click(function() {
		jQuery('#finale_amount').val(250);
	});
  jQuery('#non_profit_government').click(function() {
		jQuery('#finale_amount').val(100);
	});
  jQuery('#young_professional_student').click(function() {
		jQuery('#finale_amount').val(100);
	});  

	jQuery('#general_membership').click(function() {
		jQuery('.student_section').hide();
	});
	jQuery('#non_profit_government').click(function() {
		jQuery('.student_section').hide(); 
	});
	jQuery('#young_professional_student').click(function() {
		jQuery('.student_section').show();
	});

	jQuery('#same_as_above').click(function() {
		jQuery('#company_address').val(jQuery('#address1').val());
		jQuery('#billing_city').val(jQuery('#city').val());
		jQuery('#billing_state').val(jQuery('#state').val());
		jQuery('#billing_postal_code').val(jQuery('#postal_code').val());
	});
}); 

function validateEmail(email)
{
  var re_mail = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z])+$/;
  if (!re_mail.test(email.value))
  {
    return false;
  }
  return true;
}
function phone_formate(e,id)
{
var flag=onlyNumbers_phone(e);
if(flag==false){
var re= /\D/;
var re2 = /^\({1}\d{3}\)\d{3}-\d{4}/; 
var nums=document.getElementById(id);
var num=nums.value;
var newNum;
 if (num != "" && re2.test(num)!=true){
  if (num != ""){
   while (re.test(num)){
   num = num.replace(re,"");
   }
  }
   newNum = '(' + num.substring(0,3) + ')'+" " + num.substring(3,6) + '-' + num.substring(6,10);
   nums.value=newNum;
  }
}
}
function onlyNumbers_phone(e) 
{
 var unicode=e.charCode? e.charCode : e.keyCode;
 if (unicode!=8 && unicode!=9 && unicode!=13 && unicode!=46 && unicode!=37 && unicode!=39)
 { 
  if (unicode<48||unicode>59 || unicode>48||unicode<57)
  {return false; }
 }
 return true;
}
function numbersonly(e)
{
 var unicode=e.charCode? e.charCode : e.keyCode;
   if (unicode!=8 && unicode!=9 && unicode!=13 && unicode!=40 && unicode!=37 && unicode!=38 && unicode!=39 && unicode!=46)
   { //if the key isn't the backspace key (which we should allow)
    if (unicode<48||unicode>57) //if not a number
    return false; //disable key press
   }
   return true;
}