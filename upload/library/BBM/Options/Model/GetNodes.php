<?php

class BBM_Options_Model_GetNodes extends XenForo_Model
{
	public function getNodesOptions($selectedNodesIds)
	{
		$nodes = array();
		foreach ($this->getDbNodes() AS $node)
		{
			$nodes[] = array(
			'label' => $node['title'],
			'value' => $node['node_id'],
			'selected' => in_array($node['node_id'], $selectedNodesIds)
			);
		}

		return $nodes;
	}

	public function getDbNodes()
	{
		return $this->_getDb()->fetchAll('
		SELECT node_id, title, node_type_id
		FROM xf_node
		WHERE node_type_id = ?
		ORDER BY node_id
		', 'Forum');

	}
}