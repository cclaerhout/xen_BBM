<?php

class BBM_ControllerAdmin_Forum extends XFCP_BBM_ControllerAdmin_Forum
{
	public function actionEdit()
	{
		$parent = parent::actionEdit();

		if(isset($parent->params['forum']['bbm_bm_editor']))
		{
			$parent->params['bbm_bm_editors'] = XenForo_Model::create('BBM_Model_Buttons')->getEditorConfigsForForums($parent->params['forum']['bbm_bm_editor']);
		}

		return $parent;
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			return $this->responseReroute('XenForo_ControllerAdmin_Forum', 'deleteConfirm');
		}

		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$prefixIds = $this->_input->filterSingle('available_prefixes', XenForo_Input::UINT, array('array' => true));

		$bbm_bm_editor = $this->_input->filterSingle('bbm_bm_editor', XenForo_Input::STRING);
		$bbm_bm_editor = (empty($bbm_bm_editor )) ? 'disable' : $bbm_bm_editor;

		$writerData = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'node_name' => XenForo_Input::STRING,
			'node_type_id' => XenForo_Input::STRING,
			'parent_node_id' => XenForo_Input::UINT,
			'display_order' => XenForo_Input::UINT,
			'display_in_list' => XenForo_Input::UINT,
			'description' => XenForo_Input::STRING,
			'style_id' => XenForo_Input::UINT,
			'moderate_messages' => XenForo_Input::UINT,
			'allow_posting' => XenForo_Input::UINT,
			'count_messages' => XenForo_Input::UINT,
			'find_new' => XenForo_Input::UINT,
			'default_prefix_id' => XenForo_Input::UINT,
		));
		
		if (!$this->_input->filterSingle('style_override', XenForo_Input::UINT))
		{
			$writerData['style_id'] = 0;
		}

		$writer = $this->_getNodeDataWriter();

		if ($nodeId)
		{
			$writer->setExistingData($nodeId);
		}

		if (!in_array($writerData['default_prefix_id'], $prefixIds))
		{
			$writerData['default_prefix_id'] = 0;
		}

		$writer->bulkSet($writerData);
		$writer->set('bbm_bm_editor', $bbm_bm_editor);
		$writer->save();

		return parent::actionSave();
	}
}
//Zend_Debug::dump($parent);