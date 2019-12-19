<?php

namespace App\Manager;

use App\Model\Map;
use App\Model\Player;

class Game
{
	
	private $gameMap = [];
	
	private $players = [];
	
	public function __construct()
	{
		
		$this->gameMap = new Map(12, 12);
	}
	
	public function createPlayer($playerId, $x, $y)
	{
		
		$player = new Player($playerId, $x, $y);
		
		if (!empty($this->players)) {
			$player->setType(Player::PLAYER_TYPE_HIDE);
		}
		
		$this->players[$playerId] = $player;
		
	}
	
	# move palyer's coordinates
	public function playerMove($playerId, $direction)
	{
		
		$player = $this->players[$playerId];
		
		if ($this->canMoveToDirection($player, $direction)) {
			$player->{$direction}();
		}
	}
	
	# print Game Map
	
	public function printGameMap()
	{
		
		$mapData = $this->gameMap->getMapData();
		
		$font = [2 => '追,', 3 => '躲,'];
		
		foreach ($this->players as $player) {
			$mapData[$player->getX()][$player->getY()] = $player->getType() + 1;
		}
		
		foreach ($mapData as $line) {
			foreach ($line as $value) {
				if (empty($value)) {
					echo "墙, ";
				} elseif ($value == 1) {
					echo "    ";
				} else {
					echo $font[$value];
				}
			}
			
			echo PHP_EOL;
		}
	}
	
	
	#whether the game is over?
	public function isGameOver()
	{
		
		$result = false;
		$x      = -1;
		$y      = -1;
		
		$players = array_values($this->players);
		
		foreach ($players as $key => $player) {
			if ($key == 0) {
				$x = $player->getX();
				$y = $player->getY();
			} elseif ($x == $player->getX() && $y == $player->getY()) {
				$result = true;
			}
		}
		
		return $result;
		
	}
	
	#whether the player can move
	
	private function canMoveToDirection($player, $direction)
	{
		
		//判断是否可以走
		$origin_x = $player->getX();
		$origin_y = $player->getY();
		
		$moveCoor = $this->getMoveCoor($origin_x, $origin_y, $direction);
		$mapData  = $this->gameMap->getMapData();
		
		if (empty($mapData[$moveCoor[0]][$moveCoor[1]])) {
			return false;
		}
		
		return true;
	}
	
	
	private function getMoveCoor($x, $y, $direction)
	{
		
		switch ($direction) {
			case Player::UP:
				return [--$x, $y];
			case Player::DOWN:
				return [++$x, $y];
			case Player::LEFT:
				return [$x, --$y];
			case Player::RIGHT:
				return [$x, ++$y];
			default:
				return [$x, $y];
		}
	}
	
	
	public function getPlayers()
	{
		
		return $this->players;
	}
	
	
	public function getMapData()
	{
		
		return $this->gameMap->getMapData();
	}
	
}
