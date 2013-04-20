<?php

class BBM_ControllerAdmin_Buttons extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$configs = $this->_getButtonsModel()->getAllConfig('strict');
		$configs = $this->_addPhrasesToConfigs($configs);

		if(!is_array($configs))
		{
			$configs = '';
		}

		$viewParams = array(
			'configs' => $configs,
			'permsBbm' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
		);

		return $this->responseView('Bbm_ViewAdmin_Buttons_Homepage', 'bbm_buttons_homepage', $viewParams);
	}

	public function actionAddEditConfig()
	{
		$config = array();
		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
		
		if($config_id)
		{
			$config = $this->_getBbmConfigOrError($config_id);
		}

		return $this->_actionAddEditConfig($config);
	}

	protected function _addPhrasesToConfigs($configs)
	{
		if(is_array($configs))
		{
			foreach($configs as &$config)
			{
				if(isset($config['config_type']))
				{
					$title = 'button_manager_config_' . $config['config_type'];
					$config['phrase'] = new XenForo_Phrase($title);
				}
			}
		}
		
		return $configs;
	}

	protected function _getBbmConfigOrError($config_id)
	{
		$config = $this->_getButtonsModel()->getConfigById($config_id);

		if (!$config)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_not_found'), 404));
		}

		if(is_array($config) && isset($config['config_type']))
		{
			$title = 'button_manager_config_' . $config['config_type'];
			$config['phrase'] = new XenForo_Phrase($title);
		}

		return $config;
	}

	protected function _actionAddEditConfig($config)
	{
      		$viewParams = array(
      			'config' => $config
      		);
      		return $this->responseView('Bbm_ViewAdmin_Config_Add_Edit', 'bbm_buttons_config_add_edit', $viewParams);
	}

	public function actionSaveConfig()
	{
      		$this->_assertPostOnly();

      		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
      		$config_type = $this->_input->filterSingle('config_type', XenForo_Input::STRING);
      		$config_type_phrase = $this->_input->filterSingle('config_type_phrase', XenForo_Input::STRING);

      		$dw = XenForo_DataWriter::create('BBM_DataWriter_Buttons');

    		if (!empty($config_id) || $this->_getButtonsModel()->getConfigById($config_id))
    		{
      			$dw->setExistingData($config_id);
    		}

      		$dw->set('config_type', $config_type);
		$dw->setExtraData(BBM_DataWriter_Buttons::DATA_TYPE_PHRASE, $config_type_phrase);      		
      		$dw->preSave();
      		$dw->save();

      		return $this->responseRedirect(
      			XenForo_ControllerResponse_Redirect::SUCCESS,
      			XenForo_Link::buildAdminLink('bbm-buttons')
		);	
	}

	public function actionDeleteConfig()
	{
		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);	

		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'BBM_DataWriter_Buttons', 'config_id',
				XenForo_Link::buildAdminLink('bbm-buttons')
			);
		}
		else
		{
			$config = $this->_getBbmConfigOrError($config_id);

			$viewParams = array(
				'config' => $config
			);
			return $this->responseView('Bbm_ViewAdmin_Buttons_Delete', 'bbm_buttons_delete', $viewParams);
		}
	}

	public function actionEditorConfigltr()
	{
		return $this->_editorConfig('ltr');
	}

	public function actionEditorConfigrtl()
	{
		return $this->_editorConfig('rtl');
	}
	
	public function actionEditorCust()
	{
		if (isset($_GET['config_type']))
		{
			//Edit from bbm buttons manager
			$config_type = $_GET['config_type'];
			$datas = $this->_getButtonsModel()->getConfigByType($config_type);
		}
		else
		{
			//Edit from bbm bbcodes manager
			$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
			$datas = $this->_getButtonsModel()->getConfigById($config_id);
		}
	
		if (!$datas || !isset($datas['config_type']))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_not_found'), 404));
		}	

		return $this->_editorConfig($datas['config_type']);
	}

	protected function _editorConfig($configType)
	{
		//Get config and all buttons
		$xen = $this->_xenButtons($configType);
		$buttons = $this->_getBbmBbCodeModel()->getBbCodesWithButton();
		$buttons = $this->_addButtonClass($buttons);
		$config =  $this->_getButtonsModel()->getConfigByType($configType);

		/***
		*	Look inside config which buttons are already inside the editor and take them back from the buttons list
		***/
		if(empty($config['config_buttons_full']))
		{
			$lines = $xen['blankConfig']; //If the config is blank let's insert the default XenForo Configuration
		}
		else
		{
			$buttons = array_merge($xen['Buttons'], $buttons);
			$configFull = unserialize($config['config_buttons_full']);
	
	      		foreach($configFull as $key => $selectedButton)
	      		{
				if(!isset($selectedButton['tag']))
				{
					unset($configFull[$key]);//Fix
					continue;
				}
	
	      			if((!in_array($selectedButton['tag'], array('separator', 'carriage'))) AND !isset($buttons[$selectedButton['tag']]))
	      			{
	      				//If a button has been deleted from database, hide it from the the selected button list (It shoudn't happen due to actionDelete function)
	      				unset($configFull[$key]);
	      			}
	      			else
	      			{
	      				//Hide all buttons which are already used from the available buttons list
	      				unset($buttons[$selectedButton['tag']]);
	      			}
	      		}
			
			//Add button class to the config group too (fix a bug when an orphan button was edited directly from the button manager)
			$configFull = $this->_addButtonClass($configFull);
	
			//Create a new array with the line ID as main key 
			$lines = array();
			$line_id = 1;
			
			foreach($configFull as $button)
			{
				if($button['tag'] == 'carriage')
				{
					$line_id++;
				}
				else
				{
					$lines[$line_id][] = $button;
				}
			}
		}

		$viewParams = array(
			'codes' => $buttons,
			'config' => $config,
			'lines' => $lines,		
			'permissions' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
 		);
 		
		return $this->responseView('Bbm_ViewAdmin_Buttons_Config', 'bbm_buttons_config', $viewParams);
	}

	public function actionPostConfig()
	{
		$this->_assertPostOnly();

		// fetch and clean the message text from input
		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::STRING);
		$config_type = $this->_input->filterSingle('config_type', XenForo_Input::STRING);
		$config_type_phrase = new XenForo_Phrase($this->_getButtonsModel()->getBmConfigPhraseName($config_type));
		$config_buttons_order = $this->_input->filterSingle('config_buttons_order', XenForo_Input::STRING);
		$config_buttons_order = str_replace('button_', '', $config_buttons_order); // 'buttons_' prefix was only use for pretty css		

      		//Get buttons
      		$xen = $this->_xenButtons($config_type);	//Get XenForo buttons to add them in global buttons		
      		$buttons = $this->_getBbmBbCodeModel()->getBbCodesWithButton();
      		$buttons = $this->_addButtonClass($buttons);	
      		$buttons = array_merge($xen['Buttons'], $buttons);		
		
		//If user has disable javascript... prevent to register a blank config in database
		if(empty($config_buttons_order))		
		{
			$config_buttons_order = $xen['list']; //Default Xen layout
		}
		
      		//Get selected buttons from user configuration and place them in an array
      		$selected_buttons =  explode(',', $config_buttons_order);

      		//Create the final data array
      		$config_buttons_full = array();

      		foreach ($selected_buttons as $selected_button)
      		{
      			if(!empty($selected_button))
      			{
      				//to prevent last 'else' can't find any index, id must be: id array = id db = id js (id being separator)
      				if($selected_button == 'separator')
      				{
      					$config_buttons_full[] = array('tag' => 'separator', 'button_code' => '|');
      				}
      				elseif($selected_button == '#')
      				{
      					$config_buttons_full[] = array('tag' => 'carriage', 'button_code' => '#');
      				}
      				else
      				{
      					if(isset($buttons[$selected_button])) //Check if the button hasn't been deleted
      					{
      						$config_buttons_full[] = $buttons[$selected_button];
      					}
      				}
      			}
      		}

		//Choose what to display in the ajax response
		$ajaxresponse =  str_replace('separator', '|', $config_buttons_order); // <= Just  for a nicer display

		//Save in Database		
		$config_buttons_full = serialize($config_buttons_full);

		$dw = XenForo_DataWriter::create('BBM_DataWriter_Buttons');
		if ($this->_getButtonsModel()->getConfigById($config_id))
		{
			$dw->setExistingData($config_id);
		}

		$dw->set('config_buttons_order', $config_buttons_order);
		$dw->set('config_buttons_full', $config_buttons_full);
		$dw->setExtraData(BBM_DataWriter_Buttons::DATA_TYPE_PHRASE, $config_type_phrase);

		$dw->save();
		
		//Save into Registry
		$this->_getButtonsModel()->InsertConfigInRegistry();
		
		
		$displayME = 0;//Ajax Debug
		if($displayME == 1)
		{
			// Ajax response ("only run this code if the action has been loaded via XenForo.ajax()")
			if ($this->_noRedirect())
			{

				$viewParams = array(
					'ajaxresponse' => $ajaxresponse,
				);

				return $this->responseView(
					'BBM_ViewAdmin_Buttons_Buttons',
					'bbmbuttons_response',
					$viewParams
				);
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('bbm_buttons_config')
		);
	}

	protected function _xenButtons($languedirection)
	{
      		//Default configuration
      		
      		if ($languedirection != 'rtl')
      		{
      			$alignbuttons = 'justifyleft,justifycenter,justifyright';
      			$indentOutdentButtons = 'outdent,indent';
      			$undoRedoButtons = 'undo,redo';
      		}
      		else
      		{
      			$alignbuttons = 'justifyright,justifycenter,justifyleft';
      			$indentOutdentButtons = 'indent,outdent';
      			$undoRedoButtons = 'redo,undo';
      		}
      
      		$xen_grids[1] = 'removeformat,|,fontselect,fontsizeselect,forecolor,xenforo_smilies,|,' . $undoRedoButtons;
      		$xen_grids[2] = 'bold,italic,underline,strikethrough,|,'. $alignbuttons . ',|,bullist,numlist,' . $indentOutdentButtons . ',|,link,unlink,image,xenforo_media,|,xenforo_code,xenforo_custom_bbcode';
      		
      		
      		//Init final variables
		$output['list'] = $xen_grids[1] . ',#,' . $xen_grids[2];
		$output['list'] = str_replace('|', 'separator', $output['list']);
		
      		$output['Buttons'] = array();
      		$output['blankConfig'] = array();
      		
      		//Bake final variables
      		foreach($xen_grids as $key => $grid)
      		{
      			$xen_buttons =  explode(',', $grid);
      			
      			foreach($xen_buttons as $xen_code)
      			{
      				if($xen_code != '|')
      				{
      					$output['blankConfig'][$key][] = array('tag' => $xen_code, 'button_code' => $xen_code, 'class' => 'xenButton');
      					$output['Buttons'][$xen_code] = array('tag' => $xen_code, 'button_code' => $xen_code, 'class' => 'xenButton');
      				}
      				else
      				{
      					$output['blankConfig'][$key][] = array('tag' => 'separator', 'button_code' => $xen_code, 'class' => 'xenButton');					
      				}
      			}
      		}	
	
		return $output;
	}
	
	protected function _addButtonClass($buttons)
	{	
		foreach($buttons as &$button)
		{
			if(isset($button['class']) && $button['class'] == 'xenButton')
			{
				//Needed when check the "config" array
				continue;
			}
			
			if($button['tag'][0] == '@')
			{
				$button['class'] = 'orphanButton';
			}
			else
			{
				$button['class'] = 'activeButton';			
			}

			if(isset($button['active']) && !$button['active'])
			{
				$button['class'] = 'unactiveButton';
			}
		}
		
		return $buttons;
	}

	protected function _getBbmBbCodeModel()
	{
		return $this->getModelFromCache('BBM_Model_BbCodes');
	}

	protected function _getButtonsModel()
	{
		return $this->getModelFromCache('BBM_Model_Buttons');
	}	
}
//Zend_Debug::dump($contents);