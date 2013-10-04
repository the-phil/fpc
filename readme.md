# PHP fpc
PHP **Form Process Class** is an attempt to simplify HTML form processing by taking a raw HTML form and automating the error handling.


### How it works
Take a simple HTML/XHTML/XML file with a *form* design and turn it into a dynamic form. 


### About
The original idea was created to allow easier creating of dynamic HTML/XHTML/XML forms. In large team development projects designers would use WYSIWYG editors to create the HTML/XHTML/XML form. A need for faster processing of new designs while keeping the business logic of the form in tact.


### Block Definition
A *block* is a string variable with two square brackets surrounding it. The *block* content will look like `[errorMsg]` or `[frmAction]`. 


# Example code
For more examples see the example folder.


### HTML form
A basic form with the `[errorMsg]` and `[frmAction]` blocks
form.tpl.html

```xml
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<body>

<form action="[frmAction]" method="post">
[errorMsg]
<br />
Text Field 1 :<br />
<input name="text1" type="text" size="40" value="" style="vertical-align:middle;" /><br />
<br />
Text Field 2 :<br />
<input name="text2" type="text" size="40" value="" /><br />
<br />
<input name="submit" type="submit" value="submit" />
</form>

</body>
</html>
```

### PHP Configuration file:
A configuration file using an array
form.conf.php

```php
<?php
$arrFormConf = array(
	'form' => "form.tpl.html", // name of HTML file above
	'boolFormFile' => true, // form can be file or string
	'fieldText' => array( // text that will get errorWrap if issue is found
		'text1' => 'Text Field 1 :',
		'text2' => 'Text Field 2 :',
	),
	'fieldError' => array( // text that will be displayed with error message regarding a field
		'text1' => 'Text Field 1',
		'text2' => 'Text Field 2',
	),
	'validation' => array( // built in error checking looks for required field
		'text1' => array(
			'required',
		),
	),
	'boolUsehtmlentitiesOnRequestData' => true, // wrap data with htmlentities to prevent xss
	'boolUseConvertSmartQuotesOnRequestData' => true, // convert smartquotes and word processing characters to html characters
	// wrap field text with this
	'errorWrapper' => array(
		'0' => "\n".'<span style="color:#c00;">',
		'1' => "</span>\n"
	),
	'errorMsgWrapper' => array( // wrap error message with this
		'0' => "<br /><br />\n".'<div style="background-color:#ffffd5; border:3px #c00 solid; color:#c00; padding:5px;">'."\n\t".'<div style="font-size:1.5em; font-weight:bold; padding-bottom:.5em;">Error!</div>',
		'1' => "</div>\n"
	),
	'errorMsg' => array( // customize your error messages for built in error checking
		'required' => '<b>[field]</b> is a required field and cannot be blank',
	)
);
?>
```

### PHP core program

form.php
```php
<?php
include_once ("form_process.class.inc.php"); // include class file
include_once ("form.conf.php"); // include configuration file
$refForm = new form_process(); // instantiate object
$refForm->process($arrFormConf); // load configuration and process
// determine if the form was submitted
if (array_key_exists('submit', $_REQUEST))
	$boolDataSent = true;
else
	$boolDataSent = false;
################################################################################
# If data was submitted                                                        #
################################################################################
if ($boolDataSent)
{
	############################################################################
	# Check for errors                                                         #
	############################################################################
	// run error checking
	$refForm->checkValidation();
	// add additional error checking here
	/*
	if(....error found...)
	{
		$refForm->boolError = true; // set class error flag
		$refForm->strErrorMsg .= ...addyourmessage here..."<br />\n"; // add custom error message
	}
	*/
	############################################################################
	# If errors were found                                                     #
	############################################################################
	if ($refForm->returnErrors())
	{
		// process form submission data, and reprint form populated with error message and error highlights
		$refForm->processInputData();
		echo $refForm->strTemplate;
	}
	############################################################################
	# If No errors were found                                                  #
	############################################################################
	else
	{
		$strSuccess = '<div style="background-color:#efe; border:3px #060 solid; color:#060; padding:5px;"><div style="font-size:1.5em; font-weight:bold; padding-bottom:.5em;">Success!</div>You submitted data to this form!</div>';
		// resend form with a success message in error and original form.
		$refForm->strErrorMsg .= $strSuccess;
		$refForm->process($arrFormConf);
		echo $refForm->strTemplateOrig;
		// resend with populated data and custom message in error message location.
		#$refForm->strErrorMsg .= $strSuccess;
		#$refForm->processInputData();
		#echo $refForm->strTemplate;
		// resend with populated data and custom message in error message location and blank out a field.
		#$refForm->strErrorMsg .= $strSuccess;
		#$refForm->arrRequest['text1'] = "";
		#$refForm->processInputData();
		#echo $refForm->strTemplate;
	}
}
################################################################################
# If data was not submitted                                                    #
################################################################################
else
{
	echo $refForm->strTemplateOrig; // display original form
}
?>
```
