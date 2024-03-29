<?php

namespace App\Model;

class Player implements \JsonSerializable
{
	
	const UP = 'up';
	
	const DOWN = 'down';
	
	const LEFT = 'left';
	
	const RIGHT = 'right';
	
	const PLAYER_TYPE_SEEK = 1;
	
	const PLAYER_TYPE_HIDE = 2;
	
	const DIRECTION = [self::UP, self::DOWN, self::LEFT, self::RIGHT];
	
	private $id;
	
	private $type = self::PLAYER_TYPE_SEEK;
	
	private $x;
	
	private $y;
	
	public function __construct($id, $x, $y)
	{
		
		$this->id = $id;
		$this->x  = $x;
		$this->y  = $y;
	}
	
	public function getId()
	{
		
		return $this->id;
	}
	
	public function up()
	{
		
		$this->x--;
	}
	
	public function down()
	{
		
		$this->x++;
	}
	
	public function left()
	{
		
		$this->y--;
	}
	
	public function right()
	{
		
		$this->y++;
	}
	
	public function getType()
	{
		
		return $this->type;
	}
	
	public function setType($type)
	{
		
		$this->type = $type;
	}
	
	
	public function getX()
	{
		
		return $this->x;
	}
	
	
	public function getY()
	{
		
		return $this->y;
	}
	
	
	public function jsonSerialize()
	{
		
		// TODO: Implement jsonSerialize() method.
		
		return [
			'id' => $this->id,
			'type' => $this->type,
			'x' => $this->x,
			'y' => $this->y
		
		];
	}
	
}
