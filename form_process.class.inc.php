<?php
########################################################################
# File Name : form_process.class.inc.php                               #
# Author(s) :                                                          #
#   Phil Allen - phil@hilands.com                                      #
# Last Edited By :                                                     #
#   phil@hilands.com                                                   #
# Version : 2009102700                                                 #
#                                                                      #
# Copyright :                                                          #
#   Database Include                                                   #
#   Copyright (C) 2005 Philip J Allen                                  #
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
# External Files:                                                      #
#   List of External Files all require/includes                        #
#                                                                      #
# General Information (algorithm) :                                    #
#                                                                      #
# Functions :                                                          #
#   see classes                                                        #
#                                                                      #
# Classes :                                                            #
#   tpl                                                                #
#                                                                      #
# CSS :                                                                #
#   db_error - used in span for custom database errors                 #
#   db_sql_error_message - used in span for SQL default error          #
#       messages and error numbers                                     #
#                                                                      #
# JavaScript :                                                         #
#                                                                      #
# Change Log :                                                         #
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
	var $strFile = ""; //"form.tpl.html"; // this should be pulled from contructor input?
	var $strTemplate = ""; // container for html template
	var $strTemplateOrig = "";
	var $intCount = 0; // counter used with arrFields
	var $arrFormConf = array(); // formFile
	var $boolError = false;
	var $strErrorMsg = "";
	var $arrRequest = array (); // post and get requests $_REQUESTS
	####################################################################
	# Constructor                                                      #
	####################################################################
	// php 4 construct
	#function form_process($arrFormConf=array())
	// php 5 construct
	public function __construct($arrFormConf=array())
	{
		// handle request information
		$this->setArrRequest($_REQUEST);
		$this->arrFormConf = $arrFormConf;
		$this->strFile = $this->arrFormConf['formFile'];
		#$arrInputFields = parse_input_fields($strTemplate,count($arrFields));
		#$arrTextareaFields = parse_textarea_fields($strTemplate,count($arrFields));
		#$arrSelectFields = parse_select_fields($strTemplate,count($arrFields));
		#$arrFields = array_merge($arrFields, $arrInputFields,$arrTextareaFields,$arrSelectFields);
		$this->getForm();
		$this->parse_input_fields(); // find all fields starting with <input and stash in $this->arrFields
		$this->parse_textarea_fields(); // find all fields starting with <textarea and stash in $this->arrFields
		$this->parse_select_fields(); // find all fields starting with <select and stash in $this->arrFields
		//processInputData(); // run this from class processor file.
	}
	################################################################################
	# setArrRequest                                                                #
	#    set Request Data (used for edit data)                                     #
	################################################################################
	function setArrRequest($arrData=array())
	{
		$this->arrRequest = $arrData;
		if (get_magic_quotes_gpc())
			$this->arrRequest = $this->arrstripslashes($this->arrRequest);
			$this->arrRequest = $this->arrHTMLEntities($this->arrRequest);
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
			chr(145), //‘
			chr(146), //’
			chr(147), //“
			chr(148), //”
			chr(151) //—
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
		#$strFile = "form_allphp.tpl.html";
		// check if file is web file.
		#echo $this->strFile;
		if (substr ($this->strFile, 0, 7) != "http://")
		{
			$boolLocalFile = true;
			if (!file_exists($this->strFile) || filesize($this->strFile) == 0)
			{
				echo 'Form template file is empty or does not exist : '.$this->strFile;
				exit;
			}
		}
		else
		{
			$boolLocalFile = false;
		}
		if(!$fileHandle = fopen($this->strFile, "r"))
		{
			echo 'cannot read file '.$this->strFile.'<br />';
			exit;
		}
		else
		{
			if ($boolLocalFile)
				$this->strTemplate = fread($fileHandle, filesize($this->strFile));
			else
				$this->strTemplate = stream_get_contents($fileHandle);
			fclose($fileHandle);
			if(preg_match("[frmAction]", $this->strTemplate))
			{
				$this->strTemplate = str_replace("[frmAction]", $_SERVER['PHP_SELF'], $this->strTemplate);
			}
			$this->strTemplateOrig = $this->strTemplate;
			// orig will be used as non parsed all we'll do is remove the [errorMsg]
			if(preg_match("[errorMsg]", $this->strTemplateOrig))
			{
				$this->strTemplateOrig = str_replace("[errorMsg]", "", $this->strTemplateOrig);
			}
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
		if(preg_match("[errorMsg]", $this->strTemplate))
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
				case 'text':
				case 'hidden':
				case 'image': // ??
				case 'password': //??
					// set arrfields values
					#echo $arrFields[$strKey][$arrValue]['value']."<br />\n"; //oops extra var
					#echo $arrFields[$strKey]['value']."<br />\n";
					$this->arrFields[$strKey]['nvalue'] = $this->arrRequest[$arrValue['name']]; // need check if array_key_exists
					$this->arrFields[$strKey]['nform'] = str_replace('value="'.$this->arrFields[$strKey]['value'].'"','value="'.$this->arrFields[$strKey]['nvalue'].'"', $this->arrFields[$strKey]['form']);
					$this->strTemplate = str_replace($this->arrFields[$strKey]['form'], $this->arrFields[$strKey]['nform'], $this->strTemplate);
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
						$this->arrFields[$strKey]['nform'] = str_replace("/>", "checked />", $this->arrFields[$strKey]['nform']);
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
							$this->arrFields[$strKey]['nform'] = str_replace("/>", "checked />", $this->arrFields[$strKey]['nform']);
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
						$strTempOption = str_replace(" selected", "", $strArrValue['option']);
						// this won't work as we are looping the same "template"
						// so we'll add a variable before the foreach. and finish it outside the loop.
						#$arrFields[$strKey]['nform'] = str_replace($strArrValue['option'], $strTempOption, $arrFields[$strKey]['form']);
						$strTempForm = str_replace($strArrValue['option'], $strTempOption, $strTempForm);
						#echo "select request : ".$_REQUEST[$arrValue['name']]."<br />\n";
						#echo "Option Name : ".$strArrValue['name']."<br />\n";
						if (isset($this->arrRequest[$arrValue['name']]))
						{
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
		if ($strValue == null || $strValue == "")
		{
			$this->boolError = true;
			$this->strErrorMsg .= str_replace("[field]", $strName, $this->arrFormConf['errorMsg']['required'])."<br />\n";
			// check fieldText should see if it exists ...
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
			foreach ($this->arrFormConf['validReferer'] as $strValidReferer)
			{
				#echo "Valid Referer : ".$strValidReferer."<br />\n";
				if ($strValidReferer == $_SERVER['HTTP_REFERER'])
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
				#$this->strErrorMsg .= '<span style="color:#900;">Invalid Referer - You must use the same form provided by the host processing it</span><br />';
				$this->strErrorMsg .= $this->arrFormConf['errorMsg']['referer']."<br />\n";
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
		//
		if (!$boolValid)
		{
			$this->boolError = true;
			$this->strErrorMsg .= $this->arrFormConf['errorMsg']['email']."<br />\n";
			if (array_key_exists($strName, $this->arrFormConf['fieldText']))
			{
				$this->strTemplate = str_replace($this->arrFormConf['fieldText'][$strName],$this->arrFormConf['errorWrapper'][0].$this->arrFormConf['fieldText'][$strName].$this->arrFormConf['errorWrapper'][1],$this->strTemplate);
			}
		}
	}
################################################################################
#                                                                              #
################################################################################
} // end class form_process
?>
