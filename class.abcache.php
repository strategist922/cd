<?php
/**
 * example Cache layer... must have an increment function
 *
 * @author Jacob Oliver
 * @version 0.0.1
 */

include_once 'iredis.php';

class ABCache
{
	public $redis;

	public function __construct() 
	{
		$this->redis = new iRedis();
	}

  	public function increment($name,$key) 
	{
		var_dump($name);
		var_dump($key);
		$this->redis->hincrby($name,$key,1);
	}
	
	public function get($name,$key = null)
	{
		$response = $this->redis->hgetall($name);
		$results = array();
		$j = 0;
		foreach ($response AS $row) {
			if ($j % 2 == 0) {
				$parts = explode(':',$row);
				$results[$parts[1]][$parts[0]] = $response[$j+1];
			}
			$j++;
		}
		return $results;
	}
}