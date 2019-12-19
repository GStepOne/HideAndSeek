<?php

require_once __DIR__ . '/autoload.php';

use App\Manager\Game;
use App\Model\Player;

$redId  = "red_player";
$blueId = "blue_player";
#create game controller

$game = new Game();
# add new player
$game->createPlayer($redId, 6, 1);

$game->createPlayer($blueId, 6, 10);

for ($i = 0; $i <= 300; $i++) {
	$direct = mt_rand(0, 3);
	$game->playerMove($redId, Player::DIRECTION[$direct]);
	if ($game->isGameOver()) {
		$game->printGameMap();
		echo 'Game over' . PHP_EOL;
		
		return;
	}
	
	$direct = mt_rand(0, 3);
	$game->playerMove($blueId, Player::DIRECTION[$direct]);
	if ($game->isGameOver()) {
		$game->printGameMap();
		echo 'Game over' . PHP_EOL;
		
		return;
	}
	
	echo PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
	$game->printGameMap();
	usleep(300000);
	
}
?>


