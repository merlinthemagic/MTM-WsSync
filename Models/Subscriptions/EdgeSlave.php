<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Subscriptions;

class EdgeSlave extends BaseEdge
{
	protected $_role="slave";
	
	public function connect($authData=null)
	{
		if ($this->getTerminated() === false) {
			
			try {
				$data				= $this->getRequest("subscribe", $authData)->get();
				$this->_connectTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
				return $data;
				
			} catch (\Exception $e) {
				$this->getParent()->removeSubscriber($this);
				throw $e;
			}
			
		} elseif ($this->getTerminated() === true) {
			throw new \Exception("Subscription no longer active");
		} else {
			throw new \Exception("Already connected");
		}
	}
	public function disconnect($authData=null, $type="unsubscribe")
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