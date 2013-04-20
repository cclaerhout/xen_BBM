<?php

class BBM_DataWriter_Buttons extends XenForo_DataWriter
{
	const DATA_TYPE_PHRASE = 'config_type_phrase';

	protected $_protectedTypes = array('rtl', 'ltr', 'disable', 'transparent');
	
	protected function _getFields() {
		return array(
			'bbm_buttons' => array(
				'config_id' 	=> array(
						'type' => self::TYPE_UINT,
				                'autoIncrement' => true
				),
				'config_type' 	=> array(
						'type' => self::TYPE_STRING,
						'required' => true,
						'maxLength' => 25,
						'verification' => array('$this', '_verifConfigType')
				),
				'config_buttons_order' 	=> array(
						'type' => self::TYPE_STRING, 
						'default' => ''
				),
				'config_buttons_full' => array(
						'type' => self::TYPE_STRING, 
						'default' => ''
				)
			)
		);
	}

	protected function _verifConfigType(&$config_type)
	{
		if (empty($config_type))
		{
			$this->error(new XenForo_Phrase('bbm_config_error_required'), 'config_id');
			return false;
		}

		$config_type = strtolower($config_type);

		if (preg_match('/[^a-zA-Z0-9_]/', $config_type))
		{
			$this->error(new XenForo_Phrase('bbm_please_enter_a_configtype_using_only_alphanumeric'), 'config_id');
			return false;
		}
		
		if(in_array($config_type, $this->_protectedTypes))
		{
			$this->error(new XenForo_Phrase('bbm_config_type_protected'), 'config_id');
			return false;
		}

		if (!$this->isUpdate() && $this->_getButtonsModel()->getConfigByType($config_type))
		{
			$this->error(new XenForo_Phrase('bbm_config_type_must_be_unique'), 'config_id');
			return false;
		}

		return true;
	}

	protected function _getTitlePhraseName($type)
	{
		return $this->_getButtonsModel()->getBmConfigPhraseName($type);
	}

	protected function _preSave()
	{
		$titlePhrase = $this->getExtraData(self::DATA_TYPE_PHRASE);

		if ($titlePhrase === null || empty($titlePhrase))
		{
			$this->error(new XenForo_Phrase('bbm_please_enter_a_phrase_for_constant_type'));
			return false;		
		}

		if ($this->isUpdate() && $this->isChanged('config_type'))
		{
			$this->_renameMasterPhrase(
				$this->_getTitlePhraseName($this->getExisting('config_type')),
				$this->_getTitlePhraseName($this->get('config_type'))
			);
		}

		if ($titlePhrase !== null)
		{
			$this->_insertOrUpdateMasterPhrase(
				$this->_getTitlePhraseName($this->get('config_type')), $titlePhrase, 'BbCodeManager'
			);
		}
	}

	protected function _postDelete()
	{
		$this->_deleteMasterPhrase($this->_getTitlePhraseName($this->get('config_type')));
	}
	
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'config_id'))
		{
			return false;
		}
		return array('bbm_buttons' => $this->_getButtonsModel()->getConfigById($id));
	}
	
	protected function _getUpdateCondition($tableName)
	{
		return 'config_id = ' . $this->_db->quote($this->getExisting('config_id'));
	}

	protected function _getButtonsModel()
	{
		return $this->getModelFromCache('BBM_Model_Buttons');
	}	
}