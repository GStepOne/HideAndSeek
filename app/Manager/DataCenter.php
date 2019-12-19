<?php

namespace App\Manager;

use App\Lib\Redism as Redis;

class DataCenter
{
	
	const PREFIX_KEY = "game";
	
	public static $global;
	
	public static $server;
	
	
	public static function initDataCenter()
	{
		
		//清空匹配对列
		
		$key = self::redisKey(':player_wait_list');
		self::redis()->del($key);
		
		//清空在线玩家
		$key = self::redisKey(':online_player');
		self::redis()->del($key);
		
		//清空在线玩家信息
		$key = self::redisKey(':player_info');
		self::redis()->del($key);
	}
	
	
	public static function log($info, $context = [], $level = 'INFO')
	{
		
		if ($context) {
			echo sprintf("[%s][%s]: %s %s\n", date('Y-m-d H:i:s'), $level, $info, json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		} else {
			echo sprintf("[%s][%s]: %s\n", date('Y-m-d H:i:s'), $level, $info);
		}
	}
	
	//获取等待用户的总人数
	public static function getPlayerWaitLen()
	{
		
		$key = self::PREFIX_KEY . ":player_wait_list";
		
		return self::redis()->sCard($key);
	}
	
	public static function pushPlayerToWaitList($playerId)
	{
		
		$key = self::PREFIX_KEY . ":player_wait_list";
		self::redis()->sAdd($key, $playerId);
	}
	
	
	public static function popPlayerFromWaitList()
	{
		
		$key = self::PREFIX_KEY . ":player_wait_list";
		
		return self::redis()->sPop($key);
	}
	
	public static function delPlayerFromWaitList($playerId)
	{
		
		$key = self::PREFIX_KEY . ":player_wait_list";
		self::redis()->sRem($key, $playerId);
	}
	
	//绑定玩家id和fd
	public static function setPlayerFd($playerId, $playerFd)
	{
		
		$key   = self::redisKey(':player_info');
		$field = 'player_fd:' . $playerId;
		self::redis()->hSet($key, $field, $playerFd);
	}
	
	//根据玩家id获取fd
	public static function getPlayerFd($playerId)
	{
		
		$key = self::redisKey(':player_info');
		
		$field = 'player_fd:' . $playerId;
		
		return self::redis()->hGet($key, $field);
	}
	
	//根据玩家id删除fd
	public static function delPlayerFd($playerId)
	{
		
		$key   = self::redisKey(':player_info');
		$field = 'player_fd:' . $playerId;
		self::redis()->hDel($key, $field);
	}
	
	//绑定fd和玩家id
	public static function setPlayerId($playerFd, $playerId)
	{
		
		$key = self::redisKey(':player_info');
		
		$field = 'player_id:' . $playerFd;
		
		self::redis()->hSet($key, $field, $playerId);
	}
	
	//根据fd获取玩家id
	public static function getPlayerId($playerFd)
	{
		
		$key = self::redisKey(':player_info');
		
		$field = 'player_id:' . $playerFd;
		
		return self::redis()->hGet($key, $field);
	}
	
	//解绑fd和玩家id
	
	public static function delPlayerId($playerFd)
	{
		
		$key = self::redisKey(':player_info');
		
		$field = 'player_id:' . $playerFd;
		
		self::redis()->hDel($key, $field);
	}
	
	
	public static function setPlayerInfo($playerId, $playerFd)
	{
		
		self::setPlayerId($playerFd, $playerId);
		self::setPlayerFd($playerId, $playerFd);
		self::setOnlinePlayer($playerId);
	}
	
	
	public static function delPlayerInfo($playerFd)
	{
		
		$playerId = self::getPlayerId($playerFd);
		self::delPlayerFd($playerId);
		self::delPlayerId($playerFd);
		self::delOnlinePlayer($playerId);
		self::delPlayerFromWaitList($playerId);
	}
	
	
	/**
	 * Description:获取redis实例
	 * User: Jack
	 * Date: 2019/11/12 17:29
	 * @return mixed
	 */
	
	public static function redis()
	{
		
		return Redis::getInstance();
	}
	
	
	public static function setPlayerRoomId($playerId, $roomId)
	{
		
		$key   = self::redisKey(':player_info');
		$field = 'room_id:' . $playerId;
		self::redis()->hSet($key, $field, $roomId);
	}
	
	public static function getPlayerRoomId($playerId)
	{
		
		$key = self::redisKey(':player_info');
		
		$field = 'room_id:' . $playerId;
		
		return self::redis()->hGet($key, $field);
	}
	
	
	public static function delPlayerRoomId($playerId)
	{
		
		$key   = self::redisKey(':player_info');
		$field = 'room_id:' . $playerId;
		self::redis()->hDel($key, $field);
	}
	
	
	public static function setOnlinePlayer($playerId)
	{
		
		$key = self::PREFIX_KEY . ':online_player';
		
		self::redis()->hSet($key, $playerId, 1);
		
	}
	
	public static function getOnlinePlayer($playerId)
	{
		
		$key = self::PREFIX_KEY . ':online_player';
		
		return self::redis()->hGet($key, $playerId);
	}
	
	public static function delOnlinePlayer($playerId)
	{
		
		$key = self::PREFIX_KEY . ':online_player';
		self::redis()->hDel($key, $playerId);
	}
	
	
	public static function lenOnlinePlayer()
	{
		
		$key = self::PREFIX_KEY . ":online_player";
		
		return self::redis()->hLen($key);
	}
	
	
	static private function redisKey($suffix)
	{
		
		return self::PREFIX_KEY . $suffix;
	}
	
	
	public static function addPlayerWinTimes($playerId)
	{
		
		$key = self::redisKey(':player_rank');
		
		self::redis()->zIncrBy($key, 1, $playerId);
		
	}
	
	
	public static function getPlayersRank()
	{
		
		$key = self::redisKey(':player_rank');
		
		return self::redis()->zRevRange($key, 0, 9, true);
	}
	
}
