<?php
/**
 * @version 	plg_content_jdownloadsfilelist v2.2 for Joomla 1.6 and Joomla 1.7
 * @release		23:58 jueves, 22 de diciembre de 2011
 * @package		Joomla.Plugin
 * @subpackage	Content.jdownloadsfilelist
 * @copyright   Copyright (C) 2011 Miguel Tuyaré - Tux Merlin. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * If you want to remove the link below it, consider make a donation at www.tuxmerlin.com.ar
 *
 */
defined('_JEXEC') or die;

//Load Joomla library
jimport('joomla.plugin.plugin');


/**
 * Plugin JDownloads File List
 * @procedure	Main Class Helper
 * @package		Joomla.Plugin
 * @subpackage	Content.jdownloadsfilelist
*/
class plgContentJdownloadsfilelist extends JPlugin
{
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Constructor - Public!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Main Load Function - Public!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{		
		// jDownloads config
		$config = $this->getJDConfig();		
		
		// Load Params
		JHTML::_('behavior.tooltip');
		$pluginParams = $this->params;
		$showerr	= $pluginParams->get('depure',0);
		$itemorder	= $pluginParams->get('itemorder','5');
		
		// CSS
		$document = JFactory::getDocument();
		$stylecss = $this->getStyleCSS($pluginParams);
		if (strlen($stylecss)>1)
		{ 
			$document->addCustomTag($stylecss); 
			
		}
		
		// scan!!
		$regex = "#{jd_fl==(.*?)}#s";	
		preg_match_all($regex, $article->text, $matches);
		
		// Exe! 
		foreach( $matches[1] as $id ) 
		{		
			$catlist 	= $this->contentJDFileList_getCategoryData($id);
			$access		= $this->contentJDFileList_check($catlist);				
			if ($access['error'] == 0)
			{				
				$filelist 	= $this->contentJDFileList_getCategoryFiles($catlist->cat_id,$itemorder);					
				$itemid		= $this->contentJDFileList_CalcItemid();		
				$output		= $this->contentJDFileList_createHTML($catlist,$filelist,$pluginParams,$itemid,$config);
			} 
			else 
			{				
				($showerr) ? $output = $access['html'] : '';
			}
			$article->text = str_replace("{jd_fl==$id}", $output, $article->text);
		}
		return true;
	}

	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create Category Object - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/	
	protected function contentJDFileList_getCategoryData ($id) 
	{
		// Sanitize
		$id = (int) $id;		
		if ($id != 0) 
		{
			$query = "SELECT * FROM #__jdownloads_cats WHERE cat_id = ".$id; 
			$db =& JFactory::getDBO();
			$db->setQuery($query);
			$catlist = $db->loadObject();
		} 
		else 
		{
			$catlist = null;
		}		
		return $catlist;
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create Files Objects - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/		
	protected function contentJDFileList_getCategoryFiles ($catid,$itemorder) 
	{	
		// Sanitize
		$catid = (int) $catid;
		switch ($itemorder) 
		{
			case '1':	
				$order = ' ORDER BY file_title ASC';
				break;
			case '2':	
				$order = ' ORDER BY file_title DESC';
				break;
			case '3':	
				$order = ' ORDER BY file_ID ASC';
				break;
			case '4':	
				$order = ' ORDER BY file_ID DESC';
				break;
			default: 
				$order = ' ORDER BY ordering';
		}			
		$db =& JFactory::getDBO();
		$query = "SELECT * FROM #__jdownloads_files WHERE cat_id = ".$catid." AND published=1 {$order}";
		$db->setQuery($query);	
		$filelist = $db->loadObjectList();
		return $filelist;
	}

	
	/**
	* Plugin JDownloads File List
	* @Procedure	ItemID for jDownload Component - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function contentJDFileList_CalcItemid()
	{
		$db =& JFactory::getDBO();		
		$query= "SELECT id from #__menu WHERE link like '%index.php?option=com_jdownloads%' and published = 1";
		$db->setQuery($query);	
		$itemid = $db->loadObject();
		($itemid) ? $itemid = $itemid->id : $itemid = 0;
		return $itemid;	
	}

	
	/**
	* Plugin JDownloads File List
	* @Procedure	Crete HTML Output - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function contentJDFileList_createHTML (&$catlist, &$filelist, &$pluginParams, &$itemid, &$config) 
	{
		// See is jDownloads Component is installed and published!!!
		if (!empty($itemid))
		{
			/* Component off line */
			if ($config['offline'] == '1' ):
				$html = $config['offline.text'];
				return $html;			
			endif;
										
			/* No Styles Params */	
			$intro 		= $pluginParams->get('showintrotext','0');	
			$introtit 	= $pluginParams->get('introtit','');	
			$introdesc 	= $pluginParams->get('introdesc','');	
			$ctit		= $pluginParams->get('jdcattit',1);
			$cdesc		= $pluginParams->get('cdescription','0');
			$cdcode		= $pluginParams->get('ccleancode','0');
			$cdword		= (int) $pluginParams->get('cwordlimit',0);				
			$cdimg		= $pluginParams->get('cimage',1);
			if ($cdimg == 1)
			{ 
				$imgz = getimagesize(JURI::base().'images/jdownloads/catimages/'.$catlist->cat_pic); 
				$heigmin=$imgz[1]+4;
			}
			$countd	 	= $pluginParams->get('countdown');	
			$tittext 	= $pluginParams->get('titletext');
			$pag 	 	= $pluginParams->get('pagination');
			$imgbull 	= JURI::Base().'media/system/images/arrow.png';
			$typedesc	= $pluginParams->get('fdesctype',1);
			$fcode		= $pluginParams->get('fcleancode',0);
			$fdesc		= $pluginParams->get('fdescription',1);			
			
			$html   = '<div id="tuxjdfilelist">';		
			
			// Intro Category
			if ($intro == '1')
			{
				(strlen($introtit)>1) 	? $html  .= '<div class="tuxjdfl-introtit"><h3>'.$introtit.'</h3></div>' : '';
				(strlen($introdesc)>1) 	? $html  .= '<div class="tuxjdfl-introdesc">'.$introdesc.'</div>' : '';
			}
						
			// jDownloads Title Category
			($ctit) ? $html  .= '<div class="tuxjdfl-titcat"><h3>'.$catlist->cat_title.'</h3></div>' : '';
			
			// jDownloads Category Description
			if ($cdesc == 1)
			{					
				$html .='<div class="tuxjdfl-textcat" style="min-height:'.$heigmin.'px;padding:2px">';
				
				// If show image
				if ($cdimg == 1) 
				{ 
					$html .='<div style="padding:2px;float:left;"><img src="'.JURI::base().'images/jdownloads/catimages/'.$catlist->cat_pic.'" alt="'.$catlist->cat_title.'" title="'.$catlist->cat_title.'"/></div>';
				}
				
				if ($cdword != 0) 
				{
					$linkcat = JRoute::_( "index.php?option=com_jdownloads&Itemid=".$itemid."&view=viewcategory&catid=".$catlist->cat_id);
					$html .= $this->cutWord(strip_tags($catlist->cat_description),$cdword,$linkcat);
				} 
				else 
				{				
					($cdcode == 1) ? $html .= strip_tags($catlist->cat_description) : $html .= $catlist->cat_description ;
				}
				$html .='</div>';
			}	
			$tiptip = JText::_('JDOWNLOADSTIP_TITLE').'::'.JText::_('JDOWNLOADSTIP_HELP');
			if ($pag == '1')
			{
				// With Pagination
				$x = 0;
				$pag=1;
				$html .= "<div id='tuxaccordion'>"; 		
				$html .= '<h3 class="titletoggler"><img src="'.$imgbull.'" alt=" "/> '.$tittext.': '.$pag.'</h3>'
					. '<div class="elementcontent">';
				foreach ($filelist as $file)
				{					
					$linkfile ='<a href="'.JRoute::_('index.php?option=com_jdownloads&Itemid='.$itemid.'&view=view.download&catid='.$file->cat_id.'&cid='.$file->file_id).'">'.$file->file_title.'</a>';
					if($x == $countd)
					{						
						$pag=$pag+1;
						$html .='</ul>';		
						$html .= '</div>';
						$html .= '<h3 class="titletoggler"><img src="'.$imgbull.'" alt=" "/> '.$tittext.': '.$pag.'</h3>'
							. '<div class="elementcontent">';
						$x = 0;
					}
					($x == 0) ? $html  .= '<ul class="tuxjdfl-list">' : '';				
					$html .= '<li class="tuxjdfl-listli"><span title="'.$tiptip.'" class="editlinktip hasTip tuxjdfl-links">'.$linkfile.'</span></li>';		
					if ($fdesc == 1)
					{				
						$html .='<div class="tuxjdfl-fdesc">';
						if ($typedesc == 1) 
						{
							($fcode == 1) ? $html .= strip_tags($file->description) : $html .= $file->description ;
						} 
						else 
						{
							($fcode == 1) ? $html .= strip_tags($file->description_long) : $html .= $file->description_long ;
						}
					}					
					$html .= '<br />'.$this->getIcons($file,$pluginParams,$config);
					$html .= '</div>';					
					$x++;
				}
				$html .='</div>';
				$html .='</div>';
			} 
			else 
			{
				// Without pagination
				$html  .= '<ul class="tuxjdfl-list">';
				foreach ($filelist as $file)
				{
					$linkfile='<a href="'.JRoute::_('index.php?option=com_jdownloads&Itemid='.$itemid.'&view=view.download&catid='.$file->cat_id.'&cid='.$file->file_id).'">'.$file->file_title.'</a>';
					$html .= '<li class="tuxjdfl-listli"><span title="'.$tiptip.'" "class="editlinktip hasTip tuxjdfl-links">'.$linkfile.'</span></li>';
					if ($fdesc == 1)
					{
						$html .='<div class="tuxjdfl-fdesc">';
						if ($typedesc == 1) 
						{
							($fcode == 1) ? $html .= strip_tags($file->description) : $html .= $file->description ;
						} 
						else 
						{
							($fcode == 1) ? $html .= strip_tags($file->description_long) : $html .= $file->description_long ;
						}
						$html .='</div>';
					}
				}
				$html  .= '</ul>';
			}	
			// If you remove this line, you must make a donation - See header of this file
			$html .='</div>';
			$html .= base64_decode('PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Zm9udC1zaXplOjg1JSI+PHNwYW4gdGl0bGU9IlR1eCBNZXJsaW4gRXh0ZW5zaW9uczo6SWYgeW91IGxpa2UgdGhpcyBleHRlbnNpb24gcGxlYXNlIGRvbmF0ZSIgY2xhc3M9ImVkaXRsaW5rdGlwIGhhc1RpcCI+PGEgaHJlZj0iaHR0cDovL3d3dy50dXhtZXJsaW4uY29tLmFyIiB0YXJnZXQ9Il9ibGFuayIgc3R5bGU9InRleHQtZGVjb3JhdGlvbjpub25lO2NvbG9yOiNDQ0NDQ0M7Ij5Qb3dlcmVkIGJ5IFR1eCBNZXJsw61uIEV4dGVuc2lvbnM8L2E+PC9zcGFuPjwvZGl2Pg==');
		} 
		else 
		{
			$html   = '<div class="jdfilelist" style="border: '.$pborder.';padding: '.$psepa.'px;">';
			$html  .= '<p><span style="color:#FF0000">WARNING!! - NO EXIST JDOWNLOADS COMPONENT</span></p><p>jDownloads File List Plugins not work</p>';
		}		
		$html .= '</div><br />';			
		return $html;  
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Cut Words - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function cutWord($words,$length,$linkcat) 
	{
		if (strlen($words) > $length) 
		{
			$words = substr($words, 0, $length);
			$last_space = strrpos($words, " ");         
			$words = substr($words, 0, $last_space);
			$words .= '...(<a href="'.$linkcat.'">'.JText::_('VIEW_MORE_IN_SECTION').'</a>)'; 
		}
		return $words;
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create Icons Line  - protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function getIcons ($file,$params,$config)
	{
	
		$html = '';			
		// Rutes
		$imgf		= JURI::base().'images/jdownloads/fileimages/';
		$imgm		= JURI::base().'images/jdownloads/miniimages/';
		$is			= $config['info.icons.size'];
		// Data		
		$license 	= $this->getJDLicenses($file->license);		
		$lt			= $license->license_title; 
		$lu			= $license->license_url; 
		$filepic	= $file->file_pic;
		$author		= $file->author;
		$urlauthor	= $file->url_author;		
		$urlhome	= $file->url_home;
		$date		= $file->date_added;
		$size		= $file->size;
		$count		= $file->downloads;		
		$lca		= $params->get('link_cform',1);
		$linkauthor = '';
		if ($lca)
		{
			if (substr_count($urlauthor, '@') == 1)
			{
				$linkauthor = $this->isJoomlaContact($urlauthor);			
				if ($linkauthor->email_to !='')
				{
					$linkauthor = JRoute::_('index.php?option=com_contact&view=contact&id='.$linkauthor->id.':'.$linkauthor->alias.'&catid='.$linkauthor->catid);
				} 
				else 
				{
					$linkauthor = '';
				}
			}
		}
		// Language text
		$langs		= explode(',',$config['language.list']);
		$langtext	= $langs[(int)$file->language];
		// System text
		$syss		= explode(',',$config['system.list']);
		$systext	= $syss[(int)$file->system];		
		//Load downloads params
		$plic		= $params->get('show_lic',1);
		$pauthor	= $params->get('show_author',1);
		$pweb		= $params->get('show_web',1);
		$pdate		= $params->get('show_date',1);
		$plang		= $params->get('show_lang',1);		
		$psys		= $params->get('show_version',1);
		$psize		= $params->get('show_size',1);
		$pcount		= $params->get('show_count',1);
		
		$html .='<small>';		
		// License
		if($plic) 
		{
			$lictip = JText::_('PLJD_LIC_TITLE').'::'.JText::_('PLJD_LIC_TITLE_HELP');
			$html .=' <span title="'.$lictip.'" class="editlinktip hasTip"><img src="'.$imgm.'license.png" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px"/>';
			(strlen($lu)>1) ? $html .=' <a href="'.$lu.'" target="_blank">'.$lt.'</a></span>' : $html .= ' '.$lt.'</span>';
		}
		// Author		
		if($pauthor) 
		{
			$auttip = JText::_('PLJD_AUT_TITLE').'::'.JText::_('PLJD_AUT_TITLE_HELP');
			if (strlen($urlauthor)>1) 
			{
				$html .=' <span title="'.$auttip.'" class="editlinktip hasTip"><img src="'.$imgm.'contact.png" alt="author" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px" />';
				if (substr_count($urlauthor, '@') == 1)
				{
					if ($linkauthor != '')
					{					
						$mail = '<a href="'.$linkauthor.'">'.$author.'</a>';
					} 
					else 
					{
						$mail = $this->tuxEmailProtected($urlauthor,$author);
					}					
					(strlen($urlauthor)>1) ? $html .=' '.$mail.'</span>' : '</span>';
				} 
				else 
				{
					(strlen($urlauthor)>1) ? $html .=' <a href="http://'.$urlauthor.'" target="_blank">'.$author.'</a></span>' : $html .= ' '.$author.'</span>';
				}
			}
		}
		// Author Web		
		if($pweb) 
		{
			$webtip = JText::_('PLJD_WEB_TITLE').'::'.JText::_('PLJD_WEB_TITLE_HELP');
			if (strlen($urlhome)>1) 
			{
				$html .=' <span title="'.$webtip.'" class="editlinktip hasTip"><img src="'.$imgm.'weblink.png" style="vertical-align: middle;" alt="web page" width="'.$is.'px" height="'.$is.'px" />';
				$html .=' <a href="http://'.$urlhome.'" target="_blank">'.JText::_('WEB_PAGE').'</a></span>';
			}
		}
		// Date		
		if($pdate) 
		{
			$datetip = JText::_('PLJD_DATE_TITLE').'::'.JText::_('PLJD_DATE_TITLE_HELP');
			$html .=' <span title="'.$datetip.'" class="editlinktip hasTip"><img src="'.$imgm.'date.png" style="vertical-align: middle;" alt="date" width="'.$is.'px" height="'.$is.'px" />';
			$html .= ' '.JHTML::Date($date,JText::_('DATE_FORMAT_LC4')).'</span>';
		}
		// Language		
		if($plang) 
		{
			if (strlen($langtext)>1)
			{
				$lantip = JText::_('PLJD_LAN_TITLE').'::'.JText::_('PLJD_LAN_TITLE_HELP');
				$html .=' <span title="'.$lantip.'" class="editlinktip hasTip"><img src="'.$imgm.'language.png" alt="language" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px" title="'.$langtext.'" alt="'.$langtext.'"/> '.$langtext.'</span>';
			}
		}
		// System		
		if($psys) 
		{
			if (strlen($systext)>1)
			{
				$systip = JText::_('PLJD_SYS_TITLE').'::'.JText::_('PLJD_SYS_TITLE_HELP');
				$html .=' <span title="'.$systip.'" class="editlinktip hasTip"><img src="'.$imgm.'system.png" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px" title="'.$systext.'" alt="'.$systext.'"/> '.$systext.'</span>';
			}
		}
		// Filesize		
		if($psize) 
		{
			$siztip = JText::_('PLJD_SIZ_TITLE').'::'.JText::_('PLJD_SIZ_TITLE_HELP');
			$html .=' <span title="'.$siztip.'" class="editlinktip hasTip"><img src="'.$imgf.$filepic.'" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px" />';
			$html .= ' '.$size.'</span>';
		}
		// Count		
		if($pcount) 
		{
			$counttip = JText::_('PLJD_COUNT_TITLE').'::'.JText::_('PLJD_COUNT_TITLE_HELP');
			$html .=' <span title="'.$counttip.'" class="editlinktip hasTip"><img src="'.$imgm.'download.png" style="vertical-align: middle;" width="'.$is.'px" height="'.$is.'px" />';
			$html .= ' '.$count.'</span>';
		}
		$html .='</small>';
		return $html;
	}
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create Accordion Mootools and CSS - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function createAcc($back1,$back2,$backtit,$coltit,$loadcss)
	{
		(strlen($back1)<6) ? $back1 = '#000000' : '';
		(strlen($back2)<6) ? $back2 = '#000000' : '';
		$document = JFactory::getDocument();		
		// Load Mootools Accordion
		$header = "<script type='text/javascript' >"
					."window.addEvent('domready', function( ){ "
					."   var myAccordion = new Accordion($('tuxaccordion'), 'h3.titletoggler', 'div.elementcontent', { "
					."   opacity: false, "
					."   onActive: function(toggler, element){ "
					." toggler.setStyle('color', '".$back1."'); "
					."},"
					." onBackground: function(toggler, element){ "
					." toggler.setStyle('color', '".$back2."');"
					."}"
					."});"
					."});"
					."</script>";
		$document->addCustomTag($header);
		
		if ($loadcss)
		{
			// Load CSS
			$css  = '<style type="text/css">
					#tuxaccordion {
						margin:0px 0px;
					}
					h3.titletoggler {
						cursor: pointer;
						border: 1px solid #bbb;
						border-right-color: #ddd;
						border-bottom-color: #ddd;
						font-family: "Andale Mono", sans-serif;
						font-size: 12px; ';
	
					(strlen($backtit)==7) ? $css .= 'background: '.$backtit.'; ' : $css .='';
					(strlen($coltit)==7) ? $css .= 'color: '.$coltit.'; ' : $css .='color: #000000; ';
	
			$css .='	margin: 0 0 4px 0;
						padding: 3px 5px 1px;
					}
			
					div.elementcontent p, div.elementcontent h4 {
						margin:0px;
						padding:4px !important;
						}
					
					.elementcontent{
						padding:5px;
					}
					
					</style>';
			$document->addCustomTag($css);
		}
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create full styles CSS - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/	
	protected function getStyleCSS($params)
	{
		$style = '';
		$pag 	 	= $params->get('pagination',1);
		$localcss	= trim($params->get('loadcss',''));
		if (strlen($localcss)>1) 
		{
			$style = $this->getStyleName($localcss);
			return $style;
		}
		$tpl		= $params->get('jdfl-template');
		if ( $tpl==0) 
		{
			// Pagination			
			$back1	 = $params->get('backg1','#000000');	
			$back2	 = $params->get('backg2','#CCCCCC');		
			$backtit = $params->get('backgtitle','#CCCCCC');
			$coltit  = $params->get('colortitle');		
			$okcss	 = true;
			($pag == 1) ? $this->createAcc($back1,$back2,$backtit,$coltit,$okcss) : null;
			
			// Global plugin styles
			$pborder	= $params->get('pborderstyle','none');
			$pbckg 		= $params->get('pbackground','none');
			$psepa		= $params->get('pmargin','10px');
			$style 	.= '#tuxjdfilelist{
						border:'.$pborder.';
						background:'.$pbckg.';
						padding:'.$psepa.';
					}';
			
			// Download list style
			$ltype		= $params->get('listtype',0);
			$limg		= $params->get('listimage','media/system/images/arrow.png');
			$lmar		= $params->get('marginlist','0 0 0 15px');
			$slinks		= $params->get('stylelinks','');
			$llist		= $params->get('linelist','15px');
			if ($ltype == 'img')
			{
				$style 	.= '#tuxjdfilelist .tuxjdfl-list{
							list-style-image: url('.JURI::base(true).'/'.$limg.'); 
							padding: '.$lmar.'
							}							
							';
			} 
			else 
			{
				$style 	.= '#tuxjdfilelist .tuxjdfl-list{
							list-style-type: '.$ltype.'; 
							padding: '.$lmar.'
							}';						
			}
			$style .= '#tuxjdfilelist .tuxjdfl-list a{'.$slinks.';line-height:'.$llist.';text-decoration:none}';			
			
			// Intro Tit Category Styles
			$itstyle 	= $params->get('introtitstyle',1);
			$itshadow 	= $params->get('introtitshadow',0);
			$itshadows 	= $params->get('introtitshadows','1px');
			$itshadowc 	= $params->get('introtitshadowc','#CCCCCC');
			$italign	= $params->get('introtitalign','left');
			$itsize 	= $params->get('introtitsize','15px');
			$itfont		= $params->get('introtitfont','');
			$itcolor	= $params->get('introtitcolor','#000000');	
			$itback	 	= $params->get('introtitback','none');	
			$itborder	= $params->get('introtitborder','none');	
			$itcss		= $this->getComStyles($itsize,$italign,$itcolor,$itback,$itborder,$itstyle,$itshadow,$itshadows,$itshadowc,$itfont);
			$style 	.= '#tuxjdfilelist .tuxjdfl-introtit{'.$itcss.'}';
								
			// Intro Text Category Styles
			$istyle 	= $params->get('introstyle',1);
			$ishadow 	= $params->get('introshadow',0);
			$ishadows 	= $params->get('introshadows','1px');
			$ishadowc 	= $params->get('introshadowc','#CCCCCC');
			$ialign		= $params->get('introalign','left');
			$isize 		= $params->get('introsize','10px');
			$ifont		= $params->get('introfont','');				
			$icolor	 	= $params->get('introcolor','#000000');	
			$iback	 	= $params->get('introback','none');	
			$iborder	= $params->get('introborder','none');	
			$icss		= $this->getComStyles($isize,$ialign,$icolor,$iback,$iborder,$istyle,$ishadow,$ishadows,$ishadowc,$ifont);
			$style 	.= '#tuxjdfilelist .tuxjdfl-introdesc{'.$icss.'}';
			
			// jDownloads Category title
			$ctsize		= $params->get('titlecatsize','15px');
			$ctfont		= $params->get('titlecatfont','');
			$ctstyle	= $params->get('titlecatstyle',1);
			$ctalign	= $params->get('titlecatalign','left');
			$ctcolor	= $params->get('titlecatcolor','#000000');
			$ctback		= $params->get('titlecatback','none');
			$ctshaw		= $params->get('titlecatshadow',0);
			$ctshaws	= $params->get('titlecatshadows','1px');
			$ctshawc	= $params->get('titlecatshadowc','#CCCCCC');
			$ctborder	= $params->get('titlecatborder','none');				
			$ctcss		= $this->getComStyles($ctsize,$ctalign,$ctcolor,$ctback,$ctborder,$ctstyle,$ctshaw,$ctshaws,$ctshawc,$ctfont);
			$style 	.= '#tuxjdfilelist .tuxjdfl-titcat{'.$ctcss.'}';
			
			// Category description			
			$cdfsiz  	= $params->get('cdescfontsize','10px');
			$cdescf		= $params->get('cdescfont','');	
			$cdescs		= $params->get('cdescstyle',1);
			$cdesca		= $params->get('cdescalign','left');
			$cdfcol  	= $params->get('cdescfontcolor','#000000');	
			$cdback  	= $params->get('cbackdesc','none');
			$cdshaw		= $params->get('cdescshadow',0);
			$cdshaws	= $params->get('cdescshadows','1px');
			$cdshawc	= $params->get('cdescshadowc','#CCCCCC');
			$cdbord  	= $params->get('cborddesc','none');
			$cdcss		= $this->getComStyles($cdfsiz,$cdesca,$cdfcol,$cdback,$cdbord,$cdescs,$cdshaw,$cdshaws,$cdshawc,$cdescf);
			$style 	.= '#tuxjdfilelist .tuxjdfl-textcat{'.$cdcss.'}';
			
			// jDownloas Files				
			$ffsiz  	= $params->get('fdescfontsize','10px');
			$ffont		= $params->get('fdescfont','');
			$ffsty		= $params->get('fdescfontstyle',1);
			$falign		= $params->get('fdescalign','left');
			$ffcol 		= $params->get('fdescfontcolor','#000000');	
			$fback  	= $params->get('fbackdesc','#FFFFFF');
			$fshaw		= $params->get('fdescshadows',0);
			$fshaws		= $params->get('fdescshadows','1px');
			$fshawc		= $params->get('fdescshadowc','#CCCCCC');
			$fbord  	= $params->get('fborddesc','none');
			$fdcss		= $this->getComStyles($ffsiz,$falign,$ffcol,$fback,$fbord,$ffsty,$fshaw,$fshaws,$fshawc,$ffont);
			$style 	.= '#tuxjdfilelist .tuxjdfl-fdesc{'.$fdcss.'}';
					
		} 
		else 
		{			
			$okcss	= false;
			$back1	= '';
			$back2	= '';
			switch ($tpl)
			{
				case 1: 
					$style = $this->getStyleName('basic');
					if ($style=='') 
					{
						$style = $this->setStyleName('default');					
					} 
					$back1 = '#000000';
					$back2 = '#000000';
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 2: 
					$style = $this->getStyleName('bluescale');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					$back1 = '#000000';
					$back2 = '#000000';
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 3: 
					$style = $this->getStyleName('greenscale');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					else 
					{
						$back1 = '#005D00';
						$back2 = '#00B000';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 4: 
					$style = $this->getStyleName('redscale');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					else 
					{
						$back1 = '#6B000A';
						$back2 = '#FFA0A0';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 5: 
					$style = $this->getStyleName('joomla');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					else 
					{
						$back1 = '#FFFFFF';
						$back2 = '#CCCCCC';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 6: 
					$style = $this->getStyleName('platonic');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					else 
					{
						$back1 = '#000000';
						$back2 = '#000000';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 7: 
					$style = $this->getStyleName('aristocratic');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');						
					} 
					else 
					{
						$back1 = '#000000';
						$back2 = '#000000';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 8: 
					$style = $this->getStyleName('inlove');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');					
					} 
					else 
					{
						$back1 = '#000000';
						$back2 = '#000000';
					}					
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 9: 
					$style = $this->getStyleName('dark');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');
					}
					$back1 = '#FFFFFF';
					$back2 = '#FFFFFF';
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
				case 10: 
					$style = $this->getStyleName('merlin');
					if ($style=='') 
					{
						$style = $this->getStyleName('default');
					} 
					else 
					{					
						$back1 = '#FFFFFF';
						$back2 = '#808080';
					}
					($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
					break;
			}				
		}
		if ( $tpl== 0) 
		{
			$css = '<style type="text/css">'.$style.'</style>';
		} 
		else 
		{
			$okcss = false;
			$back1 = '#000000';
			$back1 = '#FFFFFF';
			($pag == 1) ? $this->createAcc($back1,$back2,null,null,$okcss) : null;
			$css = $style;
		}
		return $css;
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Create commons styles CSS - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function getComStyles($size,$align,$color,$bcolor,$border,$fstyle,$shadow,$shadows,$shadowc,$font)
	{
		$css ='';	
		if ($font) 
		{
			$document = JFactory::getDocument();
			$linkfont = "<link href='http://fonts.googleapis.com/css?family=".$font."' rel='stylesheet' type='text/css'>";
			$document->addCustomTag($linkfont);
			$css .="font-family:'".$font."';";		
		}		
		($size) 	? $css .='font-size:'.$size.';' : '';
		($align) 	? $css .='text-align:'.$align.';' : '';
		($color) 	? $css .='color:'.$color.';' : '';
		($bcolor) 	? $css .='background:'.$bcolor.';' : '';
		($border) 	? $css .='border:'.$border.';' : '';
		switch ($fstyle)
		{
			case 1: 
				$css .= 'font-style: normal; '; 
				break;
			case 2: 
				$css .= 'font-style: italic; '; 
				break;
			case 3: 
				$css .= 'text-transform: capitalize; '; 
				break;
			case 4: 
				$css .= 'text-transform: minimize; '; 
				break;
			case 5: 
				$css .= 'font-style: underline; '; 
				break;
			case 6: 
				$css .= 'font-variant: small-caps; '; 
				break;
			default: 
				$css .= 'text-style: normal; '; 
				break;
		}	
		($shadow) ? $css .='text-shadow:'.$shadows.' '.$shadows.' '.$shadowc.';' : '';
		return $css;	
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Veryfy Templates and create link - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function getStyleName($name)
	{
		jimport( 'joomla.filesystem.file' );
		$csslink='';
		$file 	= JURI::base().'plugins/content/jdownloadsfilelist/css/'.$name.'.css';
		$verify = JPATH_SITE.DS.'plugins'.DS.'content'.DS.'jdownloadsfilelist'.DS.'css'.DS.$name.'.css';
		$ver = JFile::exists($verify);
		if (JFile::exists($verify))
		{			
			$csslink = '<link rel="stylesheet" href="'.$file.'" type="text/css" />';			
		} 		
		return $csslink;
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Check rights - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/		
	protected function contentJDFileList_check($catlist)
	{
		$error		= array();
		$html		= '';
		$valid		= 0;
		if ($catlist != null) 
		{			
			$publish	= $catlist->published;
			$_access 	= $catlist->cat_access;			
			$gaccess	= $catlist->cat_group_access;
			$html      .= "<div style='color:#000;font-weight:bold;background:yellow;text-align:center;'>".JText::_('JDOWNLOADSFILELISTPLUGIN_MESSAGE').": ".$catlist->cat_title."</div>";
			$html	   .= "<div style='background:#A10000;color:#FFF;text-align:left;border:1px solid #CCC'>";
		} 
		else
		{
			$html      .= "<div style='color:#000;font-weight:bold;background:yellow;text-align:center;'>".JText::_('JDOWNLOADSFILELISTPLUGIN_MESSAGE')."</div>";
			$html	   .= "<div style='background:#A10000;color:#FFF;text-align:center;border:1px solid #CCC'>";
			$html	   .= JText::_('YOU_MUST_INDICATE_ALMOST_ONE_CATEGORY_ID')."</div>";
			$error['html']  = $html;
			$error['error'] = 1;
			return $error;			
		}
		
		$html	   .= "<ul>";
		//Test published
		if ($publish == 0) 
		{
			$html	.= "<li>".JText::_('CATEGORY_NOT_PUBLISHED')."</li>"; 
			$valid	= $valid+1;
		} 
		else 
		{
			$html	.= "<li>".JText::_('CATEGORY_PUBLISHED')."</li>";
			$valid	= $valid;
		}
		//Test user type
		switch ($_access)
		{
			case '11': 
				$html  .="<li>".JText::_('CATEGORY_ONLY_REGISTER')."</li>"; 
				$valid	= $valid+1;
				break;
			case '22': 
				$html  .="<li>".JText::_('CATEGORY_ONLY_SUPERADMIN')."</li>"; 
				$valid	= $valid;
				break;
			default:
				$html  .=''; 				
				$valid	= $valid;		
		}		
		//Test user group
		if ($gaccess != 0)
		{
			$group	= $this->getJdownloadsGroup($gaccess);
			$html  .="<li>".JText::_('CATEGORY_ONLY_FOR_GROUP_NAME').': '.$group->groups_name."</li>"; 
			$valid	= $valid+1;
		} 
		else 
		{
			$html  .=''; 
			$valid	= $valid;
		}		
		$html .="</ul></div>";
		$error['html']  = $html;
		$error['error'] = $valid;
		return $error;	
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	jDownloads Group - Protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/		
	protected function getJdownloadsGroup ($id) 
	{
		// Sanitize
		$id = (int) $id;
		$db =& JFactory::getDBO();
		$query = "SELECT * FROM #__jdownloads_groups WHERE id = ".$id; 
		$db->setQuery($query);
		$group = $db->loadObject();
		return $group;
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	jDownloads Configurations  - protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function getJDConfig ()
	{
		$database = &JFactory::getDBO();
		$jlistConfig = array();
		$database->setQuery("SELECT setting_name, setting_value FROM #__jdownloads_config");
		$jlistConfigObj = $database->loadObjectList();
		if(!empty($jlistConfigObj))
		{
			foreach ($jlistConfigObj as $jlistConfigRow)
			{
				$jlistConfig[$jlistConfigRow->setting_name] = $jlistConfigRow->setting_value;
			}
		}
		return $jlistConfig;		
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	jDownloads Licences  - protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function getJDLicenses ($id)
	{
		$db = &JFactory::getDBO();		
		$db->setQuery("SELECT license_title, license_url FROM #__jdownloads_license WHERE id=".$id);
		$license = $db->loadObject();
		return $license;		
	}
	
	
	/**
	* Plugin JDownloads File List
	* @Procedure	Cipher Javascript Email  - protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist	
	*/	
	protected function tuxEmailProtected($email,$show) 
	{
		$character_set  = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'; 
		$key = str_shuffle($character_set); 
		$cipher_text = ''; 
		$id = 'e'.rand(1,999999999); 
		for ($i=0;$i<strlen($email);$i+=1) 
			$cipher_text.= $key[strpos($character_set,$email[$i])]; 
			$script = 'var a="'.$key.'";var b=a.split("").sort().join("");var c="'.$cipher_text.'";var d="";var z="'.$show.'";'; 
			$script.= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));'; 
			$script.= 'document.getElementById("'.$id.'").innerHTML="<a href=\\"mailto:"+d+"\\">"+z+"</a>"'; 
			$script = "eval(\"".str_replace(array("\\",'"'),array("\\\\",'\"'), $script)."\")"; 
			$script = '<script type="text/javascript">/*<![CDATA[*/'.$script.'/*]]>*/</script>'; 
			$hemail = '<span id="'.$id.'">[javascript protected email address]</span>'.$script;
			return  $hemail;
	}
	

	/**
	* Plugin JDownloads File List
	* @Procedure	jDownloads Search for Contact  - protected!
	* @package		Joomla.Plugin
	* @subpackage	Content.jdownloadsfilelist
	*/
	protected function isJoomlaContact ($email)
	{
		$email = trim($email);		
		$db = &JFactory::getDBO();		
		$db->setQuery("SELECT * FROM #__contact_details WHERE email_to='$email'");
		$contact = $db->loadObject();
		return $contact;		
	}
	
} 
// End class
?>