<?php
date_default_timezone_set('America/New_York');
ini_set('html_errors', false);
header('Content-type: application/json');
include_once('keys.php');
include_once('HTTP.php');

$testDate = isset($_GET['date']) ? $_GET['date'] : false;
$county = isset($_GET['county']) ? $_GET['county'] : false;

if (!$testDate) {
	$now = new DateTime();
	$now->setTime(0, 0, 0);
	$testDate = explode('+', $now->format('c'))[0];
}

if ($testDate) {
	$http = new HTTP();

	$httpConfig = array(
		'url' => 'https://health.data.ny.gov/resource/xdss-u53e.json',
		'headers' => array(
			'Authorization' => 'Basic '.NYS_API_AUTH
		)
	);

	if ($testDate) {
		$httpConfig['query'] = array(
			'test_date' => $testDate
		);
	}

	$response = $http->get($httpConfig);

	if ($response['success']) {
		$total = array(
			'county' => 'all',
			'test_date' => $testDate,
			'new_positives' => 0,
			'cumulative_number_of_positives' => 0,
			'total_number_of_tests' => 0,
			'cumulative_number_of_tests' => 0
		);

		$return = array(
			'testing' => array(),
			'counties' => array()
		);

		if (is_array($response['data'])) {
			foreach ($response['data'] as $county) {
				$county['total_number_of_tests'] = intval($county['total_number_of_tests']);
				$county['new_positives'] = intval($county['new_positives']);
				$county['cumulative_number_of_tests'] = intval($county['cumulative_number_of_tests']);
				$county['cumulative_number_of_positives'] = intval($county['cumulative_number_of_positives']);

				$total['total_number_of_tests'] += $county['total_number_of_tests'];
				$total['new_positives'] += $county['new_positives'];
				
				$total['cumulative_number_of_tests'] += $county['cumulative_number_of_tests'];
				$total['cumulative_number_of_positives'] += $county['cumulative_number_of_positives'];

				if ($county['total_number_of_tests'] > 0) {
					$county['percent_positives'] = ($county['new_positives'] / $county['total_number_of_tests']) * 100;
				} else {
					$county['percent_positives'] = 0;
				}
				
				if ($county['cumulative_number_of_tests'] > 0) {
					$county['cumulative_percent_positives'] = ($county['cumulative_number_of_positives'] / $county['cumulative_number_of_tests']) * 100;
				} else {
					$county['cumulative_percent_positives'] = 0;
				}

				$return['testing'][$county['county']] = $county;
				$return['counties'][] = $county['county'];
			}

			$total['percent_positives'] = $total['new_positives'] / $total['total_number_of_tests'] * 100;
			$total['cumulative_percent_positives'] = $total['cumulative_number_of_positives'] / $total['cumulative_number_of_tests'] * 100;
		}

		$return['testing']['total'] = $total;

		$date = new DateTime($testDate);
		$encoded = json_encode($return, JSON_PRETTY_PRINT);
		file_put_contents('data/'.$date->format('Y-m-d').'.json', $encoded);
		echo $encoded;
	} else {
		echo json_encode(array());
	}
} else {
	echo json_encode(array());
}

?>