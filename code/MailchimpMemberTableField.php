<?php

/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * Class: MailChimpMemberTableField
 * Package: Chimpy          
 * New BSD License
 */

class MailchimpMemberTableField extends TableListField{
    
	private $members;
	private $controllerMailchimp;

	function __construct($controller, $name, $members){
		$this->members = $members;
		$this->controllerMailchimp = $controller;
		parent::__construct($name, "MailchimpMemberTableField_Item", array("Email" => "Email"), $sourceFilter = null,$sourceSort = null, $sourceJoin = null);
	} 
	                                    
	function sourceItems(){
		return $this->members;
	}
	
	
	function Items(){
		$fieldItems = new DataObjectSet();
		if($items = $this->sourceItems()) foreach($items as $item) {
			$obj = new MailchimpMemberTableField_Item(); 
			$obj->Email = $item['email'];
			$fieldItem = new MailchimpMemberTableField_ListItem($obj, $this);
			if($obj) $fieldItems->push(new MailchimpMemberTableField_ListItem($obj, $this));
		}
		return $fieldItems;
	}
	
	function getController(){
		return $this->controllerMailchimp;
	}
	  
} 

class MailchimpMemberTableField_Item extends DataObject{
   	static $db = array(
		"Email" => "Varchar"
	);
} 


class MailchimpMemberTableField_ListItem extends TableListField_Item {
	
	function __construct($item, $parent) {
		parent::__construct($item, $parent);
	}

	function DeleteLink() {
		return $this->parent->getController()->Link() . "deletemember/?email=" . $this->item->Email;
	}
	
	
}