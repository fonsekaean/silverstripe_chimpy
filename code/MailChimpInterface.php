<?php

/**
 * SilverStripe Chimpy - MailChimp integration for SilverStripe
 * creator - nivanka [at] silverstripers.com
 * Class: MailChimpInterface
 * Package: Chimpy          
 * New BSD License
 */

class MailChimpInterface extends LeftAndMain{
	
	static $url_segment = 'mailchimp';
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	static $menu_title = 'Mailchimp';
	
	static $import = array(
		"Unknown" => "Unknown",
		"Email" => "Email",
		"FirstName" => "First Name",
		"LastName" => "Last Name",
		"Interests" => "Interests"
	); 
	
	function init(){
		parent::init();
		Requirements::javascript("mailchimp/javascript/Mailchimp.js");
		Requirements::css("mailchimp/css/MailChimp.css");
		if(isset($_GET['height'])){
			Session::set("FrameHeight", $_GET['height']);
		}
	}
	
	function intro(){
		echo "<h1>Mailchimp</h1>";
		die();
	}                                                   
	
	function FrameHeight(){
		return Session::get("FrameHeight") . "px";
	}
	
	/**
	 * Check whether the user has entered his mailchimp credentials
	 */
	function hasCredentials(){
		if($config = DataObject::get_one("SiteConfig")){
			if($config->MailchimpAPI != "" && $config->MailchimpEmail != "")
				return true;
		}
		return false;
	} 
	
	/**
	 * create and return a form for mailchimp user credentials
	 */                                                       
	function CredentialForm(){
		$api = "";                          
		$email = "";
		if($config = DataObject::get_one("SiteConfig")){
			$api = $config->MailchimpAPI;
			$email = $config->MailchimpEmail;
		}                                          
		$fields = new FieldSet(
			new TextField("MailchimpAPI", "API Key",$api),
			new TextField("MailchimpEmail", "API Email",$email)
		);
      	$actions = new FieldSet(
         	new FormAction('updateCredentials', 'Submit')
      	);
        return new Form($this, 'CredentialForm', $fields, $actions);
	}
	
	/**
	 * save the usercredentials
	 */                        
	function updateCredentials($data, $form){
		if($config = DataObject::get_one("SiteConfig")){
			$config->MailchimpAPI = $data['MailchimpAPI'];
			$config->MailchimpEmail = $data['MailchimpEmail'];
			$config->write();
		}                    
		Director::redirectBack();
	}
	
	/**
	 * get all the lists from mailchimp
	 */                                
	function lists(){                                            
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI); 
		$lists = $api->lists(); 
		if ($api->errorCode){   
			return Convert::array2json(array(
				"Error" => 1,
				"ErrorMessage" => $api->errorMessage,
				"ErrorCode" => $api->errorCode
		    ));
		}
		return Convert::array2json($lists); 
	}                           
	
	
	function viewlist(){                
		$params = Director::urlParams();
		Session::set("listID", $params["ID"]);
		Session::set("type", "list");
		
		return $this->renderWith("MailChimpAction");
	}
	
	/**
	 * generates the forms as per the request types
	 */
	function Form(){
		if(Session::get("type") == "list"){
			return $this->getListForm();
		}
		if(Session::get("type") == "campaign"){
			return $this->getCampaignForm();
		}
	}
	
	
	/**
	 * returns a form with a table list field implementation
	 */
	function getListForm(){ 
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI); 
		$listMembers = $api->listMembers(Session::get("listID")); 
		$tabSet = new TabSet("List", "List");
		$tab = new Tab("Subscribers", "Subscribers");
		$tabSet->push($tab);
		$tab->push(new MailchimpMemberTableField($this, "Subscribers", $listMembers)); 
		$tab = new Tab("Add", "Add Member");
		$tabSet->push($tab);
		$tab->push(new EmailField("Email", "Email"));
		$tab->push(new TextField("FirstName", "First Name"));
		$tab->push(new TextField("LastName", "Last Name"));
		$tab = new Tab("Import", "CSV Import / Export");
		$tabSet->push($tab);
		if(Session::get("EmailField") == 1){
			$tab->push(new LiteralField("Message", "<p>Please select the email field</p>"));
		}
		$tab->push(new FileField("CSV", "CSV File"));		
		
      	$actions = new FieldSet(
         	new FormAction("saveList", "Save")
      	);
		return new Form($this, 'getListForm', new FieldSet($tabSet), $actions);
		return $tabSet;
	} 
	
	
	/**
	 * save a list
	 * subscribe, and import from CSV
	 */                              
	function saveList($data, $form){
		if($data["Email"] != "" && $data["FirstName"] != "" && $data["LastName"] != ""){
			$config = DataObject::get_one("SiteConfig");
			$api = new MCAPI($config->MailchimpAPI);
			$merge_vars = array('FNAME'=>$data["FirstName"], 'LNAME'=>$data["LastName"],'INTERESTS'=>'');
			$retval = $api->listSubscribe( Session::get("listID"), $data["Email"], $merge_vars );
		}
		
		if($_FILES['CSV']['tmp_name']){
			$tempFile = fopen( $_FILES['CSV']['tmp_name'], 'r' );
			if(!$tempFile) {
			  return 'The selected file did not arrive at the server';
			}
			fclose( $tempFile );
			return $this->renderWith( 'MailChimpCSVImport' );
		}
		
		Director::redirectBack();
	}
	
	/**
	 * confirming the Email field of the CSV file.
	 * Return a form to create the array to go
	 */
	function ImportForm(){
		$tabSet = new TabSet("Import", "Import");
		$tab = new Tab("Verify", "Verify");
		$tabSet->push($tab); 
		if(isset($_FILES['CSV'])){
			$tempFile = fopen( $_FILES['CSV']['tmp_name'], 'r' );
			if(!$tempFile) {
		  		return 'The selected file did not arrive at the server';
			}  
			$html = "<table>";
			$count = 0;
			while( ( $row = fgetcsv( $tempFile ) ) !== false ) { 
				if($count != 0){
					$html.= "<tr>";
					foreach($row as $name => $value){
						$html.= "<td>$value <input type='hidden' value='$value' name='Field_{$name}[]' /></td>";
					}   
					$html.= "</tr>";
		    	}else{
					$html.= "<tr>";                  
					Session::set("CSVCols", count($row));
					foreach($row as $name => $value){
						$html.= "<td><select name='Title_{$name}' />";
							foreach(self::$import as $name => $value){
								$html.= "<option value='$name' >$value</option>";
							}
						$html.= "</select></td>";
					}
					$html.= "</tr>";
				}
				$count = $count + 1; 
			}              
			$html.= "</table>"; 
			Session::set("CSVRows", $count - 1);
			fclose( $tempFile );
			$tab->push(new LiteralField("Importer", $html)); 
	    } 
      	$actions = new FieldSet(
         	new FormAction("import", "Import")
      	);
		return new Form($this, 'ImportForm', new FieldSet($tabSet), $actions);	
	}  
	
	/** 
	 * Real meat, create an array of subscribers from the list.
	 * Send them to the mailchimp
	 */
	function import(){           
		// get the fields to read
		$email = "";
		$firstname = "";
		$lastname = "";
		$interests = "";         
		for($i = 0; $i < Session::get("CSVCols"); $i++){
			if(isset($_REQUEST["Title_{$i}"])){
				if($_REQUEST["Title_{$i}"] == "Email"){
					$email = "Field_{$i}";
				}elseif($_REQUEST["Title_{$i}"] == "FirstName"){
					$firstname = "Field_{$i}";
				}elseif($_REQUEST["Title_{$i}"] == "LastName"){                
					$lastname = "Field_{$i}";
				}elseif($_REQUEST["Title_{$i}"] == "Interests"){                
					$interests = "Field_{$i}";
				}
			}
		} 
		
		// if email is not given no point in going ahead
		if($email == ""){
			Session::set("EmailField", 1);
			return Director::redirect($this->Link() . "viewlist/" . Session::get("listID") );
		}else{
			Session::set("EmailField", 0);
		}
		// adding the subscribers to the array
		$subscribers = array();
		for($i = 0; $i < Session::get("CSVRows"); $i++){
			$fname = $firstname ? $_REQUEST[$firstname][$i] : "";
			$lname = $lastname ? $_REQUEST[$lastname][$i] : ""; 
			$interests = $interests ? $_REQUEST[$interests][$i] : "";
			$subscribers[] = array(
				'EMAIL' => $_REQUEST[$email][$i], 
				'FNAME' => $fname,
				'LNAME' => $lname, 
				'INTERESTS' => $interests
		    );
		} 
		
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);		
		$vals = $api->listBatchSubscribe(Session::get("listID"),$subscribers,true, true, true);
		return Director::redirect($this->Link() . "viewlist/" . Session::get("listID") );
	}
	
	
	/**
	 * delete a member from a list
	 */                           
	function deleteMember(){ 
		if(!isset($_REQUEST['email']))
			return false;
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);         
		$api->listUnsubscribe(Session::get("listID"), $_REQUEST['email']);		
	}
	
	
	/**
	 * get all the campaigns from mailchimp
	 */                                
	function campaigns(){                                            
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI); 
		$lists = $api->campaigns(); 
		if ($api->errorCode){   
			return Convert::array2json(array(
				"Error" => 1,
				"ErrorMessage" => $api->errorMessage,
				"ErrorCode" => $api->errorCode
		    ));
		}
		return Convert::array2json($lists); 
	}                           
	
	
	function viewcampaign(){   
		if(Session::get("CreateCampaign") == 1){
			Session::set("CreateCampaign", 0);
		    Requirements::customScript('                                    
				jQuery(document).load(function(){
					jQuery("#campaignsButton", window.parent.document).trigger("click");
				});
'
);         
		}  
		Requirements::javascript('mailchimp/javascript/campaigns.js');           
		$params = Director::urlParams();
		Session::set("campaignID", $params["ID"]);
		Session::set("type", "campaign"); 
		return $this->renderWith("MailChimpAction");
	}
	
	function getCampaignForm(){
		$params = Director::urlParams();
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);
		$lists = $api->lists();
		
		$listData = array();
		
		foreach($lists as $list){
			$listData[$list["id"]] = $list["name"];
		}
		
		$title = "";
		$subject = "";
		$fromEmailA = "";
		$fromEmailB = "";
		$fromNameA = "";
		$fromNameB = "";
		$google = "";
		$list = "";
		$html = "";
		$plain = "";
		$status = "";
		
		if(strcmp(Session::get("campaignID"), "0") != 0){
			$campaign = $api->campaignContent(Session::get("campaignID"));
			$html = $campaign["html"];
			$plain = $campaign["text"];                                    
			
			$campaign = $api->campaigns(array(
				"campaign_id" => Session::get("campaignID")
			)); 
			
			//foreach($campaign[0] as $name => $val)
			//	echo "<p>$name => $val</p>";
			
			$list = $campaign[0]['list_id'];
			$title = $campaign[0]['title'];
			$fromEmailA = $campaign[0]['from_email'];
			$fromNameA = $campaign[0]['from_name'];
			$subject = $campaign[0]['subject'];
			$google = $campaign[0]['analytics_tag'];
			$status = $campaign[0]['status'];
		}
		
		//$config = DataObject::get_one("SiteConfig");
		//$api = new MCAPI($config->MailchimpAPI); 
		$listMembers = $api->listMembers(Session::get("listID")); 
		$tabSet = new TabSet("List", "List");
		$tab = new Tab("Settings", "Settings");
		$tabSet->push($tab);            
		if(Session::get("CampaignError") != 0){             
			$tab->push(new LiteralField("Error", "<p>" . Session::get("CampaignError") . "</p>"));
		}
		if(strcmp(Session::get("campaignID"), "0") != 0){
			$tab->push(new ReadonlyField("Status", "Status", $status));
		}
		$tab->push(new TextField("Title", "Title", $title));
		$tab->push(new TextField("Subject", "Subject", $subject));
		$tab->push(new DropdownField("List", "List", $listData, $list));
		$tab->push(new TextField("FromEmailA", "From Email"), $fromEmailA);
		$tab->push(new TextField("FromNameA", "From Name", $fromNameA));
	   // $tab->push(new TextField("FromEmailB", "From Email (B)", $value = $fromEmailB));
	   // $tab->push(new TextField("FromNameB", "From Name (B)", $fromNameB));
		$tab = new Tab("Content", "Content");
		$tabSet->push($tab);                                       
		$tab->push(new TextareaField("HTMLContent", "HTML Content", 50, 60, $value = $html));
		$tab->push(new TextareaField("PlainContent", "Plain Content", 50, 60, $value = $plain));
		$tab = new Tab("Templates", "Templates");
		$tabSet->push($tab);  
		
		// get the templates
		$templates = $api->campaignTemplates();
		$template = 0;
		if(count($templates) > 0){
			$tab->push(new MailChimpTemplateField("Templates", "Available Template", $templates, $template));	
		}
		
		// scheduling
		$tab = new Tab("Schedule", "Schedule");
		$tabSet->push($tab);
		$tab->push($dateField = new DatetimeField("ScheduleDate", "ScheduleDate"));
		$dateField->getDateField()->setConfig('showcalendar', true);
		$dateField->getDateField()->setLocale("en_US");
		$dateField->getDateField()->setConfig('dateformat', 'dd/MM/YYYY'); 
		$dateField->getTimeField()->setConfig('showdropdown', true);
		
		$tab->push($dateOptionField = new DatetimeField("ScheduleDate_Option", "ScheduleDate (Optional)"));
		$dateOptionField->getDateField()->setConfig('showcalendar', true);
		$dateOptionField->getDateField()->setLocale("en_US");
		$dateOptionField->getDateField()->setConfig('dateformat', 'dd/MM/YYYY');
		$dateOptionField->getTimeField()->setConfig('showdropdown', true);
		$tab->push(new CheckboxField("UnSchedule", "Unschedule campaign"));
		
		$tab = new Tab("Analytics", "Analytics");
		$tabSet->push($tab);
		$tab->push(new TextField("GoogleAnalytics", "Google Analytics Key", $google));
		
		if(strcmp(Session::get("campaignID"), "0") != 0){
			$tab = new Tab("Send", "Sending Options");
			$tabSet->push($tab);
			$tab->push(new TextField("TestEmail", "Test Email"));
			$tab->push(new LiteralField("SendTest", "<input type='button' id='sendTestNewsletter' value='Send test' />"));
		}
		
		
		
		// if the campaign status is sent, 
		// show a status tab
		// this uses google CHARTS API HEAVLIY
		if(strcmp($status, "sent") == 0){                           
			$tab = new Tab("Stats", "Stats");
			$campaignStatsJS = "";
			$stats = $api->campaignStats(Session::get("campaignID"));
			$clicks = $api->campaignClickStats(Session::get("campaignID"));
			$geo = $api->campaignGeoOpens(Session::get("campaignID"));
			if(!$api->errorCode){ 
				$opens = $stats['unique_opens'];
				$left = $stats['emails_sent'] - $opens;
				$hBounces = $stats['hard_bounces'];
				$sBounces = $stats['soft_bounces'];
				
				$campaignStatsJS .= <<<JS
					function drawCharts(){
						campaignOpenStats();
						campaignClicks();
						campaignGeo();
					}
					function campaignOpenStats() {
					  var data = new google.visualization.DataTable();
					  data.addColumn('string', 'Title');
					  data.addColumn('number', 'Campaigns');
					  data.addRows(5);
					  data.setValue(0, 0, 'Opened');
					  data.setValue(0, 1, $opens);
					  data.setValue(1, 0, 'Left Behind');
					  data.setValue(1, 1, $left);
					  data.setValue(2, 0, 'Hard Bounces');
					  data.setValue(2, 1, $hBounces);
					  data.setValue(3, 0, 'Soft Bounces');
					  data.setValue(3, 1, $sBounces);

					  // Create and draw the visualization.
					  new google.visualization.PieChart(document.getElementById('Campaign_Opens')).
					      draw(data, {width: 400, height: 240, is3D: true, title:"Basic Statistics"});
					}
JS
;
                $clickRows = "[";
                foreach($clicks as $url => $data){
					$clickRows .= "['$url', " . $data['clicks'] . ", " . $data['unique'] . "],";
				}
				
				$clickRows = substr($clickRows, 0, -1);             
				$clickRows .= "]";
				//echo $clickRows;
				$campaignStatsJS .= <<<JS
					function campaignClicks() {
				  		var data = new google.visualization.DataTable();
					  	data.addColumn('string', 'URL');
						data.addColumn('number', 'Clicks');
						data.addColumn('number', 'Unique');
						data.addRows($clickRows);

						// Create and draw the visualization.
						new google.visualization.LineChart(document.getElementById('Clicks')).
						draw(data, {width: 400, height: 240, is3D: true, title:"Clicks"});
					}
JS
;             		
			    
                $geoJS = "[";
                foreach($geo as $geoCont){
					$geoJS .= "['" . $geoCont['name'] . "', " . $geoCont['opens'] . "],";
				}
				
				$geoJS = substr($geoJS, 0, -1);             
				$geoJS .= "]";
				$campaignStatsJS .= <<<JS
					function campaignGeo() {
				  		var data = new google.visualization.DataTable();
					  	data.addColumn('string', 'Country');
						data.addColumn('number', 'Opens');
						data.addRows($geoJS);

						// Create and draw the visualization.
						var geoMap = new google.visualization.GeoMap(document.getElementById('GEOCountry'));
						geoMap.draw(data, {
							width: 400, 
							height: 250, 
							title:"Clicks", 
							dataMode: 'regions'
						});    
						
						google.visualization.events.addListener(geoMap, 'regionClick', function(obj){
							jQuery.ajax({
								url: 'admin/mailchimp/regiondata',
								dataType: 'json',
								data: 'region=' + obj.region,
								success: function(data){         
									if(data.success == 1){
										
								  		var dataR = new google.visualization.DataTable();
									  	dataR.addColumn('string', 'Country');
										dataR.addColumn('number', 'Opens');
										dataR.addRows(data.data);
										region = new google.visualization.GeoMap(document.getElementById('GEORegion'));
										region.draw(dataR, {
											width: 400, 
											height: 250,
											title:"Clicks",
											region: obj.region, 
											dataMode: 'markers'
										});										
										
									}else{
										alert("Error while retrieving data.");
									}
								}
							});
						});
						
					}
JS
;             		
			
			
			
			}
			
			if ($api->errorCode){
				$tab->push(new LiteralField("ErrorStats", "<p>Sorry! I am unable to retrieve statistics for the campaign at this time. " . $api->errorMessage . "</p>"));
			}
			else{
				Requirements::javascript("http://www.google.com/jsapi");
				Requirements::customScript(<<<JS
					google.load('visualization', '1', {'packages':['piechart', 'linechart', 'geomap']});
					$campaignStatsJS
					google.setOnLoadCallback(drawCharts);
JS
);
				$tab->push(new LiteralField('OpensCharts', '<div class="statHolder"><h1>Campaign Statistics</h1><div id="Campaign_Opens" class="box"></div><div id="Clicks" class="box"></div><div class="box"><div id="GEOCountry"></div><p>Opens over regions. Click to retrieve more infomation.</p></div><div id="GEORegion" class="box"></div></div>'));
			                 
			}   
			$tabSet->push($tab); 
		}
		
		$action = "updateCampaign";
		if(strcmp(Session::get("campaignID"), "0") == 0){
			$action = "createCampaign";
		}
      	$actions = new FieldSet(
         	new FormAction($action, "Save"),
			new FormAction("sendNewsletter", "Send"),
			new FormAction("deleteCampaign", "Delete")
      	);
		return new Form($this, 'getCampaignForm', new FieldSet($tabSet), $actions);
		return $tabSet;   
	}
	
	function createCampaign($data, $form){      
		Session::set("CreateCampaign", 1);
		Session::set("CampaignError", 0);
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);                         
		$type = 'regular';
		$opts['list_id'] = $data['List'];
		$opts['subject'] = $data['Subject'];
		$opts['from_email'] = $data['FromEmailA']; 
		$opts['from_name'] = $data['FromNameA'];

		$opts['tracking']=array('opens' => true, 'html_clicks' => true, 'text_clicks' => false);

		$opts['authenticate'] = true;
		$opts['analytics'] = array( 'google'=> $data['GoogleAnalytics'] );
		$opts['title'] = $data['Title'];

		$content = array(
			'html'=> $data['HTMLContent'], 
			'text' => $data['PlainContent']
		);     
		$campaignId = $api->campaignCreate($type, $opts, $content); 
		                                         
		if ($api->errorCode){        
		   Session::set("CreateError", $api->errorMessage);
		   return Director::redirect($this->Link() . "viewcampaign/0");
		} 
		Session::set("campaignID", $campaignId);
		Director::redirect($this->Link() . "viewcampaign/" . $campaignId);
		
	}
	
	function updateCampaign($data, $form){ 
		Session::set("CampaignError", 0);               
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);
		
		$api->campaignUpdate(Session::get("campaignID"), "title", $data['Title']);
		$api->campaignUpdate(Session::get("campaignID"), "from_email", $data['FromEmailA']);
		$api->campaignUpdate(Session::get("campaignID"), "from_name", $data['FromNameA']);
		$api->campaignUpdate(Session::get("campaignID"), "subject", $data['Subject']);
		$api->campaignUpdate(Session::get("campaignID"), "content", array(
			"text" => $data['PlainContent'],
			"html_main" => $data['HTMLContent']
		));
		$api->campaignUpdate(Session::get("campaignID"), "list_id", $data['List']);
		
		if($data["GoogleAnalytics"] != ""){
			$api->campaignUpdate(Session::get("campaignID"), "analytics",  array(
				'google'=> $data["GoogleAnalytics"]
		    ));
		} 
		if ($api->errorCode){        
		   Session::set("CreateError", $api->errorMessage);
		   return Director::redirect($this->Link() . "viewcampaign/" . Session::get("campaignID"));
		}
		
		$schedule = false;
		$optional = false;
		if(!empty($data['ScheduleDate']['date']) && !empty($data['ScheduleDate']['time'])){
			$date_split = explode('/', $data['ScheduleDate']['date']);
			$schedule = $date_split[2] . "-" . $date_split[1] . "-" . $date_split[0] . " " . substr($data['ScheduleDate']['time'], 0, 5) . ":00";	
			if(!empty($data['ScheduleDate_Option']['date']) && !empty($data['ScheduleDate_Option']['time'])){
				$date_split = explode('/', $data['ScheduleDate_Option']['date']);
				$optional = $date_split[2] . "-" . $date_split[1] . "-" . $date_split[0] . " " . substr($data['ScheduleDate_Option']['time'], 0, 5) . ":00";	
			}
		}
		if($schedule){
			if($optional){
				$api->campaignSchedule(Session::get("campaignID"), $schedule, $optional);
			}else{
				$api->campaignSchedule(Session::get("campaignID"), $schedule);
			}
		}elseif(isset($data['UnSchedule'])){
			$api->campaignUnschedule(Session::get("campaignID"));	
		}
		
		Director::redirectBack();
	}
	
	/**
	 * Delete campaign function
	 */
	function deleteCampaign(){
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);
		$api->campaignDelete(Session::get("campaignID"));
		Session::set("campaignID", 0);
		Director::redirect($this->Link() . "intro/");
	}
	
	/**
	 * send the newsletter
	 */
	function sendNewsletter($data, $form){
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);
		$api->campaignSendNow(Session::get("campaignID"));
		Director::redirectBack();		
	}
	
	function sendcampaigntest(){
		$config = DataObject::get_one("SiteConfig");
		$api = new MCAPI($config->MailchimpAPI);    
		$emails = array($_REQUEST['email']);
		$api->campaignSendTest(Session::get("campaignID"), $emails);
		if ($api->errorCode){
			return Convert::array2json(array("success" => 1));
		}
		return Convert::array2json(array("success" => 1));		
	} 
	
	/**
	 * for campaign statistics
	 */
	function regiondata(){
		//Requirements::();
		if(isset($_REQUEST['region'])){         
			$config = DataObject::get_one("SiteConfig");
			$api = new MCAPI($config->MailchimpAPI);
			$regions = $api->campaignGeoOpensForCountry(Session::get("campaignID"), $_REQUEST['region']);
			$outout = array();
			if(!$api->errorCode){           
				$regionArray = array();
				foreach($regions as $region){
					$data = array();      
					$data[] = $region['name'];
					$data[] = $region['opens'];
					$regionArray[] = $data;
				}                     
				$output['success'] = 1;
				$output['data'] = $regionArray;
			}else{
				$output['success'] = 0;
				$output['msg'] = $api->errorCode;
			}                        
			return Convert::array2json($output);
		}
	}
	
	
}