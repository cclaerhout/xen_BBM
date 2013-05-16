<?php

class BBM_Model_Buttons extends XenForo_Model
{
	/**
	* Get configs by Id
	*/
	public function getConfigById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm_buttons
			WHERE config_id = ?
		', $id);
	}

	/**
	* Get configs by type
	*/	
	public function getConfigByType($type)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm_buttons
			WHERE config_type = ?
		', $type);
	}
 
	/**
	* Get all Configs
	*/
	public function getAllConfig($options = null)
	{
		$configs = $this->fetchAllKeyed('
			SELECT * 
			FROM bbm_buttons
			ORDER BY config_type
		', 'config_id');
		
		if($options == 'strict')
		{
			foreach($configs as $id => $config)
			{
				if(in_array($config['config_type'], array('ltr', 'rtl')))
				{
					unset($configs[$id]);
				}
			}
		}

		return $configs;
	}
	
	
	/**
	* Registry Functions (thanks to Jake Bunce ; http://xenforo.com/community/threads/ideal-way-for-cron-to-loop-through-all-users-over-several-runs.33600/#post-382901)
	*/
	
	public function InsertConfigInRegistry()
	{   
		$options['bbm_buttons'] = $this->getAllConfig();
		
		//Put Config type (rtl or ltr) as key of the array (just to have a cleaner display)

		foreach ($options['bbm_buttons'] as $k => $config)
		{
			$key = $config['config_type'];
			$options['bbm_buttons'][$key] = $options['bbm_buttons'][$k];
			unset($options['bbm_buttons'][$k]);
		}
		
		XenForo_Model::create('XenForo_Model_DataRegistry')->set('bbm_buttons', $options);
	}

	public function CleanConfigInRegistry()
	{	  
		XenForo_Model::create('XenForo_Model_DataRegistry')->delete('bbm_buttons');
	}

	/**
	* Phrase Model
	*/
	public function getBmConfigPhraseName($type)
	{
		return 'button_manager_config_' . $type;
	}

	public function getEditorConfigsForForums($selected = 'disable')
	{
		return $this->getEditorConfigsForMobile($selected, false);
	}
	
	public function getEditorConfigsForMobile($selected, $tablets = false)
	{
		$configs = $this->getAllConfig('strict');

		//Check if there is any bbm configs
		if(!is_array($configs) || empty($configs))
		{
			unset($configs);
		      	$configs['disable'] = array(
		      			'value' => 'disable',
		      			'label' => new XenForo_Phrase('bbm_no_editor_available'),
		      			'selected' => true
		      	);
			return $configs;		
		}

		//Check if the system can detect tablets (on demand - see argument)		
		$visitor = XenForo_Visitor::getInstance();
		if( $tablets === true && (!class_exists('Sedo_DetectBrowser_Listener_Visitor') || !isset($visitor->getBrowser['isMobile'])))
            	{
			unset($configs);
		      	$configs['disable'] = array(
		      			'value' => 'disable',
		      			'label' => new XenForo_Phrase('bbm_mobilestyleselector_addon_not_installed'),
		      			'selected' => true
		      	);
			return $configs;
		}

	      	//Add disable option
	      	$configs['disable'] = array(
	      			'value' => 'disable',
	      			'label' => new XenForo_Phrase('bbm_disable'),
	      			'selected' => ($selected == 'disable')
	      	);

	      	//Add rtl_ltr option for tablets
	      	if($tablets === true)
	      	{
		      	$configs['transparent'] = array(
		      			'value' => 'rtl_ltr',
		      			'label' => new XenForo_Phrase('bbm_transparent'),
		      			'selected' => ($selected == 'transparent')
		      	);
		}
		
      		foreach ($configs AS $key => $config)
      		{
			if(in_array($key, array('disable', 'transparent')))
			{
	      			continue;
			}

			$phrase = (isset($config['config_type'])) ? new XenForo_Phrase('button_manager_config_' . $config['config_type']) : new XenForo_Phrase('bbm_phrase_not_found');
      			$configs[$config['config_type']] = array(
      				'value' => $config['config_type'],
      				'label' => $phrase,
      				'selected' => ($selected == $config['config_type'])
      			);
      			unset($configs[$key]);
      		}

		return $configs;
	}	
}