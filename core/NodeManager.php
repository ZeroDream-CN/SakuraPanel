<?php
namespace SakuraPanel;

use SakuraPanel;

class NodeManager {
	
	public function closeClient($node, $token)
	{
		$ninfo = $this->getNodeInfo($node);
		if($ninfo) {
			$result = SakuraPanel\Utils::http("http://admin:{$ninfo['admin_pass']}@{$ninfo['ip']}:{$ninfo['admin_port']}/api/client/close/{$token}");
			if(isset($result['body'])) {
				$json   = json_decode($result['body'], true);
				if(is_array($json)) {
					if($json['status'] == 200) {
						return true;
					} else {
						return $json['message'];
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function getUserNode($group)
	{
		return Database::toArray(Database::search("nodes", Array("group" => "{$group};")));
	}
	
	public function isNodeExist($node)
	{
		return Database::querySingleLine("nodes", Array("id" => $node)) ? true : false;
	}
	
	public function getNodeInfo($node)
	{
		return Database::querySingleLine("nodes", Array("id" => $node));
	}
	
	public function updateNode($id, $data)
	{
		if($this->getNodeInfo($id)) {
			return Database::update("nodes", $data, Array("id" => $id));
		} else {
			return false;
		}
	}
	
	public function getTotalNodes()
	{
		$rs = Database::toArray(Database::query("nodes", Array()));
		return count($rs);
	}
	
	public function addNode($data)
	{
		return Database::insert("nodes", $data);
	}
	
	public function deleteNode($data)
	{
		$result = false;
		
		if(is_array($this->getNodeInfo($data))) {
			$result = Database::delete("proxies", Array("node" => $data));
			
			if($result !== true) {
				return $result;
			}
			
			return Database::delete("nodes", Array("id" => $data));
		} else {
			return false;
		}
	}
}