<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Subscriptions;

class BaseEdge extends Base
{
	protected $_connObj=null;
	
	public function setConfiguration($syncId, $parentObj, $connObj)
	{
		$this->_syncId		= $syncId;
		$this->_parentObj	= $parentObj;
		$this->_connObj		= $connObj;
		$this->_notifyObj	= $connObj->addNotify($this);
		$this->_notifyObj->bindEvent("termination", $this, "terminate");
		$this->_isTerm		= false;
		return $this;
	}
	public function getConnection()
	{
		return $this->_connObj;
	}
	public function getRequest($type, $data=null)
	{
		$msgObj				= $this->getMessage($type, $data);
		$msgObj->data		= base64_encode(json_encode($msgObj->data));
		return $this->getConnection()->newRequest($msgObj);
	}
	public function getEvent($reqObj)
	{
		$msgObj				= $reqObj->getRxData();
		$evObj				= new \stdClass();
		$evObj->type		= $msgObj->type;
		$evObj->txId		= $msgObj->txId;
		$evObj->data		= json_decode(base64_decode($msgObj->data));
		$evObj->subObj		= $this;
		//include the connection user data, it may be needed for validation etc
		$evObj->userData	= $reqObj->getConnection()->getUserData();
		return $evObj;
	}
}