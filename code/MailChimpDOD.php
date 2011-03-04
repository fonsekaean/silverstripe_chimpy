<?php

/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * Class: MailChimpDOD
 * Package: Chimpy          
 * New BSD License
 */

class MailChimpDOD extends DataObjectDecorator{
	
	function extraStatics(){
		return array(
			"db" => array(
				"MailchimpAPI" => "Text",
				"MailchimpEmail" => "Varchar"
			)
		);                      
	}
	
}