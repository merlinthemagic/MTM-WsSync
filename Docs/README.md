### What is this?

A way use websockets as a way to syncronize objects across hosts using MTM WsRouter

Run gateway:
$testObj	= new \MTM\WsRouter\Docs\Examples\Server();
$rData		= $testObj->run();

Run server 1:
$testObj	= new \MTM\WsSync\Docs\Examples\Server();
$rData		= $testObj->runAsTimeServer();

Run client 2:
$testObj	= new \MTM\WsSync\Docs\Examples\Client();
$rData		= $testObj->runAsTimeClient();

Terminate server:
$testObj	= new \MTM\WsRouter\Docs\Examples\Client();
$testObj->terminateGateway();