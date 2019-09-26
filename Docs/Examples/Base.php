<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Docs\Examples;

class Base
{
	protected $_cStore=array();
	protected $_epObj=null;
	
	public function getHost()
	{
		return "127.0.0.1";
	}
	public function getPort()
	{
		return 5896;
	}
	private function getCerts()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \MTM\Certs\Docs\Examples\Certificates();
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getServerCert()
	{
		return $this->getCerts()->getServer1();
	}
	public function getClientCert()
	{
		return $this->getCerts()->getClient1();
	}
	protected function getEndpoint($tls=false)
	{
		if ($this->_epObj === null) {
			$this->_epObj	= \MTM\WsSync\Factories::getNodes()->getEndpoint($this->_clientId, $this->getHost(), $this->getPort());
			if ($tls === true) {
				$this->_epObj->setSsl($this->getClientCert(), true, false, false);
			}
			$authData				= new \stdClass();
			$authData->secret		= "My very secret data that lets me register";
			$this->_epObj->connect($authData);
		}
		return $this->_epObj;
	}
	public function getMessage($type, $data=null)
	{
		//example of an internal message format, 100% user controlled
		//is serialized in transport so can be anything
		$msgObj				= new \stdClass();
		$msgObj->myTime		= time();
		$msgObj->myType		= $type;
		$msgObj->myData		= $data;
		return $msgObj;
	}
}