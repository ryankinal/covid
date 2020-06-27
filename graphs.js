function getData(date) {	
	return new Promise(function(resolve, reject) {
		var xhr = new XMLHttpRequest();

		xhr.addEventListener('load', function() {
			if (xhr.status >= 200 && xhr.status < 300) {
				resolve(JSON.parse(xhr.responseText));
			} else {
				reject(JSON.parse(xhr.responseText));
			}
		})
		xhr.open('GET', 'data/' + date + '.json');
		xhr.send();
	});
}

let dates = [];
let date = new Date();
let year = date.getYear();
let month = date.getMonth();
let days = 60; 
let day = date.getDate() - days;

date = new Date(year, month, day);

for (let i = 0; i < days - 1; i++) {
	date = new Date(1900 + year, date.getMonth(), date.getDate() + 1);
	month = date.getMonth() + 1;

	if (month < 10) {
		month = '0' + month;
	}

	day = date.getDate();

	if (day < 10) {
		day = '0' + day;
	}

	let dateString = (1900 + date.getYear()) + '-' + month + '-' + day;
	dates.push(dateString);
}

let downstateCounties = [
	'Orange',
	'Rockland',
	'Westchester',
	'Suffolk',
	'Nassau',
	'Richmond',
	'Bronx',
	'Kings',
	'Queens'
];

let upstateCounties = [];

let graphElements = {
	absolute: [],
	percent: [],
	cumulative: []
};

let selector = document.getElementById('graphSelector');
let selectorChangeHandler = function() {
	document.querySelectorAll('article > div').forEach(function(d) {
		d.style.display = 'none';
	});

	graphElements[selector.value].forEach(function(d) {
		d.style.display = 'block';
	});
};

selector.addEventListener('change', selectorChangeHandler);

Promise.all(
	dates.map(function(d) {
		return getData(d);
	})
).then(function(data) {
	let focus = [
		'total',
		'monroe',
		'erie',
		'chautauqua',
		'upstate',
		'downstate'
	];
	let graphSettings = [];
	let seriesByArea = {};

	data.forEach(function(dateData) {
		if (dateData.counties.length === 0) {
			return true;
		}

		let downstateData = {
			percent_positives: 0,
			total_number_of_tests: 0,
			new_positives: 0,
			cumulative_number_of_positives: 0
		};

		let upstateData = {
			percent_positives: 0,
			total_number_of_tests: 0,
			new_positives: 0,
			cumulative_number_of_positives: 0
		};

		dateData.counties.forEach(function(county) {
			if (dateData['testing'][county]) {
				let countyData = dateData['testing'][county];
				let key = county.toLowerCase();

				if (focus.indexOf(county.toLowerCase()) >= 0) {
					seriesByArea[key] = seriesByArea[key] || {
						title: county + ' County',
						percent_positives: [],
						total_number_of_tests: [],
						new_positives: [],
						cumulative_number_of_positives: []
					};

					seriesByArea[key].percent_positives.push(countyData.percent_positives);
					seriesByArea[key].total_number_of_tests.push(countyData.total_number_of_tests);
					seriesByArea[key].new_positives.push(countyData.new_positives);
					seriesByArea[key].cumulative_number_of_positives.push(countyData.cumulative_number_of_positives);
				}

				if (downstateCounties.indexOf(county)) {
					downstateData.total_number_of_tests += parseInt(countyData.total_number_of_tests);
					downstateData.new_positives += parseInt(countyData.new_positives);
					downstateData.cumulative_number_of_positives += parseInt(countyData.cumulative_number_of_positives);
				} else {
					upstateCounties.push(county);
					upstateData.total_number_of_tests += parseInt(countyData.total_number_of_tests);
					upstateData.new_positives += parseInt(countyData.new_positives);
					upstateData.cumulative_number_of_positives += parseInt(countyData.cumulative_number_of_positives);
				}
			}
		});

		seriesByArea.downstate = seriesByArea.downstate || {
			title: 'Downstate',
			percent_positives: [],
			total_number_of_tests: [],
			new_positives: [],
			cumulative_number_of_positives: []
		};

		seriesByArea.downstate.percent_positives.push(downstateData.new_positives / downstateData.total_number_of_tests * 100);
		seriesByArea.downstate.total_number_of_tests.push(downstateData.total_number_of_tests);
		seriesByArea.downstate.new_positives.push(downstateData.new_positives);
		seriesByArea.downstate.cumulative_number_of_positives.push(downstateData.cumulative_number_of_positives);

		seriesByArea.upstate = seriesByArea.upstate || {
			title: 'Upstate',
			percent_positives: [],
			total_number_of_tests: [],
			new_positives: [],
			cumulative_number_of_positives: []
		};

		seriesByArea.upstate.percent_positives.push(upstateData.new_positives / upstateData.total_number_of_tests * 100);
		seriesByArea.upstate.total_number_of_tests.push(upstateData.total_number_of_tests);
		seriesByArea.upstate.new_positives.push(upstateData.new_positives);
		seriesByArea.upstate.cumulative_number_of_positives.push(upstateData.cumulative_number_of_positives);

		seriesByArea.total = seriesByArea.total || {
			title: 'New York State',
			percent_positives: [],
			total_number_of_tests: [],
			new_positives: [],
			cumulative_number_of_positives: []
		};

		seriesByArea.total.percent_positives.push(dateData.testing.total.new_positives / dateData.testing.total.total_number_of_tests * 100);
		seriesByArea.total.total_number_of_tests.push(dateData.testing.total.total_number_of_tests);
		seriesByArea.total.new_positives.push(dateData.testing.total.new_positives);
		seriesByArea.total.cumulative_number_of_positives.push(dateData.testing.total.cumulative_number_of_positives);
	});

	document.getElementById('loading').style.display = 'none';

	let fragment = document.createDocumentFragment();
	focus.forEach(function(f) {
		let article = document.createElement('article');
		let header = document.createElement('h1');
		header.appendChild(document.createTextNode(seriesByArea[f].title));

		let percentGraph = document.createElement('div');
		let absoluteGraph = document.createElement('div');
		let cumulativeGraph = document.createElement('div');

		article.appendChild(header);
		article.appendChild(percentGraph);
		article.appendChild(absoluteGraph);
		article.appendChild(cumulativeGraph);

		graphElements.percent.push(percentGraph);
		graphElements.absolute.push(absoluteGraph);
		graphElements.cumulative.push(cumulativeGraph);

		fragment.appendChild(article);

		setTimeout(function() {
			Highcharts.chart(percentGraph, {
				title: {
					text: 'Percent Positive (Daily)',
					subtitle: 'Percentage of tests that came back positive.'
				},
				yAxis: {
					title: {
						text: 'Testing Data'
					}
				},
				xAxis: {
					categories: dates
				},
				plotOptions: {
					series: {
						label: {
							connnectorAllowed: false
						}
					}
				},
				series: [{
					name: 'Positive %',
					data: seriesByArea[f].percent_positives
				}]
			});

			Highcharts.chart(absoluteGraph, {
				title: {
					text: 'Testing Data per Day',
				},
				yAxis: {
					title: {
						text: 'Testing Data'
					}
				},
				xAxis: {
					categories: dates
				},
				plotOptions: {
					series: {
						label: {
							connnectorAllowed: false
						}
					}
				},
				series: [{
					name: 'Tested',
					data: seriesByArea[f].total_number_of_tests
				}, {
					name: 'Positive',
					data: seriesByArea[f].new_positives
				}]
			});

			Highcharts.chart(cumulativeGraph, {
				title: {
					text: 'Total Number of Cases (Cumulative)',
				},
				yAxis: {
					title: {
						text: 'Cases'
					}
				},
				xAxis: {
					categories: dates
				},
				plotOptions: {
					series: {
						label: {
							connnectorAllowed: false
						}
					}
				},
				series: [{
					name: 'Cases',
					data: seriesByArea[f].cumulative_number_of_positives
				}]
			});

			document.body.classList.remove('loading');
		}, 100);
	});

	let udc = document.createElement('article');

	let udcHeader = document.createElement('h1');
	udcHeader.appendChild(document.createTextNode('Upstate/Downstate Comparison'));
	udc.appendChild(udcHeader);

	let udcCumulativeGraph = document.createElement('div');
	udc.appendChild(udcCumulativeGraph);

	let udcPercentGraph = document.createElement('div');
	udc.appendChild(udcPercentGraph);

	let udcAbsoluteGraph = document.createElement('div');
	udc.appendChild(udcAbsoluteGraph);

	graphElements.absolute.push(udcAbsoluteGraph);
	graphElements.cumulative.push(udcCumulativeGraph);
	graphElements.percent.push(udcPercentGraph);

	fragment.appendChild(udc);

	setTimeout(function() {
		Highcharts.chart(udcCumulativeGraph, {
			title: {
				text: 'Total Number of Cases (Cumulative)',
			},
			yAxis: {
				title: {
					text: 'Cases'
				}
			},
			xAxis: {
				categories: dates
			},
			plotOptions: {
				series: {
					label: {
						connnectorAllowed: false
					}
				}
			},
			series: [{
				name: 'Upstate',
				data: seriesByArea.upstate.cumulative_number_of_positives
			}, {
				name: 'Downstate',
				data: seriesByArea.downstate.cumulative_number_of_positives
			}]
		});

		Highcharts.chart(udcPercentGraph, {
			title: {
				text: 'Percentage Positive (Daily)',
			},
			yAxis: {
				title: {
					text: 'Percent Positive'
				}
			},
			xAxis: {
				categories: dates
			},
			plotOptions: {
				series: {
					label: {
						connnectorAllowed: false
					}
				}
			},
			series: [{
				name: 'Upstate',
				data: seriesByArea.upstate.percent_positives
			}, {
				name: 'Downstate',
				data: seriesByArea.downstate.percent_positives
			}]
		});

		Highcharts.chart(udcAbsoluteGraph, {
			title: {
				text: 'Testing Data per Day'
			},
			yAxis: {
				title: {
					text: 'Tests'
				}
			},
			xAxis: {
				categories: dates
			},
			plotOptions: {
				series: {
					label: {
						connnectorAllowed: false
					}
				}
			},
			series: [{
				name: 'Upstate Total Tests',
				data: seriesByArea.upstate.total_number_of_tests
			}, {
				name: 'Downstate Total Tests',
				data: seriesByArea.downstate.total_number_of_tests
			}, {
				name: 'Upstate Positive Tests',
				data: seriesByArea.upstate.new_positives
			}, {
				name: 'Downstate Positive Tests',
				data: seriesByArea.downstate.new_positives
			}]
		});
	}, 100);

	document.getElementById('graphs').appendChild(fragment);
	selectorChangeHandler();
});