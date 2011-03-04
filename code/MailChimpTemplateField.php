<?php

/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * Class: MailChimpTemplateField
 * Package: Chimpy          
 * New BSD License
 */

class MailChimpTemplateField extends FormField{
	
	private $templates;
	function __construct($name, $title = null, $templates, $value = null, $form = null, $rightTitle = null) {
		$this->templates = $templates;
		parent::__construct($name, $title, $value, $form, $rightTitle);
		
	}	
	
	
	
	function Field() {
		$html = "<ul class='templateList'>";
		foreach($this->templates as $template){
			$imageURL = "mailchimp/images/no-image.jpg";	
			if($template['preview_image'])
				$imageURL = $template['preview_image'];
			$html.= "<li id='" . $template['id'] . "'><img src='$imageURL' /><p><strong>Name: </strong>". $template['name'] ."<br /><strong>Layout: </strong>".$template['layout'] . "</p></li>";
		}
		$html.= "</ul>";
		return $html;
	}	
	
}