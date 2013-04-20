<?php

class BBM_Listeners_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
	   switch ($templateName) {
	   	case 'help_bb_codes':
			$template->preloadTemplate('help_bbm_bbcodes');
	   		break;
	   	case 'editor':
	   		if(XenForo_Application::get('options')->get('Bbm_Bm_ShowControllerInfo'))
			{
				$template->preloadTemplate('bbm_editor_extra_info');
			}
	   		break;
	   	case 'forum_edit':
	   		if($template instanceof XenForo_Template_Admin && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
	   		{
				$template->preloadTemplate('bbm_forum_edit_bbm_editor');
		   	}
	   		break;
	   	case 'home':
	   		if($template instanceof XenForo_Template_Admin)
	   		{
	   			$template->preloadTemplate('bbm_admin_icon');
	   		}
		}
	}
}