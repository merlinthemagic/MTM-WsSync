<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Docs\Examples;

class Server extends Base
{
	protected $_clientId=null;
	protected $_syncObj=null;
	protected $_recvData=array();
	
	public function runAsTimeServer($tls=false)
	{
		$this->_recvData	= array();
		$this->_clientId	= "time_server";
		$epObj				= $this->getEndpoint($tls);
		$epObj->setIngressCb($this, "ingressHandler");
		
		$runTime	= ini_get("max_execution_time");
		if ($runTime == 0 || $runTime > 10) {
			$runTime	= 10; //dont want this experiment to time out
		}
		$tTime	= time() + $runTime;
		while (time() < $tTime) {
			$epObj->getAsync()->getParent()->runOnce();
		}

		return $this->getRecvData();
	}
	public function ingressHandler($reqObj)
	{
		//this method is called when we receive a message from a peer
		//one that is not yet setup for sync
		$msgObj				= $reqObj->getRxData();
		$msgObj->function	= __FUNCTION__;
		$this->_recvData[]	= $msgObj;

		if (
			$msgObj instanceof \stdClass
			&& property_exists($msgObj, "myType") === true
			&& $msgObj->myType == "get-sync-id"
		) {
			$syncObj	= $this->getSyncObj();
			$reqObj->setTxData($syncObj->getData())->exec();

		} else {
			throw new \Exception("Server says: Invalid non-sync request");
		}
	}
	public function syncHandler($msgObj)
	{
		//this method is called when we receive an event from a peer
		//e.g. a subscription request, data update, request data etc
		//any data you return will be relayed back to the requester
		//as long as they expect RSVP for the request
		$msgObj->function	= __FUNCTION__;
		$this->_recvData[]	= $msgObj;
		if (
			$msgObj->data instanceof \stdClass
			&& property_exists($msgObj->data, "myType") === true
		) {
			if ($msgObj->type == "subscribe" && $msgObj->data->myType == "get-access") {
				//this is a subscription request
				//we are ok with all subscriptions
				return $this->getMessage("access-granted", "Welcome");
				
			} elseif ($msgObj->type == "data" && $msgObj->data->myType == "set-time") {
				$this->setTime($msgObj->data->myData);
				return $this->getMessage("completed", "time was set");
			} else {
				throw new \Exception("Server says: Invalid request type: " . $msgObj->data->myType);
			}
			
		} else {
			throw new \Exception("Server says: Invalid data");
		}
	}
	public function setTime($epoch)
	{
		//lets send the new time to all slaves
		//this is an example of a method in a class that is updated
		//and the result is sent to all slave subscribers
		$msgObj = $this->getMessage("time-update", $epoch);
		$this->getSyncObj()->sendSlaves($msgObj, 0);
	}
	public function getSyncObj()
	{
		if ($this->_syncObj === null) {
			$this->_syncObj	= $this->getEndpoint()->newSync();
			$this->_syncObj->setEventCb($this, "syncHandler"); //any inbound data will be sent here
		}
		return $this->_syncObj;
	}
	public function getRecvData()
	{
		return $this->_recvData;
	}
}