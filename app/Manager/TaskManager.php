<?php
/**
 *
 * User: Jack
 *
 * Date: 2019/11/13 14:08
 *
 */

namespace App\Manager;

use App\Manager\DataCenter;

class TaskManager
{
	
	const TASK_CODE_FIND_PLAYER = 1;
	
	public static function findPlayer()
	{
		
		$playerListLen = DataCenter::getPlayerWaitLen();
		
		if ($playerListLen >= 2) {
			
			$redPlayer  = DataCenter::popPlayerFromWaitList();
			$bluePlayer = DataCenter::popPlayerFromWaitList();
			
			return [
				'red_player' => $redPlayer,
				'blue_player' => $bluePlayer
			];
			
		}
		
		return false;
	}
	
	
}
