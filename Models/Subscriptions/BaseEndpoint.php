<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Subscriptions;

class BaseEndpoint extends Base
{
	protected $_peerObj=null;

	public function setConfiguration($syncId, $parentObj, $peerObj)
	{
		$this->_syncId		= $syncId;
		$this->_parentObj	= $parentObj;
		$this->_peerObj		= $peerObj;
		$this->_notifyObj	= $peerObj->addNotify($this);
		$this->_notifyObj->bindEvent("termination", $this, "terminate");
		$this->_isTerm		= false;
		return $this;
	}
	public function getPeer()
	{
		return $this->_peerObj;
	}
	public function getRequest($type, $data=null)
	{
		$msgObj				= $this->getMessage($type, $data);
		$msgObj->data		= base64_encode(serialize($msgObj->data));
		return $this->getPeer()->newRequest($msgObj);
	}
	public function getEvent($reqObj)
	{
		$msgObj				= $reqObj->getRxData();
		$evObj				= new \stdClass();
		$evObj->type		= $msgObj->type;
		$evObj->txId		= $msgObj->txId;
		$evObj->data		= unserialize(base64_decode($msgObj->data));
		$evObj->subObj		= $this;
		return $evObj;
	}
}