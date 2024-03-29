<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Manager\DataCenter;
use App\Manager\Logic;
use App\Manager\TaskManager;
use App\Manager\Sender;

class Server
{
	
	const HOST = '0.0.0.0';
	
	const PORT = 8811;
	
	const FRONT_PORT = 8812;
	
	const CONFIG = [
		'worker_num' => 4,
		'enable_static_handler' => true,
		'document_root' => '/home/vagrant/www/lj/HideAndSeek/frontend',
		'task_worker_num' => 4,
		'dispatch_mode' => 5
	];
	
	const CLIENT_CODE_MATCH_PLAYER = 600;
	
	const CLIENT_CODE_START_ROOM = 601;
	
	const CLIENT_CODE_MOVE_PLAYER = 602;
	
	const CLIENT_CODE_MAKE_CHALLENGE = 603;
	
	const CLIENT_CODE_ACCEPT_CHALLENGE = 604;
	
	const CLIENT_CODE_REFUSE_CHALLENGE = 605;
	
	private $logic;
	
	private $ws;
	
	public function __construct()
	{
		
		$this->logic = new Logic();
		$this->ws    = new \Swoole\WebSocket\Server(self::HOST, self::PORT);
		$this->ws->set(self::CONFIG);
		$this->ws->listen(self::HOST, self::FRONT_PORT, SWOOLE_SOCK_TCP);
		$this->ws->on('start', [$this, 'onStart']);
		$this->ws->on('workerStart', [$this, 'onWorkerStart']);
		$this->ws->on('open', [$this, 'onOpen']);
		$this->ws->on('task', [$this, 'onTask']);
		$this->ws->on('finish', [$this, 'onFinish']);
		$this->ws->on('message', [$this, 'onMessage']);
		$this->ws->on('close', [$this, 'onClose']);
		$this->ws->on('request', [$this, 'onRequest']);
		
		$this->ws->start();
	}
	
	
	public function onStart($server)
	{
		
		swoole_set_process_name('hide-and-seek');
		echo sprintf("master start (listening on %s:%d) \n", self::HOST, self::PORT);
		DataCenter::initDataCenter();
	}
	
	
	public function onWorkerStart($server, $workerId)
	{
		
		echo "server: onWorkStart,worker_id:{$server->worker_id}\n";
		DataCenter::$server = $server;
	}
	
	
	public function onOpen($server, $request)
	{
		
		DataCenter::log(sprintf('client open fd:%d', $request->fd));
		
		$playerId = $request->get['player_id'];
		
		if (empty(DataCenter::getOnlinePlayer($playerId))) {
			DataCenter::setPlayerInfo($playerId, $request->fd);
		} else {
			$server->disconnect($request->fd, 4000, '该player_id已在线');
		}
	}
	
	
	public function onRequest($request, $response)
	{
		
		DataCenter::log('onRequest');
		$action = $request->get['a'];
		if ($action == 'get_online_player') {
			
			$data = [
				'online_player' => DataCenter::lenOnlinePlayer()
			];
			
			$response->end(json_encode($data));
		} elseif ($action == 'get_player_rank') {
			$data = ['players_rank' => DataCenter::getPlayersRank()];
			$response->end(json_encode($data));
		}
	}
	
	
	public function onMessage($server, $request)
	{
		
		DataCenter::log(sprintf('Client open fd: %d, message: %s', $request->fd, $request->data));
		$data     = json_decode($request->data, true);
		$playerId = DataCenter::getPlayerId($request->fd);
		switch ($data['code']) {
			case self::CLIENT_CODE_MATCH_PLAYER:
				DataCenter::log('matchPlayer');
				$this->logic->matchPlayer($playerId);
				break;
			case self::CLIENT_CODE_START_ROOM:
				DataCenter::log('startRoom');
				$this->logic->startRoom($data['room_id'], $playerId);
				break;
			case self::CLIENT_CODE_MOVE_PLAYER:
				DataCenter::log('movePlayer');
				$this->logic->movePlayer($data['direction'], $playerId);
				break;
			case self::CLIENT_CODE_MAKE_CHALLENGE:
				DataCenter::log('makeChallenge');
				$this->logic->makeChallenge($data['opponent_id'], $playerId);
				break;
			case self::CLIENT_CODE_ACCEPT_CHALLENGE:
				$this->logic->acceptChallenge($data['challenger_id'], $playerId);
				break;
			case self::CLIENT_CODE_REFUSE_CHALLENGE:
				$this->logic->refuseChallenge($data['challenger_id']);
				break;
		}
		DataCenter::log('Async');
		Sender::sendMessage($playerId, Sender::MSG_SUCCESS);
	}
	
	
	public function onClose($server, $fd)
	{
		
		DataCenter::log(sprintf('client close fd: %d', $fd));
		$this->logic->closeRoom(DataCenter::getPlayerId($fd));
		DataCenter::delPlayerInfo($fd);
		
	}
	
	
	public function onTask($server, $taskId, $srcWorkerId, $data)
	{
		
		DataCenter::log('onTask', $data);
		$result = [];
		switch ($data['code']) {
			case TaskManager::TASK_CODE_FIND_PLAYER:
				$ret = TaskManager::findPlayer();
				if (!empty($ret)) {
					$result['data'] = $ret;
				}
				break;
		}
		
		if (!empty($result)) {
			
			$result['code'] = $data['code'];
			
			return $result;
		}
	}
	
	
	public function onFinish($server, $taskId, $data)
	{
		
		DataCenter::log("onFinish", $data);
		switch ($data['code']) {
			case TaskManager::TASK_CODE_FIND_PLAYER:
				$this->logic->createRoom($data['data']['red_player'], $data['data']['blue_player']);
				break;
		}
	}
	
}


new Server();
