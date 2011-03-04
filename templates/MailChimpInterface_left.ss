<% if hasCredentials %>
<h2 class="headBtn" id="listsButton">Lists</h2>
<div id="MailchimpLists" style="display: none; padding: 3px;">
	<ul id="MailchimpListsUL"></ul>
</div> 


<h2 class="headBtn" id="campaignsButton">Campaigns</h2>	
<div id="MailchimpCampaigns" style="display: none;">
	<ul id="MailchimpCampaignsUL"></ul>
	<ul id="CampaignActions">
		<li id="addcampaign" class="action"><button>Create</button></li>
	</ul>
	<div class="clear">&nbsp;</div>
</div>


<h2 class="headBtn" id="credentialsButton">Mailchimp credentials</h2>
<div id="CredentialsForm" style="display: none;">
	<p>Edit your mailchimp credentials.</p> 
	<div  class="mailchimpForm">
		$CredentialForm
	</div>                                         
</div>


<% end_if %>                  


<div class="mailchimpForm">
	<% if hasCredentials %>

	<% else %>
		<h2>Mailchimp credentials</h2>    
		<div style="padding: 3px;">
			<p>Your Mailchimp credentials are not saved. Please add them first.</p> 
			<div>
				$CredentialForm
			</div>                   
		</div>
	<% end_if %>             
</div> 

<style type="text/css">
	.mailchimpForm form label.left { float: none; margin-left:0em; }
	.mailchimpForm form .field { margin-left: 0px; }
	.headBtn { cursor: pointer; } 
</style>