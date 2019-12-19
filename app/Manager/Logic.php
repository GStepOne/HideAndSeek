<?php

namespace App\Manager;

use App\Manager\DataCenter;
use App\Manager\TaskManager;
use App\Manager\Sender;
use App\Model\Player;

class Logic
{
	
	const PLAYER_DISPLAY_LEN = 2;
	
	const GAME_TIME_LIMIT = 10;
	
	public function matchPlayer($playerId)
	{
		
		//将用户放入对列
		DataCenter::pushPlayerToWaitList($playerId);
		
		//发起一个Task尝试匹配
		DataCenter::$server->task(['code' => TaskManager::TASK_CODE_FIND_PLAYER]);
	}
	
	
	//开房
	
	public function createRoom($redPlayer, $bluePlayer)
	{
		
		$roomId = uniqid('room_');
		$this->bindRoomWorker($redPlayer, $roomId);
		$this->bindRoomWorker($bluePlayer, $roomId);
	}
	
	//关闭房间
	public function closeRoom($closerId)
	{
		
		$roomId = DataCenter::getPlayerRoomId($closerId);
		
		if (!empty($roomId)) {
			$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
			$players     = $gameManager->getPlayers();
			foreach ($players as $player) {
				if ($player->getId() != $closerId) {
					Sender::sendMessage($player->getId(), Sender::MSG_OTHER_CLOSE, Sender::CODE_MSG[Sender::MSG_OTHER_CLOSE]);
				}
				
				DataCenter::delPlayerRoomId($player->getId());
			}
			
			unset(DataCenter::$global['rooms'][$roomId]);
		}
		
	}
	
	
	private function bindRoomWorker($playerId, $roomId)
	{
		
		$playerFd = DataCenter::getPlayerFd($playerId);
		DataCenter::$server->bind($playerFd, crc32($roomId));
		DataCenter::setPlayerRoomId($playerId, $roomId);
		Sender::sendMessage($playerId, Sender::MSG_ROOM_ID, ['room_id' => $roomId]);
	}
	
	
	public function startRoom($roomId, $playerId)
	{
		
		if (!isset(DataCenter::$global['rooms'][$roomId])) {
			DataCenter::$global['rooms'][$roomId] = [
				'id' => $roomId,
				'manager' => new Game()
			];
		}
		
		$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
		if (empty(count($gameManager->getPlayers()))) {
			$gameManager->createPlayer($playerId, 6, 1);
			Sender::sendMessage($playerId, Sender::MSG_WAIT_PLAYER);
		} else {
			$gameManager->createPlayer($playerId, 6, 10);
			DataCenter::$global['rooms'][$roomId]['timer_id'] = $this->createGameTimer($roomId);
			Sender::sendMessage($playerId, Sender::MSG_ROOM_START);
			$this->sendGameInfo($roomId);
		}
	}
	
	
	private function sendGameInfo($roomId)
	{
		
		$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
		$players     = $gameManager->getPlayers();
		$mapData     = $gameManager->getMapData();
		
		foreach (array_reverse($players) as $player) {
			$mapData[$player->getX()][$player->getY()] = $player->getId();
		}
		
		foreach ($players as $player) {
			$data = [
				'players' => $players,
				'map_data' => $this->getNearMap($mapData, $player->getX(), $player->getY()),
				'timer_limit' => self::GAME_TIME_LIMIT
			];
			
			Sender::sendMessage($player->getId(), Sender::MSG_GAME_INFO, $data);
		}
	}
	
	//获取自己周边范围的数据
	private function getNearMap($mapData, $x, $y)
	{
		
		$result = [];
		for ($i = -1 * self::PLAYER_DISPLAY_LEN; $i <= self::PLAYER_DISPLAY_LEN; $i++) {
			$tmp = [];
			for ($j = -1 * self::PLAYER_DISPLAY_LEN; $j <= self::PLAYER_DISPLAY_LEN; $j++) {
				$tmp[] = $mapData[$x + $i][$y + $j] ?? 0;
			}
			$result[] = $tmp;
		}
		
		return $result;
	}
	
	
	public function movePlayer($direction, $playerId)
	{
		
		if (!in_array($direction, Player::DIRECTION)) {
			
			echo $direction;
			
			return;
		}
		
		$roomId = DataCenter::getPlayerRoomId($playerId);
		if (isset(DataCenter::$global['rooms'][$roomId])) {
			$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
			$gameManager->playerMove($playerId, $direction);
			$this->sendGameInfo($roomId);
			$this->checkGameOver($roomId);
		}
	}
	
	
	private function checkGameOver($roomId)
	{
		
		$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
		
		if ($gameManager->isGameOver()) {
			$players = $gameManager->getPlayers();
			$winner  = current($players)->getId();
			$this->gameOver($roomId, $winner);
		}
	}
	
	//发起挑战
	public function makeChallenge($opponentId, $playerId)
	{
		
		if (empty(DataCenter::getOnlinePlayer($opponentId))) {
			
			Sender::sendMessage($playerId, Sender::MSG_OPPONENT_OFFLINE);
			
		} else {
			$data = ['challenger_id' => $playerId];
			
			Sender::sendMessage($opponentId, Sender::MSG_MAKE_CHALLENGE, $data);
		}
	}
	
	//接受挑战
	public function acceptChallenge($challengerId, $playerId)
	{
		
		$this->createRoom($challengerId, $playerId);
	}
	
	#拒绝挑战
	#哈哈
	public function refuseChallenge($challengerId)
	{
		
		Sender::sendMessage($challengerId, Sender::MSG_REFUSE_CHALLENGE);
		
	}
	
	#创建定时器
	
	private function createGameTimer($roomId)
	{
		
		return swoole_timer_after(self::GAME_TIME_LIMIT * 1000, function () use ($roomId) {
			
			if (isset(DataCenter::$global['rooms'][$roomId])) {
				$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
				$players     = $gameManager->getPlayers();
				$winner      = end($players)->getId();
				DataCenter::addPlayerWinTimes($winner);
				
				foreach ($players as $player) {
					Sender::sendMessage($player->getId(), Sender::MSG_GAME_OVER, ['winner' => $winner]);
					DataCenter::delPlayerRoomId($player->getId());
				}
				unset(DataCenter::$global['rooms'][$roomId]);
			}
		});
		
	}
	
	# whether game is over
	private function gameOver($roomId, $winner)
	{
		
		$gameManager = DataCenter::$global['rooms'][$roomId]['manager'];
		$players     = $gameManager->getPlayers();
		DataCenter::addPlayerWinTimes($winner);
		foreach ($players as $player) {
			Sender::sendMessage($player->getId(), Sender::MSG_GAME_OVER, ['winner' => $winner]);
			DataCenter::delPlayerRoomId($player->getId());
		}
		
		unset(DataCenter::$global['rooms'][$roomId]);
	}
}
