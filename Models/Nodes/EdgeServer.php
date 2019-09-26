<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Models\Nodes;

class EdgeServer extends \MTM\WsEdge\Models\Nodes\Server
{
	protected $_bypassCb=null;
	protected $_syncObjs=array();

	public function __construct()
	{
		//override the standard ingress call back function
		//we need to insert outselves in the middle
		parent::setIngressCb($this, "wsSyncIngress");
	}
	public function terminate()
	{
		foreach ($this->getSyncs() as $syncObj) {
			$syncObj->terminate();
		}
		//make sure parent also terminates
		parent::terminate();
	}
	public function setIngressCb($obj=null, $method=null)
	{
		if (is_object($obj) === true && is_string($method) === true) {
			$this->_bypassCb	= array($obj, $method);
		}
		return $this;
	}
	public function wsSyncIngress($reqObj)
	{
		if ($reqObj->getType() == "edge-ingress-request") {

			$msgObj	= $reqObj->getRxData();
			if (
				$msgObj instanceof \stdClass === true
				&& property_exists($msgObj, "service") === true
				&& $msgObj->service == "MTM-WS-SYNC"
			) {
				
				try {
					$syncObj	= $this->getSyncByGuid($msgObj->dstSync, true);
					$syncObj->handleIngress($reqObj);
					$reqObj->exec();
				} catch (\Exception $e) {
					$reqObj->setError($e)->exec(false);
				}
						
			} elseif ($this->_bypassCb !== null) {
				//not a sync call pass through to the default handler
				call_user_func_array($this->_bypassCb, array($reqObj));
			}

		} elseif ($this->_bypassCb !== null) {
			//not a sync call pass through to the default handler
			call_user_func_array($this->_bypassCb, array($reqObj));
		}
	}
	public function getSyncs()
	{
		return $this->_syncObjs;
	}
	public function newSync()
	{
		$rObj		= new \MTM\WsSync\Models\Syncs\Edge();
		$rObj->setParent($this);
		$this->_syncObjs[$rObj->getGuid()]	= $rObj;
		return $rObj;
	}
	public function getSyncByGuid($guid, $throw=true)
	{
		if (array_key_exists($guid, $this->_syncObjs) === true) {
			return $this->_syncObjs[$guid];
		} elseif ($throw === true) {
			throw new \Exception("Edge sync Guid does not exist: " . $guid, 8823);
		} else {
			return null;
		}
	}
	public function removeSync($syncObj)
	{
		if (array_key_exists($syncObj->getGuid(), $this->_syncObjs) === true) {
			unset($this->_syncObjs[$syncObj->getGuid()]);
		}
		return $this;
	}
}