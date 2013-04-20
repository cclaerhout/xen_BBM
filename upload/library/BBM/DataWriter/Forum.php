<?php

class BBM_DataWriter_Forum extends XFCP_BBM_DataWriter_Forum
{
	protected function _getFields()
	{
		$parent = parent::_getFields();
		$parent['xf_forum']['bbm_bm_editor'] = array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => 'disable');
		//Zend_Debug::dump($parent);break;

		return $parent;
	}
}