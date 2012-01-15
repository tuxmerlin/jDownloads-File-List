<?php 
/**
* @version		1.0.0
* @package		Tux Joomla Complement
* @copyright	Copyright (C) 2011 Miguel Tuyaré - Tux Merlín. All rights reserved.
* @license		GNU/GPL 3.0
* Tux Joomla Complement is free software. 
* This version may have been modified pursuant to the GNU General Public License, 
* and as distributed it includes or is derivative of works licensed under 
* the GNU General Public License or other free or open source software licenses.
*/
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldSeparators extends JFormField
{
	/**
	 * Element name	
	 * @access	protected
	 * @var		string
	 */
	protected $type 	= 'Separators';	
	
	/**
	 * Clear space label 
	 */
	public function getLabel() {
		$label = '';
		return $label; 
	}
	
	/**
	 * Otuput News Elements
	*/
	function getInput()
	{
		$class    	= $this->element['class'];		
		$value		= $this->element['value'];
		$title		= $this->element['default'];
		$color		= $this->element['fielName'];		
		// Get Rute and Images From Extension
		$paths 		= $this->element->xpath('//*[@addfieldpath]/@addfieldpath');
		$xml		= $paths[0]; $xml = $xml[0]; $rute = $xml->data($xml).'/img/';
		$urlback	= $rute.'back-tit-'.$color.'.png';		
		$urllogo	= $rute.'logotux.png';
		$urljlogo	= $rute.'logoj.png';
		// Generate HTML output
		$html   ='<p style="clear:both;"></p>';
		switch ($class->data($class))
		{
			case "textdesc":
				$html .='<p style="width:100%;border:1px dotted blue;font-size:90%;text-align:center;">'.JText::_($value).'</p>';
				break;
			case "title":				
				$html .='<p style="width:100%;border:1px solid red;line-height:15px;background: url('.$urlback.')repeat-x;color:#FFF;font-weight:bold;font-variant:small-caps;padding:2px;text-align:center">'.JText::_($title).'</p>';
				break;
			case "fulltext":
				$html .='<p style="width:100%;margin:0;border-top:1px solid red;border-left:1px solid red;border-right:1px solid red;line-height:15px;background: url('.$urlback.') repeat-x;color:#FFF;font-weight:bold;font-variant:small-caps;padding:2px;text-align:center;text-shadow:1px 1px #808080">.: '.JText::_($title).' :.</p>';
				$html .='<p style="width:100%;border:1px solid red;background:#ECECEC;color:#000;padding:0 2px;text-align:justify;font-size:90%">'.JText::_($value).'</p>';
				break;
			case "about":
				$html .='<p style="color:#000053;text-align:center;text-shadow:1px 1px #CCC;">';
				$html .='<img src="'.$urllogo.'" alt="Tux Merlin Extension" title="Tux Merlin Extension" style="float:left;padding:4px;" />';
				$html .= JText::_('TUX_TEXT_FOOT');
				$html .='<img src="'.$urljlogo.'" alt="Powered by Joomla CMS" title="Powered by Joomla" style="float:right;padding:4px;" />';
				$html .='</p>';				
		}		
		return $html;		
	}
	
}
?>