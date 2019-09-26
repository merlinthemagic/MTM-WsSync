<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Docs\Examples;

class Client extends Base
{
	protected $_clientId=null;
	protected $_syncObj=null;
	protected $_recvData=array();
	
	public function runAsTimeClient($tls=false)
	{
		$this->_recvData	= array();
		$this->_clientId	= "time_client";
		$epObj				= $this->getEndpoint($tls);
		
		//somehow obtain the sync id we want to subscribe to
		//here we use a standard WsRouter message to obtain it
		$serverId		= "time_server";
		$peerObj		= $epObj->getPeersById($serverId);
		if (is_object($peerObj) === false) {
			throw new \Exception("Time server not connected to gateway");
		}
		$msgObj		= $this->getMessage("get-sync-id", "Can i have the sync ID please");
		$syncData	= $peerObj->newRequest($msgObj)->get();
		
		//msgObj follows your own custom scheme
		//use to allow / deny access and transmit data later
		$msgObj		= $this->getMessage("get-access", "Can i please subscribe");

		//this will throw if access is denied
		$data->function		= __FUNCTION__;
		$this->_recvData[]	= $data;
		
		//lets set the time on master, we are not expecting a return so timeout
		$msgObj		= $this->getMessage("set-time", time());
		$this->getSyncObj()->sendMasters($msgObj, 0);
		
		
		//see if master returned a message with a new time
		//it should come into the syncHandler()
		sleep(1);
		$epObj->getAsync()->getParent()->runOnce();

		return $this->getRecvData();
	}
	public function syncHandler($data)
	{
		$data->function	= __FUNCTION__;
		$this->_recvData[]	= $data;
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