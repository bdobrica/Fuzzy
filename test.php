<?php
namespace Fuzzy;

include ('exception.php');
include ('set.php');
include ('rule.php');
include ('model.php');

$model = new Model ((object) [
	'inputs'	=> [
		'price'	=> [
			'very_low'	=> [ [ 'x' => 0, 'y' => 1 ], [ 'x' => 1.25, 'y' => 0 ] ],
			'low'		=> [ [ 'x' => 0, 'y' => 0 ], [ 'x' => 1.25, 'y' => 1 ], [ 'x' => 2.5, 'y' => 0 ] ],
			'medium'	=> [ [ 'x' => 1.25, 'y' => 0 ], [ 'x' => 2.5, 'y' => 1 ], [ 'x' => 3.75, 'y' => 0 ] ],
			'high'		=> [ [ 'x' => 2.5, 'y' => 0 ], [ 'x' => 3.75, 'y' => 1 ], [ 'x' => 5, 'y' => 0 ] ],
			'very_high'	=> [ [ 'x' => 3.75, 'y' => 0 ], [ 'x' => 5, 'y' => 1 ] ],
		],
		'temperature' => [
			'very_low' 	=> [ [ 'x' => 0, 'y' => 1 ], [ 'x' => 25, 'y' => 0 ] ],
			'low'	 	=> [ [ 'x' => 0, 'y' => 0 ], [ 'x' => 25, 'y' => 1 ], [ 'x' => 50, 'y' => 0 ] ],
			'medium' 	=> [ [ 'x' => 25, 'y' => 0 ], [ 'x' => 50, 'y' => 1 ], [ 'x' => 75, 'y' => 0 ] ],
			'high'	 	=> [ [ 'x' => 50, 'y' => 0 ], [ 'x' => 75, 'y' => 1 ], [ 'x' => 100, 'y' => 0 ] ],
			'very_high'	=> [ [ 'x' => 75, 'y' => 0 ], [ 'x' => 100, 'y' => 1 ] ],
		],
	],
	'outputs'	=> [
		'sales' => [
			'very_low' 	=> [ [ 'x' => 0, 'y' => 1 ], [ 'x' => 25, 'y' => 0 ] ],
			'low'	 	=> [ [ 'x' => 0, 'y' => 0 ], [ 'x' => 25, 'y' => 1 ], [ 'x' => 50, 'y' => 0 ] ],
			'medium' 	=> [ [ 'x' => 25, 'y' => 0 ], [ 'x' => 50, 'y' => 1 ], [ 'x' => 75, 'y' => 0 ] ],
			'high'	 	=> [ [ 'x' => 50, 'y' => 0 ], [ 'x' => 75, 'y' => 1 ], [ 'x' => 100, 'y' => 0 ] ],
			'very_high'	=> [ [ 'x' => 75, 'y' => 0 ], [ 'x' => 100, 'y' => 1 ] ],
		]
	],
	'rules'		=> [
		'if price is medium then sales are high',
		'if temperature is high then sales are very_high',
	],
]);


$result = $model->run (['price' => 0.5, 'temperature' => 55]);
var_dump ($result);