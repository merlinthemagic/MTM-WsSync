<?php
//© 2019 Martin Peter Madsen
namespace MTM\WsSync\Factories;

class Nodes extends Base
{	
	//USE: $nodeObj		= \MTM\WsSync\Factories::getNodes()->__METHOD__();

	public function getEndpoint($id, $ip, $port)
	{
		$rObj	= new \MTM\WsSync\Models\Nodes\Endpoint();
		$rObj->setConfiguration($id, $ip, $port);
		return $rObj;
	}
	public function getServerEdge($id, $ip, $port)
	{
		$rObj	= new \MTM\WsSync\Models\Nodes\EdgeServer();
		$rObj->setConfiguration($id, $ip, $port);
		return $rObj;
	}
}