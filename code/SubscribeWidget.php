<?php

class SubscribeWidget extends Widget {

    public function Title() {
        return _t('SubscribeWidget.TITLE', 'Newsletter Signup');
    }

    public function CMSTitle() {
        return _t('SubscribeWidget.CMSTITLE', 'Newsletter Signup');
    }

    public function Description() {
        return _t('SubscribeWidget.DESCRIPTION', 'Let your users to subscribe to the mailchimp newletter.');
    }

    public static $db = array(
        'ListID' => 'Varchar(20)'
    );

    public function getCMSFields() {
        $config = DataObject::get_one("SiteConfig");
        $api = new MCAPI($config->MailchimpAPI);
        $lists = $api->lists();
        $listsForDropDown = array(
            "0" => _t('SubscribeWidget.LISTLABEL', 'Select a list')
        );
        if ($lists) {
            foreach ($lists as $list) {
                $listsForDropDown[$list['id']] = $list['name'];
            }
        }
        return new FieldSet(new DropdownField('ListID', _t('SubscribeWidget.LISTLABEL', 'Select a list'), $listsForDropDown));
    }

    /* function SubscribeForm(){

      } */
}

class SubscribeWidget_Controller extends Widget_Controller {

    function SubscribeForm() {
        $config = DataObject::get_one("SiteConfig");
        if ($this->ListID) {
            $listField = new HiddenField('List', 'List', $this->ListID);
        } else {
            $api = new MCAPI($config->MailchimpAPI);
            $lists = $api->lists();
            $listsForDropDown = array(
                "0" => _t('SubscribeWidget.LISTLABEL', 'Select a list')
            );
            if ($lists) {
                foreach ($lists as $list) {
                    $listsForDropDown[$list['id']] = $list['name'];
                }
            }
            $listField = new DropdownField('List', 'List', $listsForDropDown);
        }

        return new Form(
                        $this,
                        'SubscribeForm',
                        new FieldSet(
                                new TextField('Name', _t('SubscribeWidget.NAMELABEL', 'Name')),
                                new EmailField('Email', _t('SubscribeWidget.EMAILLABEL', 'Email')),
                                $listField
                        ),
                        new FieldSet(
                                new FormAction('doAction', _t('SubscribeWidget.BUTTONVALUE', 'Subscribe'))
                        ),
                        new RequiredFields(
                                'Name', 'Email', 'List'
                        )
        );
    }

    function doAction($data, $form) {
        $config = DataObject::get_one("SiteConfig");
        $api = new MCAPI($config->MailchimpAPI);
        $merge_vars = array('FNAME' => $data['Name'], 'LNAME' => '', 'INTERESTS' => '');
        $retval = $api->listSubscribe($data['List'], $data["Email"], $merge_vars);
        Director::redirectBack();
    }

}