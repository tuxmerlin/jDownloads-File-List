<?php
/**
* @version		1.0.0
* @package		Tux Columnists Pro
* @copyright	Copyright (C) 2011 Miguel Tuyaré. All rights reserved.
* @license		GNU/GPL 2.0
* Columnists Pro Module is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

/**
 * Form Field class for the Joomla Framework.
 * @package		Joomla.Framework
 * @subpackage	Form
 * @since		1.6
 */
class JFormFieldJSColor extends JFormField
{
	/**
	 * The form field type.
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JSColor';

	/**
	 * Method to get the field input markup.
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		global $JElementJSColorJSWritten;
		if (!$JElementJSColorJSWritten) 
		{
             $jsFile = dirname(__FILE__) . DS . "js" . DS . "jscolor.js";
             $jsUrl = str_replace(JPATH_ROOT, JURI::root(true), $jsFile);
             $jsUrl = str_replace(DS, "/", $jsUrl);

			$document	= JFactory::getDocument();
			$document->addScript( $jsUrl );

			$JElementJSColorJSWritten = TRUE;
		}

		// Initialize JavaScript field attributes.
		$onchange	= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

		$class		= ' class="color {required:false,hash:true,pickerClosable:true} "';

		return '<input type="text" name="'.$this->name.'" id="'.$this->id.'"' .
				' value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'"' .
				$class.$onchange.'/>';
	}
}
