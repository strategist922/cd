<?php
/**
 * c/d testing
 *
 * @author Jacob Oliver
 * @version 0.0.1
 */

include_once 'class.abcache.php';

class cd
{
	/**
	* start your A/B test
	*
	* @param string name
	* @param mixed options
	* @param boolean force
	* @return mixed
	*/
    public static function start($name, $options = null, $force = false)
    {
        $subject = new Subject($name,$options);
		if(!$subject->start_time || $force)
			$subject->create();
        return is_array($options) ? $options[$subject->key] : $options;
    }

	/**
	* finish your A/B test
	*
	* @param string name
	* @param boolean force
	* @return void
	*/
    public static function goal($name, $force = false)
    {
        $subject = new Subject($name);
        if ($subject->status === '0' || $force)
            $subject->converted();
    }
	
	/**
	* get conversion rates
	*
	* @param string name
	* @return array
	*/
	public static function getConversions($name)
	{
		$cache = new ABCache();
		$visitors = $cache->get($name . ':start');
		$finish = $cache->get($name . ':finish');
		$conversions = array();
		foreach($visitors as $date => $keys) {
			$options = max($options,count($keys));
			foreach($keys as $key => $val) {
				$conversions[$date][$key]['start'] = $val;
				$conversions[$date][$key]['finish'] = $finish[$date][$key];
			}
		}
		return array($conversions,$options);
	}
	
	/**
	* computes z-score and calculates probability of winning
	*
	* @param int converted_1
	* @param int tested_1
	* @param int converted_2
	* @param int tested_2
	* @return float
	*/
	public static function calculateProbability($converted_1,$tested_1,$converted_2,$tested_2)
	{
		$z_score = self::_calculateZScore($converted_1,$tested_1,$converted_2,$tested_2);
		$b1 =  0.319381530;
	  	$b2 = -0.356563782;
	  	$b3 =  1.781477937;
	  	$b4 = -1.821255978;
	  	$b5 =  1.330274429;
	  	$p  =  0.2316419;
	  	$c  =  0.39894228;	
	  	if ($z_score >= 0) {
	      	$t = 1/(1+$p*$z_score);
	      	return (1-$c*exp(-$z_score*$z_score/2)*$t*($t*($t*($t*($t*$b5+$b4)+$b3)+$b2)+$b1));
	  	}
	  	$t = 1/(1-$p*$z_score);
		return ($c*exp(-$z_score *$z_score/2.0)*$t*($t*($t*($t*($t*$b5+$b4)+$b3)+$b2)+$b1));
	}
	
	/**
	* calculates z-score
	*
	* @param int converted1
	* @param int tested1
	* @param int converted2
	* @param int tested2
	* @return float
	*/
	private static function _calculateZScore($converted1,$total1,$converted2,$total2)
	{
		$rate1 = $converted1/$total1;
		$rate2 = $converted2/$total2;
		return ($rate1-$rate2)/sqrt(($rate1*(1-$rate1)/$total1)+($rate2*(1-$rate2)/$total2));
	}
	
	/**
	* calculates standard error
	*
	* @param int converted
	* @param int tested
	* @param int percentile
	* @return float
	*/
	public static function calculateSE($converted,$total,$percentile = 80)
	{
		$percent_to_z = array(95 => 1.96, 90 => 1.645, 80 => 1.282);
		$z_score = $percent_to_z[$percentile];
		$rate = $converted/$total;
		return $z_score*($rate*(1-$rate)/$total);
	}
	
	/**
	* computes percent improvement
	*
	* @param int converted1
	* @param int tested1
	* @param int converted2
	* @param int tested2
	* @return float
	*/
	public static function calculateImprovement($converted1,$total1,$converted2,$total2)
	{
		return (($converted1/$total1)-($converted2/$total2))/($converted2/$total2);
	}
}


class Subject
{
	public $start_time;	//timestamp of start
	public $key;		//which option
	public $status; 	//1 for converted else 0

	/**
	* constructor
	*
	* @param string test_name
	* @param mixed options
	* @return void
	*/
	public function __construct($test_name,$options = null)
	{
		$this->test_name = $test_name;
		$this->count = is_array($options) ? count($options) - 1 : 0;
		$cookie = $_COOKIE['ab'];
		$tests = explode(':',$cookie);
		foreach($tests as $test){
			$parts = explode('|',$test);
			if($parts[0] == substr($this->test_name,0,1)) {
				$this->start_time = $parts[1];
				$this->key = $parts[2];
				$this->status = $parts[3];
				break;
			}
		}
	}
	
	/**
	* create a subject
	*
	* @return void
	*/
	public function create()
	{
		$this->cache->increment($this->test_name.':start',$this->key.':'.date('Y-m-d'));
		$this->start_time = time();
		$this->key = rand(0,$this->count);
		$this->status = 0;
		$this->cache = new ABCache();
		$this->_save();
	}
	
	/**
	* measure a converions
	*
	* @return void
	*/
	public function converted()
	{	
		$this->cache->increment($this->test_name.':finish',$this->key.':'.date('Y-m-d',$this->start_time));
		$this->status = 1;
		$this->_save();
	}
	
	/**
	* save a subject
	*
	* @return void
	*/
	private function _save()
	{
		$tests = explode(':',$_COOKIE['ab']);
		foreach($tests as $test){
			$parts = explode('|',$test);
			if($parts[0] && $parts[0] != substr($this->test_name,0,1))
				$cookie .= $test . ':';
		}
		$cookie .= substr($this->test_name,0,1) . '|' . implode('|',array_slice(get_object_vars($this),0,3));
		setcookie('ab',$cookie);
	}
}
