<?php
// -------------------------------------------------------------------
// German translation for the Mailchimp module
// -------------------------------------------------------------------

i18n::include_locale_file('mailchimp', 'en_US');

global $lang;

if(array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
	$lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
	$lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['SubscribeWidget']['TITLE'] = 'Newsletter Anmelden';
$lang['de_DE']['SubscribeWidget']['CMSTITLE'] = 'Newsletter Anmelden';
$lang['de_DE']['SubscribeWidget']['DESCRIPTION'] = 'Besucher können sich für den Mailchimp Newsletter anmelden.';
$lang['de_DE']['SubscribeWidget']['LISTLABEL'] = 'Liste auswählen';
$lang['de_DE']['SubscribeWidget']['NAMELABEL'] = 'Name';
$lang['de_DE']['SubscribeWidget']['EMAILLABEL'] = 'E-Mail';
$lang['de_DE']['SubscribeWidget']['BUTTONVALUE'] = 'Absenden';