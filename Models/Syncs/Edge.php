<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Syncs;

class Edge extends Base
{
	public function getData()
	{
		//this edge sync's location on the network
		$this->_lastDataTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		$rObj					= new \stdClass();
		$rObj->connId			= $this->getParent()->getGuid();
		$rObj->syncId			= $this->getGuid();
		return $rObj;
	}
	public function terminateSubscriber($subObj)
	{
		$termObj				= new \stdClass();
		$termObj->type			= "termination";
		$termObj->txId			= null;
		$termObj->subObj		= $subObj;
		$termObj->userData		= $subObj->getConnection()->getUserData();
		$this->newEvent($termObj);
		return $this;
	}
	protected function newMasterSubscription($reqObj)
	{
		$msgObj		= $reqObj->getRxData();
		$subObj		= $this->getSubscriberBySyncId($msgObj->srcSync);
		if (is_object($subObj) === false) {
			//add us in a master role
			$subObj	= new \MTM\WsSync\Models\Subscriptions\EdgeMaster();
			$subObj->setConfiguration($msgObj->srcSync, $this, $reqObj->getConnection());

			//throw if you do not want to proceed, and sub will not be added
			$evObj	= $subObj->getEvent($reqObj);
			$reqObj->setTxData($this->newEvent($evObj));
			$this->addSubscriber($subObj);
		} else {
			throw new \Exception("Already subscribed");
		}
		return $subObj;
	}
	protected function newSlaveSubscription($sd)
	{
		$subObj		= $this->getSubscriberBySyncId($sd->syncId);
		if (is_object($subObj) === false) {
			$connObj		= $this->getParent()->getConnectionFromGuid($sd->connId);
			if (is_object($connObj) === true) {
				$subObj	= new \MTM\WsSync\Models\Subscriptions\EdgeSlave();
				$subObj->setConfiguration($sd->syncId, $this, $connObj);
				$this->addSubscriber($subObj);
			} else {
				throw new \Exception("Invalid Peer");
			}
		} else {
			throw new \Exception("Already subscribed");
		}
		return $subObj;
	}
}