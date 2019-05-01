<?php
namespace Fuzzy;

class Model {
	public function __construct ($model) {
		/**
		 * $model should be an object with:
		 *	inputs
		 *	rules
		 *	outputs
		 */
		if (is_string ($model))
			$model = json_decode ($model);
		if (is_array ($model))
			$model = (object) $model;
		if (!is_object ($model))
			throw new Exception (/*T[*/'You need to provide an appropiate argument to the constructor.'/*]*/);
		if (!isset ($model->inputs))
			throw new Exception (/*T[*/'The Fuzzy Model requires the inputs key.'/*]*/);
		if (!isset ($model->outputs))
			throw new Exception (/*T[*/'The Fuzzy Model requires the ouputs key.'/*]*/);
		if (!isset ($model->rules))
			throw new Exception (/*T[*/'The Fuzzy Model requires the rules key.'/*]*/);

		/**
		 * check so that intputs are of the form:
		 * 'input' => [
		 *	'attribute' => [ 'fuzzy-set' ]
		 *	...
		 *	]
		 */
		$this->inputs = [];
		$inputs = (array) $model->inputs;
		if (empty ($inputs))
			throw new Exception (/*T[*/'You need to provide input variables to the Fuzzy Model.'/*]*/);
		foreach ($inputs as $input => $fuzzy_set_descriptions) {
			$this->inputs[$input] = [];
			foreach ($fuzzy_set_descriptions as $fuzzy_set => $fuzzy_set_description) {
				$fuzzy_set_description = (array) $fuzzy_set_description;
				$fuzzy_set_description_size = sizeof ($fuzzy_set_description);
				if ($fuzzy_set_description_size < 2 || $fuzzy_set_description_size > 4)
					throw new Exception ();
				$this->inputs[$input][$fuzzy_set] = [];
				foreach ($fuzzy_set_description as $value) {
					if (is_array ($value))
						$value = (object) $value;
					if (!is_object ($value))
						throw new Exception ();
					if (!isset ($value->x) || !isset ($value->y))
						throw new Exception ();
					$this->inputs[$input][$fuzzy_set][] = (object) ['x' => floatval ($value->x), 'y' => floatval ($value->y)];
				}
				$this->inputs[$input][$fuzzy_set] = new Set ($this->inputs[$input][$fuzzy_set]);
			}
		}
		
		/**
		 * check so that intputs are of the form:
		 * 'output' => [
		 *	'attribute' => [ 'fuzzy-set' ]
		 *	...
		 *	]
		 */
		$this->outputs = [];
		$outputs = (array) $model->outputs;
		if (empty ($outputs))
			throw new Exception ();
		foreach ($outputs as $output => $fuzzy_set_descriptions) {
			$this->outputs[$output] = [];
			foreach ($fuzzy_set_descriptions as $fuzzy_set => $fuzzy_set_description) {
				$fuzzy_set_description = (array) $fuzzy_set_description;
				$fuzzy_set_description_size = sizeof ($fuzzy_set_description);
				if ($fuzzy_set_description_size < 2 || $fuzzy_set_description_size > 4)
					throw new Exception ();
				$this->outputs[$output][$fuzzy_set] = [];
				foreach ($fuzzy_set_description as $value) {
					if (is_array ($value))
						$value = (object) $value;
					if (!is_object ($value))
						throw new Exception ();
					if (!isset ($value->x) || !isset ($value->y))
						throw new Exception ();
					$this->outputs[$output][$fuzzy_set][] = (object) ['x' => floatval ($value->x), 'y' => floatval ($value->y)];
				}
				$this->outputs[$output][$fuzzy_set] = new Set ($this->outputs[$output][$fuzzy_set]);
			}
		}

		/**
		 * check if rules are of the form
		 * if <condition> then <result>
		 * where
		 * <condition> can contain keywords:
		 *	{input} is {input_fuzzy_set}, and/or ...
		 * <result> can contain keywords:
		 *	{output} is {output_fuzzy_set}
		 */
		$this->rules = [];
		$rules = (array) $model->rules;
		if (empty ($rules))
			throw new Exception ();
		foreach ($rules as $rule) {
			$this->rules[] = new Rule ($rule);
		}
	}

	public function run ($inputs) {
		$vars = [];

		foreach ($inputs as $input => $value) {
			if (!in_array ($input, array_keys ($this->inputs)))
				continue;
			$vars[$input] = (object) [
				'crisp' => floatval ($value),
				'sets' => $this->inputs[$input]
			];
		}

		$centroids = [];
		foreach ($this->rules as $rule) {
			$outputs = $rule->eval ($vars);
			if (!empty ($outputs))
			foreach ($outputs as $output => $sets) {
				if (!isset ($this->outputs[$output]))
					continue;

				if (!empty ($sets))
				foreach ($sets as $set => $clip) {
					if (!isset ($this->outputs[$output][$set]))
						continue;
					
					if (!isset ($centroids[$output]))
						$centroids[$output] = (object) [
							'area' => 0,
							'X' => 0,
							'Y' => 0,
						];
					
					$shape = $this->outputs[$output][$set]->get ('shape', (object) ['c' => min ($clip)]);
					$centroids[$output]->area += $shape->area;
					$centroids[$output]->X += $shape->area * $shape->centroid->X;
					$centroids[$output]->Y += $shape->area * $shape->centroid->Y;
				}
			}
		}

		foreach ($centroids as $output => $data) {
			$centroids[$output]->X = $data->X / $data->area;
			$centroids[$output]->Y = $data->Y / $data->area;
			unset ($centroids[$output]->area);
		}

		return $centroids;
	}
}