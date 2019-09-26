<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Subscriptions;

class Base
{
	protected $_cStore=array();
	protected $_syncId=null;
	protected $_isTerm=null;
	protected $_connectTime=null;
	protected $_notifyObj=null;
	protected $_parentObj=null;
	protected $_txId=0;
	
	//this attribute is controlled by the
	//user and provides a way to have i.e.
	//access or auth data follow a subscription
	protected $_userData=null;

	public function getSyncId()
	{
		return $this->_syncId;
	}
	public function getConnectTime()
	{
		return $this->_connectTime;
	}
	public function getTerminated()
	{
		return $this->_isTerm;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
	public function getRole()
	{
		return $this->_role;
	}
	public function getNotifier()
	{
		return $this->_notifyObj;
	}
	public function getTxId()
	{
		return $this->_txId;
	}
	public function tickTx()
	{
		$this->_txId++;
		return $this;
	}
	public function setUserData($data)
	{
		//for end user exclusive use
		$this->_userData	= $data;
		return $this;
	}
	public function getUserData()
	{
		//for end user exclusive use
		return $this->_userData;
	}
	public function getMessage($type, $data=null)
	{
		$msgObj				= new \stdClass();
		$msgObj->service	= "MTM-WS-SYNC";
		$msgObj->type		= $type;
		$msgObj->txId		= $this->getTxId();
		$msgObj->srcSync	= $this->getParent()->getGuid();
		$msgObj->dstSync	= $this->getSyncId();
		$msgObj->data		= $data;
		return $msgObj;
	}
}