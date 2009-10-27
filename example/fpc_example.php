<?php
################################################################################
# File Name : fpc_example.php                                                  #
# Author(s) :                                                                  #
#   Phil Allen phil@hilands.com                                                #
# Last Edited By :                                                             #
#   phil@hilands.com                                                           #
# Version : 2009101300                                                         #
#                                                                              #
# Copyright :                                                                  #
#   This file is a php form input test script                                  #
#   Copyright (C) 2005 Philip J Allen                                          #
#                                                                              #
#   This file is free software; you can redistribute it and/or modify          #
#   it under the terms of the GNU General Public License as published          #
#   by the Free Software Foundation; either version 2 of the License,          #
#   or (at your option) any later version.                                     #
#                                                                              #
#   This File is distributed in the hope that it will be useful,               #
#   but WITHOUT ANY WARRANTY; without even the implied warranty of             #
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              #
#   GNU General Public License for more details.                               #
#                                                                              #
#   You should have received a copy of the GNU General Public License          #
#   along with This File; if not, write to the Free Software                   #
#   Foundation, Inc., 51 Franklin St, Fifth Floor,                             #
#   Boston, MA  02110-1301  USA                                                #
#                                                                              #
# External Files:                                                              #
#                                                                              #
# General Information (algorithm) :                                            #
#   Created to read a plain local file or read a dynamic page from http://     #
#   used for basic validation and data return to form on errors. this file     #
#   acts as the form processor using the form process class and configuration  #
#   file. Configuration file must be edited for the html form file, referer    #
#   and any validations.                                                       #
#                                                                              #
#   Email processing in the class does not work at this point                  #
#                                                                              #
# Functions :                                                                  #
#                                                                              #
# Classes :                                                                    #
#                                                                              #
# CSS :                                                                        #
#   should not be in this file only in template file                           #
#                                                                              #
# JavaScript :                                                                 #
#   should not be in this file only in template file                           #
#                                                                              #
# Variable Lexicon :                                                           #
#   String             - $strStringName                                        #
#   Array              - $arrArrayName                                         #
#   Resource           - $resResourceName                                      #
#   Reference Variable - $refReferenceVariableName  (aka object)               #
#   Integer            - $intIntegerName                                       #
#   Boolean            - $boolBooleanName                                      #
#   Function           - function_name (all lowercase _ as space)              #
#   Class              - class_name (all lowercase _ as space)                 #
#                                                                              #
# Commenting Style :                                                           #
#   # (in boxes) denotes commenting for large blocks of code, function         #
#       and classes                                                            #
#   # (single at beginning of line) denotes debugging infromation              #
#       like printing out array data to see if data has properly been          #
#       entered                                                                #
#   # (single indented) denotes commented code that may later serve            #
#       some type of purpose                                                   #
#   // used for simple notes inside of code for easy follow capability         #
#   /* */ is only used to comment out mass lines of code, if we follow         #
#       the above way of code we will be able to comment out entire            #
#       files for major debugging                                              #
#                                                                              #
################################################################################
################################################################################
# Session information                                                          #
################################################################################
session_start();
################################################################################
# Includes                                                                     #
################################################################################
// form
if (file_exists("../form_process.class.inc.php")) { include ("../form_process.class.inc.php"); } else { echo 'failed to open form_process.class.inc.php'; exit;}
################################################################################
# Variables                                                                    #
################################################################################
$arrFormConf = array(
	'formFile' => "fpc_example.tpl.html",
	'validReferer' => array(
		'http://<Your Host>/fpc_example.php'
	),
//	'emailData' => array(
//		'from' => 'emailform@localhost', //replace with input field from form.
//		'to' => 'root@localhost',
//		'cc' => null,
//		'subject' => 'email form',
//		'message' => null
//	),
	'fieldText' => array(
		'text1' => 'Text Field 1 (required) :<br />',
		'text2' => 'Text Field 2 :<br />',
		'name' => 'Name :<br />',
		'textarea' => 'Text Area 1 :<br />',
		'textarea2' => 'Text Area 2 :<br />',
		'select' => 'Select Box 1 :<br />',
		'checkbox1' => 'Check Box 1 : ',
		'checkbox2' => 'Check Box 2 : ',
		//'captcha' => 'Enter text from security image :<br />',
	),
	'validation' => array(
		'text1' => array(
			'required',
//			'email',
		),
		//'captcha' => array(
		//	'required',
		//	'captcha',
		//),
	),
	'errorWrapper' => array(
		'0' => "\n".'<span style="color:#c00;">',
		'1' => "</span>\n"
	),
	'errorMsgWrapper' => array(
		'0' => "\n".'<div style="text-align:center; font-size:1.5em; font-weight:bold; color:#c00;">Error</div><div style="color:#c00; border:1px #c00 solid; padding:5px;">',
		'1' => "</div>\n"
	),
	'errorMsg' => array(
		'referer' => 'Invalid Referer ['.$_SERVER['HTTP_REFERER'].']- You must use the same form provided by the host processing it',
		'required' => '<b>[field]</b> is a required field and cannot be blank',
//		'email' => 'The Email address entered is not valid please verify',
//		'captcha' => 'Captcha text does not match the image, use the reset checkbox to reset the image',
	)
);
################################################################################
# Reference Variables                                                          #
################################################################################
$refForm = new form_process($arrFormConf);
################################################################################
#                                                                              #
################################################################################
#echo "REQUEST : <pre>";print_r($_REQUEST);echo "</pre><br />\n";
#echo "POST : <pre>";print_r($_POST);echo "</pre><br />\n";
#echo "GET : <pre>";print_r($_GET);echo "</pre><br />\n";
#if (empty($_REQUEST))
if (array_key_exists('submit', $_REQUEST))
	$boolDataSent = true;
else
	$boolDataSent = false;
################################################################################
#                                                                              #
################################################################################
if ($boolDataSent)
{
	// run error checking
	$refForm->checkReferer();
	$refForm->checkValidation();
	// add additional error checking here
	/*
	...Check Code...
	if(....error found...)
	{
		$refForm->boolError = true;
		$refForm->strErrorMsg .= ...addyourmessage here..."<br />\n";
	}
	*/
	// check error "list"
	if ($refForm->returnErrors())
	{
		// process form submission data if errors for form reprint
		$refForm->processInputData();
		echo $refForm->strTemplate;
		#echo "arrFields : <pre>";print_r($refForm->arrRequest);echo "</pre><br />\n";
		#echo "Session : <pre>";print_r($_SESSION);echo "</pre><br />\n";
	}
	else
	{
		$_SESSION['captcha_valid'] = false;
		$_SESSION['captcha_reset'] = true;
		echo "data has been submitted!";
	}
}
else
{
	echo $refForm->strTemplateOrig;
}
#echo "<pre>"; print_r($_REQUEST); echo "</pre>";
#echo "<pre>"; print_r($refForm->arrFields); echo "</pre>";
