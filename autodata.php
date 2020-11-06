<?php
date_default_timezone_set('America/New_York');
include_once('HTTP.php');

$then = isset($argv[1]) ? $argv[1] : null;

if ($then)
{
	$then = new DateTime($then);
	$then->setTime(0, 0, 0);
}
else
{
	$then = new DateTime();
	$then->modify('-3 days');
	$then->setTime(0, 0, 0);
}

$now = new DateTime();
$now->setTime(0, 0, 0);

$http = new HTTP();

while ($then <= $now)
{
	$testDate = preg_replace('/\-0(4|5):00/', '', $then->format('c'));
	echo $testDate.':';

	$response = $http->get('http://local.covid.com/data.php?date='.$testDate);
	
	if ($response['success'])
	{
		echo " success\n";
	}
	else
	{
		print_r($response);
		break;
	}

	$then->modify('+1 day');
}
?>