<?php

class SubscribeWidget extends Widget{

	static $title = "Newsletter Signup";
	static $cmsTitle = "Newsletter Signup";
	static $description = "Let your users to subscribe to the mailchimp newletter.";
    
	public static $db = array(
		'ListID' => 'Varchar(20)'
	);  
	
	public function getCMSFields(){
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);
		$lists = $api->lists();
		$listsForDropDown = array(
			"0" => "Select a list"
		);
		if($lists){
			foreach($lists as $list){
				$listsForDropDown[$list['id']] = $list['name'];
			}
		}
		return new FieldSet(new DropdownField('ListID', 'List', $listsForDropDown));
	}

	/*function SubscribeForm(){

	}*/
	
}

class SubscribeWidget_Controller extends Widget_Controller {


	function SubscribeForm() {
  		$config = DataObject::get_one("SiteConfig");
		if($this->ListID){
			$listField = new HiddenField('List', 'List', $this->ListID);
		}	
		else{
			$api = new MCAPI($config->MailchimpAPI); 
			$lists = $api->lists(); 
			$listsForDropDown = array(
				"0" => "Select a list"
			);
			if($lists){
				foreach($lists as $list){
					$listsForDropDown[$list['id']] = $list['name'];
				}
			}  
			$listField = new DropdownField('List', 'List', $listsForDropDown);
		}   
		
		return new Form(
		      $this, 
		      'SubscribeForm', 
		      new FieldSet(
				new TextField('Name'),
				new EmailField('Email'),
				$listField
		      ), 
		      new FieldSet(
				new FormAction('doAction', 'Subscribe')
		      ),
		      new RequiredFields(
		      		'Name', 'Email', 'List'		
		      )
		);
  	}
 
	function doAction($data, $form) {
  		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI); 
		$merge_vars = array('FNAME'=>$data['Name'], 'LNAME'=>'','INTERESTS'=>'');
		$retval = $api->listSubscribe( $data['List'], $data["Email"], $merge_vars );
		Director::redirectBack();
	}
}