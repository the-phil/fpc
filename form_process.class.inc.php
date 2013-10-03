<?php
########################################################################
# File Name : form_process.class.inc.php                               #
# Author(s) :                                                          #
#   Phil Allen - phil@hilands.com                                      #
# Last Edited By :                                                     #
#   20110525 Removed the magic quotes for setArrRequest function as it #
#      should be done by user, changed frmAction to REQUEST_URI        #
#      instead of PHP_SELF, added a check to see if "name" exists in   #
#      input, Fixed handling of html vs xhtml endings /> vs >, Added   #
#      "Match" validation for checking if two fields are identical,    #
#      Fixed error message text with required, Fixed get input while   #
#      checking referers, Added Referer to main error block.           #
#      phil@hilands.com                                                #
#   20110608 added the ability to set the template as a file or string #
#      phil@hilands.com                                                #
#   20110701 added token timer, added useragent checker, added random  #
#      seed, Testing Destructor, Added html 5 input types (email, url, #
#      range, search, color), Fixed issue with select processing and   #
#      selected, Added check on validreferer for possible xss attack,  #
#      Added validation for configuration array must have fieldError,  #
#      Fixed pregmatch in getForm and processInputData.                #
#      phil@hilands.com                                                #
#   20131001 Cleaned header and comments phil@hilands.com              #
#   20131002 changed strFile to strForm, configuration "formFile" now  #
#      "form" "boolFile" now "boolFormFile", added field error to      #
#      configuration to show the specialized text in error block, pull #
#      construct out and have a process fuction instead, testing token #
#      and useragent which should be done outside of this...           #
#                                                                      #
# Version : 2013100200                                                 #
#                                                                      #
# Copyright :                                                          #
#   Copyright (C) 2005,2006,2007,2008,2009,2010,2011,2012,2013         #
#   Philip J Allen                                                     #
#                                                                      #
#   This file is free software; you can redistribute it and/or modify  #
#   it under the terms of the GNU General Public License as published  #
#   by the Free Software Foundation; either version 2 of the License,  #
#   or (at your option) any later version.                             #
#                                                                      #
#   This File is distributed in the hope that it will be useful,       #
#   but WITHOUT ANY WARRANTY; without even the implied warranty of     #
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      #
#   GNU General Public License for more details.                       #
#                                                                      #
#   You should have received a copy of the GNU General Public License  #
#   along with This File; if not, write to the Free Software           #
#   Foundation, Inc., 51 Franklin St, Fifth Floor,                     #
#   Boston, MA  02110-1301  USA                                        #
#                                                                      #
# General Information (algorithm) :                                    #
#   An attempt to simplify html form processing by taking a raw HTML   #
#   form and automating the error handling.                            #
#    * Takes both get and post data via the $_REQUEST variable.        #
#    * Reads configuration array                                       #
#    * Processes HTML file                                             #
#    * Parses form input values                                        #
#                                                                      #
# Usage :                                                              #
#   include_once ("form_process.class.inc.php"); //include class file  #
#   // Define form configuration array                                 #
#   $arrFormConf = array(......)                                       #
#      .....                                                           #
#      formFile - template                                             #
#      validReferer - array of valid referer URL's                     #
#      emailData - array of email data: from, to, cc, subject, message #
#      fieldText - array of form input "name" => "text title"          #
#      validation - array form input name => array required, email,    #
#         captcha, etc.                                                #
#      errorWrapper - array keys 0 to start, 1 to end to wrap form     #
#         input text                                                   #
#      errorMsgWrapper - array keys 0 to start, 1 to end to wrap error #
#         message                                                      #
#      errorMsg - array keys referer, required, email, captcha, etc    #
#         => error                                                     #
#   $refForm = new form_process($arrFormConf); // instantiate object   #
#   // check if form has been submitted                                #
#   if (array_key_exists('submit', $_REQUEST))                         #
#      $boolDataSent = true;                                           #
#   else                                                               #
#      $boolDataSent = false;                                          #
#   // If data has been sent process                                   #
#   if ($boolDataSent)                                                 #
#   {                                                                  #
#      $refForm->checkReferer();                                       #
#      $refForm->checkValidation();                                    #
#      // handle errors if they exist                                  #
#      if ($refForm->returnErrors())                                   #
#      {                                                               #
#         $refForm->processInputData();                                #
#         echo $refForm->strTemplate;                                  #
#      }                                                               #
#      // if no errors                                                 #
#      else                                                            #
#      {                                                               #
#         echo "data has been submitted!";                             #
#      }                                                               #
#   }                                                                  #
#   else                                                               #
#   {                                                                  #
#      echo $refForm->strTemplateOrig;                                 #
#   }                                                                  #
#                                                                      #
# Variable Lexicon :                                                   #
#   String             - $strStringName                                #
#   Array              - $arrArrayName                                 #
#   Resource           - $resResourceName                              #
#   Reference Variable - $refReferenceVariableName  (aka object)       #
#   Integer            - $intIntegerName                               #
#   Boolean            - $boolBooleanName                              #
#   Function           - function_name (all lowercase _ as space)      #
#   Class              - class_name (all lowercase _ as space)         #
#                                                                      #
# Commenting Style :                                                   #
#   # (in boxes) denotes commenting for large blocks of code, function #
#       and classes                                                    #
#   # (single at beginning of line) denotes debugging infromation      #
#       like printing out array data to see if data has properly been  #
#       entered                                                        #
#   # (single indented) denotes commented code that may later serve    #
#       some type of purpose                                           #
#   // used for simple notes inside of code for easy follow capability #
#   /* */ is only used to comment out mass lines of code, if we follow #
#       the above way of code we will be able to comment out entire    #
#       files for major debugging                                      #
#                                                                      #
########################################################################
########################################################################
# Class form_process                                                   #
########################################################################
/*
How to handle editing.
Shall we use arrREQUEST to take input on all posted data. Do we do a data set
to pass the data from the database to populate the form. then a boolean for
edit to parse data before it gets to the form or should we allow that to be handled
by the form code itself.
*/
class form_process
{
	var $arrFields = array();  // process field chunks
#	var $strFile = ""; //"form.tpl.html"; // this should be pulled from contructor input?
	var $strForm = ""; //"form.tpl.html"; // this should be pulled from contructor input?
	var $boolFormFile = true;

	var $strTemplate = ""; // container for processed form
	var $strTemplateOrig = ""; // container for original form

	var $arrFormConf = array(); // formConf
	var $boolError = false; // if an error is found this will be true

	var $strErrorMsg = "";
	var $arrRequest = array (); // post and get requests $_REQUESTS

	var $intCount = 0; // counter used with arrFields
	var $boolUsehtmlentitiesOnRequestData = false;
	var $boolUseConvertSmartQuotesOnRequestData = false;
	var $boolUserAgent = false; //use user agent checker?
	var $boolToken = false; //use token?
	var $boolTokenTimeLimitError = false;
	var $boolTokenError = false;
	var $strRandomSeed = 'f457c545a9ded88f18ecee47145a72c0';
	####################################################################
	# Constructor                                                      #
	####################################################################
	// php 4 construct
	#function form_process($arrFormConf=array())
	// php 5 construct
	#public function __construct($arrFormConf=array())
	#{
	#}
	####################################################################
	# process                                                          #
	####################################################################
	function process($arrFormConf)
	{
		$this->arrFormConf = $arrFormConf;
		// Set booleans from configuration array
		if (array_key_exists('boolFormFile', $this->arrFormConf)) {
			$this->boolFormFile = $this->arrFormConf['boolFormFile'];
		}
		if (array_key_exists('boolToken', $this->arrFormConf)) {
			$this->boolToken = $this->arrFormConf['boolToken'];
		}
		if (array_key_exists('boolUserAgent', $this->arrFormConf)) {
			$this->boolUserAgent = $this->arrFormConf['boolUserAgent'];
		}
		if (array_key_exists('RandomSeed', $this->arrFormConf)) {
			$this->strRandomSeed = $this->arrFormConf['RandomSeed'];
		}
		// check use tokens for initial settings
		if ($this->boolToken)
			$this->setToken();
		if ($this->boolUserAgent)
			$this->setUserAgent($this->strRandomSeed);
	// handle request information
		$this->setArrRequest($_REQUEST);
		$this->strForm = $this->arrFormConf['form'];
		$this->getForm();
		$this->parse_input_fields(); // find all fields starting with <input and stash in $this->arrFields
		$this->parse_textarea_fields(); // find all fields starting with <textarea and stash in $this->arrFields
		$this->parse_select_fields(); // find all fields starting with <select and stash in $this->arrFields
		//processInputData(); // run this from class processor file.
		#echo $this->strTemplate;
	}
	####################################################################
	# Destructor                                                       #
	####################################################################
#	function __destruct() {
#		$this->strToken = md5(uniqid(rand(), true));
#		$_SESSION['token'] = $this->strToken;
#		$_SESSION['token_time'] = time();
#	}
	################################################################################
	# setArrRequest                                                                #
	#    set Request Data (used for edit data)                                     #
	################################################################################
	function setArrRequest($arrData=array())
	{
		$this->arrRequest = $arrData;
		#echo "setArrRequest 1 :<hr /><pre>"; print_r($this->arrRequest); echo "</pre><hr />";
		#echo "Magic Quotes GPC: ".get_magic_quotes_gpc()."<br />\n";
		#echo "Magic Quotes runtime: ".get_magic_quotes_runtime()."<br />\n";
		//if (get_magic_quotes_gpc())
		#if(get_magic_quotes_runtime())
		#{
		#	echo "inside quotes<br />\n";
		#	$this->arrRequest = $this->arrstripslashes($this->arrRequest);
		#}
		#echo "setArrRequest :<pre>"; print_r($this->arrRequest); echo "</pre>";

		if ($this->boolUsehtmlentitiesOnRequestData)
			$this->arrRequest = $this->arrHTMLEntities($this->arrRequest);
		if ($this->boolUseConvertSmartQuotesOnRequestData)
			$this->arrRequest = $this->arrConvertSmartQuotes($this->arrRequest);
	}
	################################################################################
	# processPost                                                                  #
	#    Process Data if request/post/get data sent.                               #
	################################################################################
	// should this be added? for additional error checking?
	#function processPost()
	#{
	#}
	################################################################################
	# arrstripslashes                                                              #
	#    Helper Function                                                           #
	################################################################################
	function arrstripslashes($input)
	{
		if (!is_array($input))
		{
			$input = stripslashes($input);
		}
		else
		{
			foreach ($input as $key => $val) {
				if (is_array($input[$key])) {
					$input[$key] = $this->arrstripslashes($input[$key]);
				}
				else {
					$input[$key] = stripslashes($input[$key]);
				}
			}
		}
		return $input;
	}
	################################################################################
	# arrConvertSmartQuotes                                                        #
	#    Helper Function this will help us with the input from programs like       #
	#    MicroSoft Word which create special dashes input from programs like       #
	################################################################################
	// reference this later http://www.ssc.uwo.ca/explore/specialcharacters.html
	function arrConvertSmartQuotes($arrInput)
	{
		// create search and replace arrays
		$arrSearch = array(
			chr(145), // ‘ start single "smart" quote
			chr(146), // ’ end single "smart" quote
			chr(147), // “ start double "smart" quote
			chr(148), // ” end double "smart" quote
			chr(151) // — en or em dash?
			);
		$arrReplace = array(
			"&#8216;",
			"&#8217;",
			'&#8220;',
			'&#8221;',
			'-');
		if (!is_array($arrInput))
		{
			$arrInput = str_replace($arrSearch, $arrReplace, $arrInput);
		}
		else
		{
			foreach ($arrInput as $key => $val) {
				if (is_array($arrInput[$key])) {
					$arrInput[$key] = $this->arrConvertSmartQuotes($arrInput[$key]);
				}
				else {
					$arrInput[$key] = str_replace($arrSearch, $arrReplace, $arrInput[$key]);
				}
			}
		}
		return $arrInput;
	}
	################################################################################
	# arrHTMLEntities                                                              #
	#    Helper Function                                                           #
	################################################################################
	function arrHTMLEntities($arrInput)
	{
		if (!is_array($arrInput))
		{
			$arrInput = htmlentities($arrInput,ENT_QUOTES);
		}
		else
		{
			foreach ($arrInput as $key => $val) {
				if (is_array($arrInput[$key])) {
					$arrInput[$key] = $this->arrHTMLEntities($arrInput[$key]);
				}
				else {
					$arrInput[$key] = htmlentities($arrInput[$key],ENT_QUOTES);
				}
			}
		}
		return $arrInput;
	}
	################################################################################
	# getForm                                                                      #
	#    Grab the HTML file that contains the form                                 #
	################################################################################
	function getForm()
	{
		// check if file is web file.
		if ($this->boolFormFile)
		{
			if (substr ($this->strForm, 0, 7) != "http://")
			{
				$boolLocalFile = true;
				if (!file_exists($this->strForm) || filesize($this->strForm) == 0)
				{
					echo 'Form template file is empty or does not exist : '.$this->strForm;
					exit;
				}
			}
			else
			{
				$boolLocalFile = false;
			}
			if(!$fileHandle = fopen($this->strForm, "r"))
			{
				echo 'cannot read file '.$this->strForm.'<br />';
				exit;
			}
			// open file and stor in strTemplate
			if ($boolLocalFile)
				$this->strTemplate = fread($fileHandle, filesize($this->strForm));
			else
				$this->strTemplate = stream_get_contents($fileHandle);
			fclose($fileHandle);
		}
		else
		{
			$this->strTemplate = $this->arrFormConf['form'];
		}
		// this isn't loading the data being sent......
		#echo $this->strTemplate; exit;
// this is the old processing.
// no else should be needed as we exit.
		if(preg_match("/\[frmAction\]/", $this->strTemplate))
		{
			$this->strTemplate = str_replace("[frmAction]", $_SERVER['REQUEST_URI'], $this->strTemplate);
			#$this->strTemplate = str_replace("[frmAction]", $_SERVER['PHP_SELF'], $this->strTemplate);
		}
		#$this->strTemplateOrig = $this->strTemplate;
		// find [token] check for tokenTimer to see if we are using tokens
		#if (array_key_exists('genToken', $this->arrFormConf))
		if (array_key_exists('tokenTimer', $this->arrFormConf))
		{
			if(preg_match("/\[token\]/", $this->strTemplate))
			{
				// This sets the new token in the form, from setToken
				#$this->strTemplateOrig = str_replace("[token]", $this->strToken, $this->strTemplateOrig);
				$this->strTemplate = str_replace("[token]", $this->strToken, $this->strTemplate);
				#$this->strTemplate = str_replace("[token]", $this->strToken, $this->strTemplate);
			}
		}
		// stash template in template orig.
		$this->strTemplateOrig = $this->strTemplate;
		// orig will be used as non parsed all we'll do is remove the [errorMsg]
		if(preg_match("/\[errorMsg\]/", $this->strTemplateOrig))
		{
			if ($this->strErrorMsg != "")
				$this->strTemplateOrig = str_replace("[errorMsg]", $this->strErrorMsg, $this->strTemplateOrig);
			else
				$this->strTemplateOrig = str_replace("[errorMsg]", "", $this->strTemplateOrig);
		}
	}
	################################################################################
	# parse_input_fields                                                           #
	################################################################################
	#function parse_input_fields($strTemplate, $i)
	function parse_input_fields()
	{
		$intStartPosition = 0;
		while(@strpos($this->strTemplate, "<input", $intStartPosition))
		#while(@strpos($strTemplate, $strNeedle, $intStartPosition))
		{
			$this->intCount++;
			$intStart = strpos($this->strTemplate, "<input", $intStartPosition);
			#$intStart = strpos($strTemplate, $strNeedle, $intStartPosition);
			$intEnd = strpos($this->strTemplate, ">", $intStart+6); // intstart + <input +6
			$intLength = ++$intEnd - $intStart; // different + 1 for length
			$intStartPosition = $intEnd;
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 66';
				exit;
			}
			// collect form.
			$strFrmInput = substr ($this->strTemplate,$intStart,$intLength);
			// we'll have an issue with type= etc.. strto lower? strto upper? I dunno
			############################################################################
			# gather form data type                                                    #
			############################################################################
			$intStart = strpos($strFrmInput, 'type="', 0);
			$intEnd = strpos($strFrmInput, '"', ($intStart+6)); // add length of type=
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 6; // different + 1 for length
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 87';
				exit;
			}
			// collect form.
			$strType = substr ($strFrmInput,$intStart+6,$intLength);
			############################################################################
			# gather form data name                                                    #
			############################################################################
			$intStart = strpos($strFrmInput, 'name="', 0);
			$intEnd = strpos($strFrmInput, '"', $intStart+6);
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 6;
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 100';
				exit;
			}
			// collect form.
			$strName = substr ($strFrmInput,$intStart+6,$intLength);
			############################################################################
			# gather input value                                                       #
			############################################################################
			$intStart = strpos($strFrmInput, 'value="', 0);
			$intEnd = strpos($strFrmInput, '"', $intStart+7);
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 7; // different + 1 for length
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 113';
				exit;
			}
			// collect form.
			$strValue = substr ($strFrmInput,$intStart+7,$intLength);
			############################################################################
			# stash values into array                                                  #
			############################################################################
			#echo "Form : ".$strFrmInput."<br />\n";
			#echo "Type : ".$strType."<br />\n";
			#echo "Name : ".$strName."<br />\n";
			#echo "Value : ".$strValue."<br />\n";
			$this->arrFields[$this->intCount] = array(
				'type' => $strType,
				'name' => $strName,
				'value' => $strValue,
				'form' => $strFrmInput
			);
		}
		// remove return as its part of "this" class
		#return $arrFields;
	}
	################################################################################
	# parse_textarea_fields                                                        #
	#   <textarea for input                                                        #
	################################################################################
	#function parse_textarea_fields($strTemplate,$i)
	function parse_textarea_fields()
	{
		$intStartPosition = 0;
		while(@strpos($this->strTemplate, "<textarea", $intStartPosition))
		#while(@strpos($strTemplate, $strNeedle, $intStartPosition))
		{
			#echo $i++."<br />\n";
			$this->intCount++;
			$intStart = strpos($this->strTemplate, "<textarea", $intStartPosition);
			#$intStart = strpos($strTemplate, $strNeedle, $intStartPosition);
			$intEnd = strpos($this->strTemplate, "</textarea>", $intStart+6); // intstart + <input +6
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd+11 - $intStart; // different + 1 for length
			$intStartPosition = $intEnd; //should add +10?
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 66';
				exit;
			}
			// collect form.
			$strFrmInput = substr ($this->strTemplate,$intStart,$intLength);
			// this data should be stashed in a name array.
			// get the "name variable inside of $strFrmInput then use that as a key
			// hmm radio buttons will be fucked up because of that. shall we make a type
			// as the key them a sub array with the value .. yep sounds good to me.
			//$arrFields
			// dig out input type, name and value.
			// we'll have an issue with type= etc.. strto lower? strto upper? I dunno
			############################################################################
			#                                                                          #
			############################################################################
			$intStart = strpos($strFrmInput, 'type="', 0);
			$intEnd = strpos($strFrmInput, '"', ($intStart+6)); // add length of type=
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 6; // different + 1 for length
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 87';
				exit;
			}
			// collect form.
			$strType = substr ($strFrmInput,$intStart + 6,$intLength);
			############################################################################
			#                                                                          #
			############################################################################
			$intStart = strpos($strFrmInput, 'name="', 0);
			$intEnd = strpos($strFrmInput, '"', $intStart+6);
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 6; // different + 1 for length
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 100';
				exit;
			}
			// collect form.
			$strName = substr ($strFrmInput,$intStart + 6,$intLength);
			############################################################################
			#                                                                          #
			############################################################################
			$intStart = strpos($strFrmInput, '>', 0);
			$intEnd = strpos($strFrmInput, '</textarea>', $intStart);
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart;
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 113';
				exit;
			}
			// collect form.
			$strValue = substr ($strFrmInput,$intStart+1,$intLength-1);
			############################################################################
			#                                                                          #
			############################################################################
			#echo "Form : ".$strFrmInput."<br />\n";
			#echo "Type : ".$strType."<br />\n";
			#echo "Name : ".$strName."<br />\n";
			#echo "Value : ".$strValue."<br />\n";
			// stash everything in an array
			// set new start position
			#echo $intStartPosition."<br />\n";
			#if ($i > 10) exit;
			$this->arrFields[$this->intCount] = array(
				'type' => $strType,
				'name' => $strName,
				'value' => $strValue,
				'form' => $strFrmInput
			);
		}
		#return $arrFields;
	}
	################################################################################
	# parse_select_fields                                                          #
	################################################################################
	#function parse_select_fields($strTemplate,$i)
	function parse_select_fields()
	{
		$intStartPosition = 0;
		while(@strpos($this->strTemplate, "<select", $intStartPosition))
		#while(@strpos($strTemplate, $strNeedle, $intStartPosition))
		{
			#echo $i++."<br />\n";
			$this->intCount++;
			$intStart = strpos($this->strTemplate, "<select", $intStartPosition);
			#echo substr($this->strTemplate, $intStart);
			#$intStart = strpos($strTemplate, $strNeedle, $intStartPosition);
			$intEnd = strpos($this->strTemplate, "</select>", $intStart+6); // intstart + <input +6
			$intLength = $intEnd+9 - $intStart; // different + 1 for length
			$intStartPosition = $intEnd;
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 465';
				exit;
			}
			// collect form.
			$strFrmInput = substr ($this->strTemplate,$intStart,$intLength);
			############################################################################
			#                                                                          #
			############################################################################
			$intStart = strpos($strFrmInput, 'name="', 0);
			$intEnd = strpos($strFrmInput, '"', $intStart+6);
			#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			$intLength = $intEnd - $intStart - 6; // different + 1 for length
			if ($intLength < 0)
			{
				echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 100';
				exit;
			}
			// collect form.
			$strName = substr ($strFrmInput,$intStart + 6,$intLength);
			############################################################################
			#                                                                          #
			############################################################################
			#echo "Form : ".$strFrmInput."<br />\n";
			#echo "Name : ".$strName."<br />\n";
			$this->arrFields[$this->intCount] = array(
				'type' => "select",
				'name' => $strName,
				'form' => $strFrmInput,
				'options' => array()
			);
			############################################################################
			#                                                                          #
			############################################################################
			$intStartPosition2 = 0;
			while(@strpos($strFrmInput, "<option", $intStartPosition2))
			{
				$intStart = strpos($strFrmInput, "<option", $intStartPosition2);
				$intEnd = strpos($strFrmInput, "</option>", $intStart + 7);
				$intLength = $intEnd + 9 - $intStart;
				$intStartPosition2 = $intEnd;
				if ($intLength < 0)
				{
					echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 66';
					exit;
				}
				$strOption = substr($strFrmInput, $intStart, $intLength);
				############################################################################
				#                                                                          #
				############################################################################
				$intStart = strpos($strOption, '>', 0);
				$intEnd = strpos($strOption, '</option>', $intStart);
				#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
				$intLength = $intEnd - $intStart;
				if ($intLength < 0)
				{
					echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 113';
					exit;
				}
				$strName = substr ($strOption,$intStart+1,$intLength-1);
				############################################################################
				#                                                                          #
				############################################################################
				$intStart = strpos($strOption, 'value="', 0);
				$intEnd = strpos($strOption, '"', $intStart+7);
				#echo "start/end : ".$intStart." ".$intEnd."<br />\n";
			#<input name="text1" type="text" size="40" value="" /><br />
				$intLength = $intEnd - $intStart - 7; // different + 1 for length
				if ($intLength < 0)
				{
					echo 'strange start('.$intStart.') and end ('.$intEnd.')location killing program : line 113';
					exit;
				}
				// collect form.
				$strValue = substr ($strOption,$intStart+7,$intLength);
				############################################################################
				#                                                                          #
				############################################################################
				// collect form.
				#echo "Option : ".htmlspecialchars($strOption)."<br />\n";
				#echo "Name : ".$strName."<br />\n";
				#echo "Value : ".$strValue."<br />\n";
				$this->arrFields[$this->intCount]['options'][$intStartPosition2]['name'] = $strName;
				$this->arrFields[$this->intCount]['options'][$intStartPosition2]['value'] = $strValue;
				$this->arrFields[$this->intCount]['options'][$intStartPosition2]['option'] = $strOption;
			}
		}
		#return $arrFields;
	}
	############################################################################
	# processInputData                                                         #
	#    Used to process request data                                          #
	#    we don't process button, reset, submit shouldn't need to?             #
	############################################################################
	function processInputData()
	{
		########################################################################
		#                                                                      #
		########################################################################
		if(preg_match("/\[errorMsg\]/", $this->strTemplate))
		{
			$this->strTemplate = str_replace("[errorMsg]", $this->strErrorMsg, $this->strTemplate);
		}
		########################################################################
		#                                                                      #
		########################################################################
		foreach ($this->arrFields as $strKey => $arrValue)
		{
			switch($arrValue['type'])
			{
				################################################################
				#                                                              #
				################################################################
				/*
				// add html 5 things in here.
				http://www.w3schools.com/html5/html5_form_input_types.asp
				http://www.w3schools.com/html5/tag_input.asp
				button, checkbox, color, date, datetime, datetime-local, email, file, hidden, image, month, number, password,
				radio, range, reset, search, submit, tel, text, time, url, week
				*/
				case 'text':
				case 'hidden':
				case 'image': // ??
				case 'password': //??
				case 'email':
				case 'url':
				case 'range':
				case 'search':
				case 'color':
				case 'number':
					// set arrfields values
					#echo $arrFields[$strKey][$arrValue]['value']."<br />\n"; //oops extra var
					#echo $arrFields[$strKey]['value']."<br />\n";
					if (array_key_exists($arrValue['name'], $this->arrRequest))
					{
						// if our token timer runs out or token doesn't match push str token into form.
						#if ($this->boolTokenTimeLimitError && $arrValue['name'] == 'token') // if we hit the time limit do special token work around.

						#if (($this->boolTokenError || $this->boolTokenTimeLimitError) && $arrValue['name'] == 'token') // if we hit the time limit do special token work around.
						// we do this so the token does NOT take the value of the form
						if ($arrValue['name'] == 'token') // if we hit the time limit do special token work around.
						{
							#echo 'change token to :"'.$this->strToken.'"<br />';
							$this->arrFields[$strKey]['nvalue'] = $this->strToken; // need check if array_key_exists
						}
						else
							$this->arrFields[$strKey]['nvalue'] = $this->arrRequest[$arrValue['name']]; // need check if array_key_exists

						// don't reset token field with error
						#if ($arrValue['name'] != 'token')
						#	$this->arrFields[$strKey]['nvalue'] = $this->arrRequest[$arrValue['name']]; // need check if array_key_exists
						$this->arrFields[$strKey]['nform'] = str_replace('value="'.$this->arrFields[$strKey]['value'].'"','value="'.$this->arrFields[$strKey]['nvalue'].'"', $this->arrFields[$strKey]['form']);
						$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
					}
					break;
				################################################################
				#                                                              #
				################################################################
				case 'textarea':
					$this->arrFields[$strKey]['nvalue'] = $this->arrRequest[$arrValue['name']];
					$this->arrFields[$strKey]['nform'] = str_replace('>'.$this->arrFields[$strKey]['value'].'</textarea>','>'.$this->arrFields[$strKey]['nvalue'].'</textarea>', $this->arrFields[$strKey]['form']);
					$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
					break;
				################################################################
				#                                                              #
				################################################################
				case 'checkbox':
					// strip any "checked" notations
					$this->arrFields[$strKey]['nform'] = str_replace(" checked", "", $this->arrFields[$strKey]['form']);
					//check if data was submitted for this checkbox
					#if (isset($this->arrRequest[$arrValue['name']]))
					// added to handle arrRequest set for editing.
					if (isset($this->arrRequest[$arrValue['name']]) && $this->arrRequest[$arrValue['name']] != "0" && $this->arrRequest[$arrValue['name']] != null)
					{
						// add "checked" if it was.
							// need to add something in here for just plain end > instead of end />
						#preg_match($this->arrFields[$strKey]['nform']
						if ("/>" == substr($this->arrFields[$strKey]['nform'], -2))
							$this->arrFields[$strKey]['nform'] = str_replace("/>", "checked />", $this->arrFields[$strKey]['nform']);
						else
							$this->arrFields[$strKey]['nform'] = str_replace(">", "checked>", $this->arrFields[$strKey]['nform']);
					}
					$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
					break;
				################################################################
				#                                                              #
				################################################################
				case 'radio':
					//strip checked add if matched with request.
					$this->arrFields[$strKey]['nform'] = str_replace(" checked", "", $this->arrFields[$strKey]['form']);
					//check if data was submitted for this checkbox
					if (isset($this->arrRequest[$arrValue['name']]))
					{
						// see if the request name is the same as the value
						if ($this->arrRequest[$arrValue['name']] == $arrValue['value'])
						{
							// add "checked" if it was.
							// need to add something in here for just plain end > instead of end />
							if ("/>" == substr($this->arrFields[$strKey]['nform'], -2))
								$this->arrFields[$strKey]['nform'] = str_replace("/>", "checked />", $this->arrFields[$strKey]['nform']);
							else
								$this->arrFields[$strKey]['nform'] = str_replace(">", "checked>", $this->arrFields[$strKey]['nform']);
						}
					}
					$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
					break;
				################################################################
				#                                                              #
				################################################################
				case 'select':
					$strTempForm = $this->arrFields[$strKey]['form'];
					foreach ($this->arrFields[$strKey]['options'] as $strArrValue)
					{
						// remove "selected" from form. moved this into the is set incase there is no input, we use html default
						#echo 'arrValue : '.$strArrValue['option'].'<br /><br />';
#						$strTempOption = str_replace(" selected", "", $strArrValue['option']);
						// this won't work as we are looping the same "template"
						// so we'll add a variable before the foreach. and finish it outside the loop.
						#$arrFields[$strKey]['nform'] = str_replace($strArrValue['option'], $strTempOption, $arrFields[$strKey]['form']);
#						$strTempForm = str_replace($strArrValue['option'], $strTempOption, $strTempForm);
						#echo "select request : ".$_REQUEST[$arrValue['name']]."<br />\n";
						#echo "Option Name : ".$strArrValue['name']."<br />\n";
						if (isset($this->arrRequest[$arrValue['name']]))
						{
							$strTempOption = str_replace(" selected", "", $strArrValue['option']);
							$strTempForm = str_replace($strArrValue['option'], $strTempOption, $strTempForm);
							if ($this->arrRequest[$arrValue['name']] == $strArrValue['value'])
							{
								#echo "here : &gt;".$strArrValue['name']."&lt;<br />\n";
								#echo "show : ".$strArrValue['value']."<br />\n";
								$strTempForm = str_replace(">".$strArrValue['name']."<", " selected>".$strArrValue['name']."<", $strTempForm);
							}
						}
						#echo htmlspecialchars($strArrValue['option'])."<br />\n";
						#echo htmlspecialchars($strTempOption)."<br />\n";
						#echo "<pre>".htmlspecialchars($arrFields[$strKey]['nform'])."</pre><br />\n";
						#echo "<pre>".htmlspecialchars($strTempForm)."</pre><br />\n";
					}
					#echo "<pre>".htmlspecialchars($strTempForm)."</pre><br />\n";
					$this->arrFields[$strKey]['nform'] = $strTempForm;
					#$arrFields[$strKey]['noptions'] =
					$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
					break;
				################################################################
				#                                                              #
				################################################################
				default:
					break;
			}
		}
	}
	############################################################################
	# returnErrors                                                             #
	############################################################################
	function returnErrors()
	{
		if ($this->boolError)
		{
			$this->strErrorMsg = $this->arrFormConf['errorMsgWrapper'][0].$this->strErrorMsg.$this->arrFormConf['errorMsgWrapper'][1];
			return true;
		}
		else
		{
			return false;
		}
	}
	############################################################################
	# checkValidation                                                          #
	#    Runs Validation Functions                                             #
	############################################################################
	function checkValidation()
	{
		if (array_key_exists('validation', $this->arrFormConf))
		{
			foreach ($this->arrFormConf['validation'] as $strKey => $arrValue)
			{
				if ($strKey == 'match')
				{
					#$this->checkMatch($_REQUEST[$arrValue[0]], $_REQUEST[$arrValue[1]]);
					$this->checkMatch($arrValue);
				}
				#echo "Key : ".$strKey."<br />\n";
				#echo "<pre>"; print_r($arrValue); echo "</pre><br />\n";
				foreach ($arrValue as $strValue)
				{
					if ($strValue == "required")
					{
						#echo "checkRequired [".$strKey."] : ".$_REQUEST[$strKey]."<br />\n";
						#echo "running required routine";
						$this->checkRequired($strKey, $_REQUEST[$strKey]);
					}
					if ($strValue == "captcha")
					{
						#echo "checkCAPTCHA : ".$_REQUEST[$strKey]."<br />\n";
						$this->checkCAPTCHA($strKey, $_REQUEST[$strKey]);
					}
					if ($strValue == "email")
					{
						#echo "checkEmail : ".$strKey." - ".$_REQUEST[$strKey]."<br />\n";
						$this->checkEmail($strKey, $_REQUEST[$strKey]);
					}
				} // end spliting arrays from validation
			} // end foreach array from validation
		} // end if array exist
	}
	############################################################################
	# checkRequired                                                            #
	#    Validation Function - verify data is in field                         #
	############################################################################
	function checkRequired($strName, $strValue)
	{
		#echo "strName : ".$strName."<br />\n";
		#echo "strValue : ".$strValue."<br />\n";
		if ($strValue == null || $strValue == "")
		{
			//create error message, see if fieldError array exists for "our" fields.
			// if it exists use it for the error message, otherwise use the "name" of input
			if (array_key_exists($strName, $this->arrFormConf['fieldError']))
				$strField = $this->arrFormConf['fieldError'][$strName];
			else
				$strField = $strName;
			$this->boolError = true;
			$this->strErrorMsg .= str_replace("[field]", $strField, $this->arrFormConf['errorMsg']['required'])."<br />\n";
			#$this->strErrorMsg .= str_replace("[field]", $strName, $this->arrFormConf['errorMsg']['required'])."<br />\n";
			// check fieldText should see if it exists
			// this will find the text matching in the form to wrap with errorWrapper (e.g. change the color of the text near the form)
			if (array_key_exists($strName, $this->arrFormConf['fieldText']))
			{
			// replace array with wrap + array in strTemplate
				$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$strName],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$strName].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
			}
		}
	}
	############################################################################
	# checkCAPTCHA                                                             #
	#    Validation Function - Runs Captcha Validation                         #
	############################################################################
	function checkCAPTCHA($strName, $strValue)
	{
		// Check Security Reset Check box and process.
		if($_REQUEST['captcha_check_box'] == 1) // need check if array_key_exists
		{
			$_SESSION['captcha_reset'] = true;
		}
		else
		{
			$_SESSION['captcha_reset'] = false;
		}
		// process captcha information
		// check if valid session is set.
		// if form text is not equal to captcha session variable or if the session data is empty fail, otherwise become true
		// note if it does become valid and user changes captcha field. Validation will be true within the session still.
		if(!$_SESSION['captcha_valid']) // need check if array_key_exists
		{
			#echo $_REQUEST['captcha'] .' eq? '. $_SESSION['captcha_code']."<br />\n";
			if($_SESSION['captcha_code'] == $_REQUEST['captcha'] && !empty($_SESSION['captcha_code']))
			{
				// this variable is checked in the captcha image generator file.
				$_SESSION['captcha_valid'] = true;
			}
			else
			{
				$this->boolError = true;
				$this->strErrorMsg .= $this->arrFormConf['errorMsg']['captcha']."<br />\n";
				#echo "key test".$strName." :: ".$this->arrFormConf['fieldText'][$strName];
				if (array_key_exists($strName, $this->arrFormConf['fieldText']))
				{
					#echo "replace captcha text<br />\n";
				// replace array with wrap + array in strTemplate
					$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$strName],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$strName].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
				}
			}
		}
	}
	############################################################################
	# checkReferer                                                             #
	#    Validation Function - verify referer array vs $_SERVER['HTTP_REFERER']#
	############################################################################
	function checkReferer()
	{
		if (array_key_exists('validReferer', $this->arrFormConf))
		{
			$boolValid = false;
			//need to strip ? and passed. from referer.
			$strReferer = substr ($_SERVER['HTTP_REFERER'], 0, strpos($_SERVER['HTTP_REFERER'], '?', 1));
			if ($strReferer == "")
				$strReferer = $_SERVER['HTTP_REFERER'];
			// run loop for each "valid referer" from Configuration array.
			foreach ($this->arrFormConf['validReferer'] as $strValidReferer)
			{
				// check if referer is [PHP_SELF] block if it is replace PHP_SELF and what the referer wants.
				if ($strValidReferer == '[PHP_SELF]')
				{
					//add self referer.
					if (!empty($_SERVER['HTTPS']))
						$strServer = "https://";
					else
						$strServer = "http://";
					// added htmlspecialchars just in case return with xss some how.
					$strValidReferer = htmlspecialchars($strServer.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
					#$strValidReferer = $strServer.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
				}
				#echo "Valid Referer : ".$strValidReferer."<br />\n";
				#if ($strValidReferer == $_SERVER['HTTP_REFERER'])
				#echo $strValidReferer."<br />\n";
				#echo $strReferer."<br />\n";
				if ($strValidReferer == $strReferer)
				{
					#echo $strValidReferer.' eq '.$_SERVER['HTTP_REFERER']."<br />\n";
					$boolValid = true;
				}
				else
				{
					#echo $strValidReferer.' ne '.$_SERVER['HTTP_REFERER']."<br />\n";
				}
				#echo "bool valid: ".$boolValid."<br />\n";
			}
			if (!$boolValid)
			{
				#echo "boolvalid is false<br />\n";
				$this->boolError = true;
				// parse error message replace [referer] block.
				$this->strErrorMsg .= str_replace("[referer]", $strReferer, $this->arrFormConf['errorMsg']['referer'])."<br />\n";
				#$this->strErrorMsg .= $this->arrFormConf['errorMsg']['referer']."<br />\n";
			}
		}// end if array key exists validReferer
	}
	############################################################################
	# checkEmail                                                               #
	#    Validation Function - checks for a valid email address                #
	#    http://www.devshed.com/c/a/PHP/Email-Address-Verification-with-PHP/2/ #
	#    this needs to be modified to validate vs all RFC's                    #
	#    http://www.linuxjournal.com/article/9585                              #
	############################################################################
	function checkEmail($strName, $email)
	{
		$boolValid = true;
		if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email))
		{
			list($username,$domain)=split('@',$email);
			if(!checkdnsrr($domain,'MX'))
			{
				$boolValid = false;
			}
		}
		else
		{
			$boolValid = false;
		}
		if (!$boolValid)
		{
			if (array_key_exists($strName, $this->arrFormConf['fieldError']))
				$strField = $this->arrFormConf['fieldError'][$strName];
			else
				$strField = $strName;
			$this->boolError = true;
			$this->strErrorMsg .= str_replace("[field]", $strField, $this->arrFormConf['errorMsg']['email'])."<br />\n";
			// check fieldText should see if it exists ...
			if (array_key_exists($strName, $this->arrFormConf['fieldText']))
			{
			// replace array with wrap + array in strTemplate
				$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$strName],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$strName].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
			}
#			$this->boolError = true;
#			$this->strErrorMsg .= $this->arrFormConf['errorMsg']['email']."<br />\n";
		}
	}
	############################################################################
	# checkMatch                                                               #
	#    Validation Function - check is two fields are identical               #
	############################################################################
	function checkMatch($arrMatch)
	{
					#$this->checkMatch($_REQUEST[$arrValue[0]], $_REQUEST[$arrValue[1]]);
		#if ($strVar1 != $strVar2)
		if ($_REQUEST[$arrMatch[0]] != $_REQUEST[$arrMatch[1]])
		{
			// set error message.
			$this->boolError = true;
			//create error message, see if fieldError array exists for "our" fields.
			//check first field for error text, if it exists store in strField.
			if (array_key_exists($arrMatch[0], $this->arrFormConf['fieldError']))
				$strField = $this->arrFormConf['fieldError'][$arrMatch[0]];
			else
				$strField = $arrMatch[0];
			$strErrorMsg = str_replace("[field-a]", $strField, $this->arrFormConf['errorMsg']['match']);
			//check second field for error text, if it exists store in strField.
			if (array_key_exists($arrMatch[1], $this->arrFormConf['fieldError']))
				$strField = $this->arrFormConf['fieldError'][$arrMatch[1]];
			else
				$strField = $arrMatch[1];
			$strErrorMsg = str_replace("[field-b]", $strField, $strErrorMsg);
			// append a break to the error.
			$strErrorMsg .= "<br />\n";
			// take functions error message and append to class error message.
			$this->strErrorMsg .= $strErrorMsg;
			#$this->strErrorMsg .= $this->arrFormConf['errorMsg']['match']."<br />\n";
			#$this->strErrorMsg .= str_replace("[field-a]", $strVar1, $this->arrFormConf['errorMsg']['required'])."<br />\n";
			// Change fieldText color with errorWrapper
			if (array_key_exists($arrMatch[0], $this->arrFormConf['fieldText']))
			{
				$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$arrMatch[0]],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$arrMatch[0]].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
			}
			if (array_key_exists($arrMatch[1], $this->arrFormConf['fieldText']))
			{
				$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$arrMatch[1]],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$arrMatch[1]].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
			}
		}
	}
	############################################################################
	# setToken                                                                 #
	############################################################################
	function setToken()
	{
		// creates token, if token exists store in "strTokenCurrent"
		#if (array_key_exists('genToken', $this->arrFormConf))
		if (array_key_exists('tokenTimer', $this->arrFormConf))
		{
			// stash old session variables for use with check.
			if (array_key_exists('token', $_SESSION))
				$this->strTokenChk = $_SESSION['token'];
			if (array_key_exists('token_time', $_SESSION))
				$this->strTokenTimeChk = $_SESSION['token_time'];
			// create new session vars and token var for form.
			#echo 'making new token!<br />';
			$this->strToken = md5(uniqid(rand(), true));
			$_SESSION['token'] = $this->strToken;
			$_SESSION['token_time'] = time();
		}
	}
	############################################################################
	# checkToken                                                               #
	#    Validation Function - checks for a valid token                        #
	#    http://phpsec.org/projects/guide/2.html                               #
    #    http://shiflett.org/articles/cross-site-request-forgeries             #
	############################################################################
	function checkToken()
	{
		$intTokenTimeLimit = $this->arrFormConf['tokenTimer'];
		#echo "this arrformconf :<pre>";print_r($this->arrFormConf);echo"</pre><br />\n";
		#echo "token timer: ".$intTokenTimeLimit."<br />\n";
		#$intTokenTimeLimit = '300'; // time in seconds 300 = 5 minutes
		$boolTokenTimeLimit = false;
		$boolToken = false;
		// check token and timer
		if (array_key_exists('token', $_SESSION))
		{
			#if($_POST['token'] == $_SESSION['token'])
			if($_POST['token'] == $this->strTokenChk)
				$boolToken = true;
			#$strTokenTime = time() - $_SESSION['token_time'];
			$strTokenTime = time() - $this->strTokenTimeChk;
			if ($strTokenTime <= $intTokenTimeLimit)
				$boolTokenTimeLimit = true;
		}
		if (!$boolTokenTimeLimit)
		{
			$this->boolTokenTimeLimitError = true;
			$this->boolError = true;
			#$this->strErrorMsg .= "Security token ran out of time. Please click submit on the form.<br />\n";
			$this->strErrorMsg .= $this->arrFormConf['errorMsg']['tokentime']."<br />\n";
			$this->strToken = md5(uniqid(rand(), true));
			$_SESSION['token'] = $this->strToken;
			$_SESSION['token_time'] = time();
		}
		if (!$boolToken)
		{
			$this->boolTokenError = true;
			$this->boolError = true;
			#$this->strErrorMsg .= "Token does not match<br />\n";
			$this->strErrorMsg .= $this->arrFormConf['errorMsg']['token']."<br />\n";
			$this->strToken = md5(uniqid(rand(), true));
			$_SESSION['token'] = $this->strToken;
			$_SESSION['token_time'] = time();
		}
	}
	############################################################################
	# setUserAgent                                                             #
	#    Validation Function - checks for a valid token                        #
	#    http://phpsec.org/projects/guide/2.html                               #
    #    http://shiflett.org/articles/cross-site-request-forgeries             #
	############################################################################
	function setUserAgent($strRandomSeed)
	{
		$_SESSION['user_agent'] = md5($_SERVER['HTTP_USER_AGENT'].$strRandomSeed); // should help with session hijacking
	}
	############################################################################
	# checkUserAgent                                                           #
	#    Validation Function - checks for a valid token                        #
	#    http://phpsec.org/projects/guide/2.html                               #
    #    http://shiflett.org/articles/cross-site-request-forgeries             #
	############################################################################
	function checkUserAgent($strRandomSeed)
	{
		if ($_SESSION['user_agent'] != md5($_SERVER['HTTP_USER_AGENT'].$strRandomSeed))
		{
			$this->boolError = true;
			$this->strErrorMsg .= $this->arrFormConf['errorMsg']['useragent']."<br />\n";
		}
	}
################################################################################
#                                                                              #
################################################################################
} // end class form_process
?>
