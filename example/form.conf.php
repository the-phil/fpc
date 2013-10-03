<?php
$arrFormConf = array(
	'form' => "form.tpl.html",
	'boolFormFile' => true,
	'validReferer' => array(
		'http://<server>/<formlocation>'
	),
	'fieldText' => array(
		'text1' => 'Text Field 1 ',
		'text2' => 'Text Field 2 ',
	),
	'fieldError' => array(
		'text1' => 'Text Field 1',
		'text2' => 'Text Field 2',
	),
	'validation' => array(
		'text1' => array(
			'required',
		),
		'text2' => array(
			'required',
		),
		'match' => array(
				'text1',
				'text2',
			),
	),
	'boolToken' => true,
	'tokenTimer' => '900',
	'boolUserAgent' => true,
	'RandomSeed' => '18ecee47145a72c0f457c545a9ded88f',
	'boolUsehtmlentitiesOnRequestData' => false,
	'boolUseConvertSmartQuotesOnRequestData' => false,
	// wrap field text with this
	'errorWrapper' => array(
		'0' => "\n".'<span style="color:#c00;">',
		'1' => "</span>\n"
	),
	// wrap error message with this
	'errorMsgWrapper' => array(
		'0' => "<br /><br />\n".'<div style="background-color:#ffffd5; border:3px #c00 solid; color:#c00; padding:5px;">'."\n\t".'<div style="font-size:1.5em; font-weight:bold; padding-bottom:.5em;">Error!</div>',
		'1' => "</div>\n"
	),
	// error messages for specific types of errors
	'errorMsg' => array(
		'captcha' => 'Captcha text does not match the image, use the reset checkbox to reset the image',
		'email' => 'The Email address entered is not valid please verify',
		'match' => 'The fields <b>[field-a]</b> and <b>[field-b]</b> do not match.',
		'referer' => 'Invalid Referer [referer]- You must use the same form provided by the host processing it',
		'required' => '<b>[field]</b> is a required field and cannot be blank',
		'token' => '<b>Invalid security token</b> Commonly caused by reloading the page, try resubmitting the form with the form buttons',
		'tokentime' => 'Security token ran out of time. Resubmit the form to solve this problem.',
		'useragent' => '<b>Possible session hijacking</b>, form submission has been halted.'
	)
);
?>
