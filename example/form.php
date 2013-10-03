<?php
session_start();
################################################################################
# Includes                                                                     #
################################################################################
if (file_exists("../form_process.class.inc.php")) { include ("../form_process.class.inc.php"); } else { echo 'failed to open form_process.class.inc.php'; exit;}
################################################################################
# fpc                                                                          #
################################################################################
if (file_exists("form.conf.php")) { include_once ("form.conf.php"); } else { echo 'failed to open form.conf.php'; exit;}
$refForm = new form_process();
$refForm->process($arrFormConf);
################################################################################
# Set boolean if submit button was clicked                                     #
################################################################################
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
	#$refForm->checkReferer();
	$refForm->checkValidation();
	$refForm->checkToken();
	$refForm->checkUserAgent($refForm->strRandomSeed);
	// add additional error checking here
	/*
	...Check Code...
	if(....error found...)
	{
		$refForm->boolError = true;
		$refForm->strErrorMsg .= ...addyourmessage here..."<br />\n";
	}
	*/
	############################################################################
	# If errors were found                                                     #
	############################################################################
	if ($refForm->returnErrors())
	{
		// process form submission data if errors for form reprint
		$refForm->processInputData();
		echo $refForm->strTemplate;
		#echo "arrFields : <pre>";print_r($refForm->arrRequest);echo "</pre><br />\n";
		#echo "Session : <pre>";print_r($_SESSION);echo "</pre><br />\n";
	}
	############################################################################
	# If No errors were found                                                  #
	############################################################################
	else
	{
		ob_start();
		echo '<div style="background-color:#efe; border:3px #060 solid; color:#060; padding:5px;">
	<div style="font-size:1.5em; font-weight:bold; padding-bottom:.5em;">data has been submitted!</div>';
		echo '	<pre>';print_r($_REQUEST);echo "</pre>\n\n";
		echo "</div>";
		$strBuffer = ob_get_contents();
		ob_end_clean();
		// resend form with a success message in error and original form.
		#$refForm->strErrorMsg .= $strBuffer;
		#$refForm->process($arrFormConf);
		#echo $refForm->strTemplateOrig;
		// resend with populated data and custom message in error message location.
		$refForm->strErrorMsg .= $strBuffer;
		$refForm->processInputData();
		echo $refForm->strTemplate;
		// resend with populated data and custom message in error message location and blank out a field.
		#$refForm->strErrorMsg .= $strBuffer;
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
	echo $refForm->strTemplateOrig;
}
#echo "<pre>REQUEST:\n"; print_r($_REQUEST); echo "</pre>";
#echo "<pre>"; print_r($refForm->arrFields); echo "</pre>";
#echo "<pre>SESSION:\n"; print_r($_SESSION); echo "</pre>";
?>
