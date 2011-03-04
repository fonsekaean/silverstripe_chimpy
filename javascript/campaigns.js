/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * File: campaigns.js
 * Package: Chimpy          
 * New BSD License
 */

jQuery(document).ready(function(){
	jQuery("#sendTestNewsletter").click(function(){
		
		if(jQuery("#Form_getCampaignForm_TestEmail").val() == ""){
			alert("Enter a test email");
			return;
		}
		
		jQuery.ajax({
			url: 'admin/mailchimp/sendcampaigntest',
			dataType: 'json',
			data: 'email=' + jQuery("#Form_getCampaignForm_TestEmail").val(),
			success: function(data){         
				if(data.success == 1){
					alert("Test send successfully.");
				}else{
					alert("Error while sending test.");
				}
			}
		});
		
	});
});