/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * File: Mailchimp.js
 * Package: Chimpy          
 * New BSD License
 */

jQuery("document").ready(function(){
	
	/**
	 * show / hide the credentials form
	 */
	jQuery("#credentialsButton").toggle(
		function(){ 
			jQuery("#CredentialsForm").slideDown("slow");
			jQuery("#MailchimpCampaigns").slideUp("slow");
			jQuery("#MailchimpLists").slideUp("slow"); 
		},
		function(){ 
			jQuery("#CredentialsForm").slideUp("slow"); 
		}
  	); 

	/**
	 * load lists
	 */          
	jQuery("#listsButton").toggle(
		loadLists,
		closeLists
	); 
	
	
	/**
	 * load lists
	 */          
	jQuery("#campaignsButton").toggle(
		loadCampaigns,
		closeCampaings
	);
	
	jQuery("#addcampaign").click(createNewCampaign);
	
		
	
}); 


function loadLists(){
	jQuery.ajax({
		url: 'admin/mailchimp/lists',
		dataType: 'json',
		success: function(data){         
			$list = jQuery("#MailchimpListsUL");
			$list.html('');
			jQuery.each(data, function(){
				$list.append("<li><a href='admin/mailchimp/#"+ this.id +"' id='" + this.id + "'>" + this.name + "</a></li>");
			});
			
			$list.find("a").click(viewList);
		}
	}); 
	jQuery("#CredentialsForm").slideUp("slow");
	jQuery("#MailchimpCampaigns").slideUp("slow");
	jQuery("#MailchimpLists").slideDown("slow");
} 

function viewList(){ 
	height = jQuery("#right").height();
	jQuery("#right").html("<iframe src='admin/mailchimp/viewlist/"+ jQuery(this).attr('id') + "?height="+ height +"' width='100%' height='" + height + "px' scrolling='yes' style='overflow-x: hidden;' ></iframe>");
}  

function closeLists(){                          
	jQuery("#MailchimpLists").slideUp("slow");
}   


function loadCampaigns(){
	jQuery.ajax({
		url: 'admin/mailchimp/campaigns',
		dataType: 'json',
		success: function(data){         
			$list = jQuery("#MailchimpCampaignsUL");
			$list.html('');
			jQuery.each(data, function(){
				$list.append("<li><a href='admin/mailchimp/#"+ this.id +"' id='" + this.id + "'>" + this.title + "</a></li>");
			});
			
			$list.find("a").click(viewCampaign);
		}
	}); 
	jQuery("#CredentialsForm").slideUp("slow");
	jQuery("#MailchimpLists").slideUp("slow"); 
	jQuery("#MailchimpCampaigns").slideDown("slow");   
}   

function closeCampaings(){
	jQuery("#MailchimpCampaigns").slideUp("slow");
}   

function viewCampaign(){
	height = jQuery("#right").height();
	jQuery("#right").html("<iframe src='admin/mailchimp/viewcampaign/"+ jQuery(this).attr('id') + "?height=" + height + "' width='100%' height='" + height + "px' scrolling='yes' style='overflow-x: hidden;' ></iframe>");	
}

function createNewCampaign(){
	height = jQuery("#right").height();
	jQuery("#right").html("<iframe src='admin/mailchimp/viewcampaign/0?height=" + height + "' width='100%' height='" + height + "px' scrolling='yes' style='overflow-x: hidden;' ></iframe>");
}

