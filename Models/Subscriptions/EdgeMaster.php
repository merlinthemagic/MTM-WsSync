<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Subscriptions;

class EdgeMaster extends BaseEdge
{
	protected $_role="master";

	public function connect($authData=null)
	{
		if ($this->getTerminated() === false) {
			$this->_connectTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
			$this->_isTerm		= false;
		} else {
			throw new \Exception("Subscription no longer active");
		}
		return $this;
	}
	public function disconnect($authData=null, $type="disconnect")
	{
		if ($this->getConnectTime() !== null) {
			if ($this->getConnection()->getTerminated() === false) {
				$this->getRequest($type, $authData)->setTimeout(0)->exec();
			}
			$this->_isTerm	= true;
			$this->getParent()->removeSubscriber($this);
		}
		return $this;
	}
	public function terminate($notifyObj=null, $event=null)
	{
		if ($this->getTerminated() === false) {
			$this->_isTerm	= true;
			$this->disconnect(null, "termination");
			$this->getConnection()->removeNotifier($this->getNotifier());
			$this->getParent()->removeSubscriber($this);
		}
	}
}