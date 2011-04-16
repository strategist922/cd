c/d
======

A simple A/B testing framework in php  
[iRedis](https://github.com/antirez/redis) must be included somewhere 


Setup
--------

include

	include_once 'class.cd.php'
	
start

	CD::start('test',array(option1,option2));

finish

	CD::goal('test');


and that's it! c/d takes care of the rest!
	
Guts
--------
	* iRedis php library used to store data
	* everybody gets cookied and recieves the same option on subsequent visits to the start page
	* they are only counted once on the goal page
	* view.metrics.php gives you an example of a nice conversion page and uses the Google Visualization JS Library

![view.metrics.php](http://dl.dropbox.com/u/2024444/screenshot.png)
	

ToDo
--------
	* abstract out iRedis so any caching layer could be used
	* multi-variate testing and all the math that entails
	* pass a 'subject' in the function call
	* filter out web crawlers/ability to filter out a subset of visitors