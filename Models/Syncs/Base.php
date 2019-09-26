<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Syncs;

class Base
{
	protected $_guid=null;
	protected $_lastDataTime=null;
	protected $_parentObj=null;
	protected $_eventCb=null;
	protected $_subObjs=array();

	public function getGuid()
	{
		if ($this->_guid === null) {
			$this->_guid	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		}
		return $this->_guid;
	}
	public function setParent($obj)
	{
		$this->_parentObj	= $obj;
		return $this;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
	public function setEventCb($obj=null, $method=null)
	{
		if (is_object($obj) === true && is_string($method) === true) {
			$this->_eventCb	= array($obj, $method);
		}
		return $this;
	}
	public function newEvent($data)
	{
		return call_user_func_array($this->_eventCb, array($data));
	}
	public function sendAll($data, $timeout=25000)
	{
		$reqObjs	= array();
		foreach ($this->getSubscribers() as $subObj) {
			$reqObjs[]	= $this->sendData($subObj, $data, $timeout);
		}
		return $reqObjs;
	}
	public function sendMasters($data, $timeout=25000)
	{
		$reqObjs	= array();
		foreach ($this->getMasters() as $subObj) {
			$reqObjs[]	= $this->sendData($subObj, $data, $timeout);
		}
		return $reqObjs;
	}
	public function sendSlaves($data, $timeout=25000)
	{
		$reqObjs	= array();
		foreach ($this->getSlaves() as $subObj) {
			$reqObjs[]	= $this->sendData($subObj, $data, $timeout);
		}
		return $reqObjs;
	}
	public function sendData($subObj, $data, $timeout=25000)
	{
		return $subObj->tickTx()->getRequest("data", $data)->setTimeout($timeout)->exec();
	}
	public function handleIngress($reqObj)
	{
		$msgObj		= $reqObj->getRxData();
		if ($msgObj->type == "data") {
			$subObj		= $this->getSubscriberBySyncId($msgObj->srcSync);
			if (is_object($subObj) === true) {
				$evObj			= $subObj->getEvent($reqObj);
				$reqObj->setTxData($this->newEvent($evObj));
			} else {
				throw new \Exception("Not subscribed");
			}
			
		} elseif ($msgObj->type == "subscribe") {
			
			$subObj	= $this->newMasterSubscription($reqObj);
			$subObj->connect();
			
		} elseif (
			$msgObj->type == "unsubscribe"
			|| $msgObj->type == "termination" //termination cannot provide authData
			|| $msgObj->type == "disconnect"
		) {
			
			$subObj		= $this->getSubscriberBySyncId($msgObj->srcSync);
			if (is_object($subObj) === true) {

				//this call does not expect a return
				//we remove first, since in every senario:
				//hijacked sync connection, unsubscription, termination, disconnect
				//there is nothing we can do about mainaining the connection
				$this->removeSubscriber($subObj);
			}
	
		} else {
			throw new \Exception("Not handled for type: " . $msgObj->type);
		}
	}
	public function subscribe($syncData, $authData=null)
	{
		if (
			$syncData instanceof \stdClass
			&& property_exists($syncData, "syncId") === true
			&& (
				property_exists($syncData, "peerId") === true
				|| property_exists($syncData, "connId") === true
			)
		) {
			$subObj		= $this->newSlaveSubscription($syncData);
			return $subObj->connect($authData);
			
		} else {
			throw new \Exception("Invalid sync data");
		}
	}
	public function getSubscriberBySyncId($syncId)
	{
		if (array_key_exists($syncId, $this->_subObjs) === true) {
			return $this->_subObjs[$syncId];
		} else {
			return null;
		}
	}
	public function getSubscribers()
	{
		return $this->_subObjs;
	}
	protected function addSubscriber($subObj)
	{
		$this->_subObjs[$subObj->getSyncId()]	= $subObj;
		return $this;
	}
	public function getMasters()
	{
		$subObjs	= array();
		foreach ($this->getSubscribers() as $subObj) {
			if ($subObj->getRole() == "slave") {
				$subObjs[]	= $subObj;
			}
		}
		return $subObjs;
	}
	public function getSlaves()
	{
		$subObjs	= array();
		foreach ($this->getSubscribers() as $subObj) {
			if ($subObj->getRole() == "master") {
				$subObjs[]	= $subObj;
			}
		}
		return $subObjs;
	}
	public function removeSubscriber($subObj)
	{
		if (array_key_exists($subObj->getSyncId(), $this->_subObjs) === true) {
			unset($this->_subObjs[$subObj->getSyncId()]);
			$this->terminateSubscriber($subObj);
		}
		return $this;
	}
	public function terminate()
	{
		foreach ($this->getSubscribers() as $subObj) {
			$subObj->terminate();
		}
		$this->getParent()->removeSync($this);
	}
	public function getLastDataTime()
	{
		//last time someone asked for our location
		//on the network
		return $this->_lastDataTime;
	}
}