<?php
namespace Fuzzy;

class Set {
	private $P;

	public function __construct ($description) {
		if (empty ($description) || !is_array ($description))
			throw new Exception ();
		$this->type = sizeof ($description);
		if ($this->type < 2)
			throw new Exception ();
		
		foreach ($description as $point) {
			if (is_array ($point))
				$point = (object) $point;
			if (!isset ($point->x) || !isset ($point->y))
				throw new Exception ();

			$this->P[] = (object) ['x' => floatval ($point->x), 'y' => floatval ($point->y)];
		}
	}

	public function get ($key = null, $opts = null) {
		if (is_string ($key)) {
			switch ($key) {
			case 'shape':
				$i = 0;
				$X = [];
				$Y = [];

				if (is_array ($opts))
					$opts = (object) $opts;
				if (is_string ($opts))
					$opts = json_decode ($opts);
				
				$r = end ($this->P);
				$p = reset ($this->P);

				$a = isset ($opts->a) ? floatval ($opts->a) : $p->x;
				$b = isset ($opts->b) ? floatval ($opts->b) : $r->x;
				$c = isset ($opts->c) ? $opts->c : 1;
				if (self::cmp ($a, $b) >= 0)
					throw new Exception ();

				if (self::cmp ($r->x, $a) <= 0) {
					$i += ($_i = $r->y * ($b - $a));
					$X[] = $a; $X[] = $b;
					$Y[] = $r->y;
				}
				else
				if (self::cmp ($r->x, $b) <= 0) {
					$i += ($_i = $r->y * ($b - $r->x));
					$X[] = $r->x; $X[] = $b;
					$Y[] = $r->y;
				}

				if (self::cmp ($b, $p->x) <= 0) {
					$i += ($_i = $p->y * ($b - $a));
					$X[] = $a; $X[] = $b;
					$Y[] = $p->y;
				}
				else
				if (self::cmp ($a, $p->x) <= 0) {
					$i += ($_i = $p->y * ($p->x - $a));
					$X[] = $p->x; $X[] = $a;
					$Y[] = $p->y;
				}

				while (($r = next ($this->P)) !== false) {
					$slope = self::div ($r->y - $p->y, $r->x - $p->x);
					$ax = $p->x;
					$bx = $r->x;
					$ay = $p->y;
					$by = $r->y;

					if (
						(self::cmp ($p->x, $a) <= 0 && self::cmp ($a, $r->x) <= 0)
					) {
						if (self::cmp ($b, $r->x) <= 0) {
							$ax = $a;
							$bx = $b;
							$ay = $p->y + ($a - $p->x) * $slope;
							$by = $p->y + ($b - $p->x) * $slope;
						}
						else {
							$ax = $a;
							$ay = $p->y + ($a - $p->x) * $slope;
							$by = $r->y;
						}
					}
					else
					if (
						(self::cmp ($p->x, $b) <= 0 && self::cmp ($b, $r->x) <= 0)
					) {
						$bx = $b;
						$ay = $p->y;
						$by = $p->y + ($b - $p->x) * $slope;
					}
					$i += ($_i = 0.5 * ($ay + $by) * ($bx - $ax));
					$X[] = $ax; $X[] = $bx;
					$Y[] = $ay; $Y[] = $by;

					unset ($p);
					$p = $r;
					unset ($r);
				}

				$min_X = min ($X);
				$max_X = max ($X);
				$min_Y = 0;
				$max_Y = min ($c, max ($Y));

				$area = $c * $c * $i;

				
				return (object) [
					'area' => $area,
					'min' => (object) [
						'X' => $min_X,
						'Y' => $min_Y,
					],
					'max' => (object) [
						'X' => $max_X,
						'Y' => $max_Y,
					],
					'centroid' => (object) [
						'X' => $min_X + self::div ($area, $max_Y - $min_Y),
						'Y' => $min_Y + self::div ($area, $max_X - $min_X),
					],
				];
				break;
			}
		}
	}

	public function has ($x) {
		$u = 0;
		$p = end ($this->P);
		if (self::cmp ($x, $p->x) > 0)
			return $p->y;
		$p = reset ($this->P);
		if (self::cmp ($x, $p->x) < 0)
			return $p->y;

		while (($r = next ($this->P)) !== false) {
			if (self::cmp ($x, $p->x) > 0 && self::cmp ($x, $r->x) <= 0) {
				$u = $p->y + ($x - $p->x) * self::div ($r->y - $p->y, $r->x - $p->x);
				break;
			}
			$p = $r;
		}
		return $u;
	}

	public static function cmp ($a, $b) {
		return $a == $b ? 0 : ($a < $b ? -1 : 1);
	}

	public static function div ($a, $b) {
		if (self::cmp (0, $b) == 0) return 0;
		return $a / $b;
	}

	private static function _normal_pdf ($x, $mu, $sigma) {
		$var = $sigma * $sigma;
		return 1.00 / (sqrt (2 * M_PI * $var) * exp (($x - $mu) * ($x - $mu) * 0.5 / $var));
	}

	/**
	 * $sigma_dev = 
	 * 	1: 68%
	 * 	2: 95%
	 * 	3: 99.7%
	 */
	public static function normal ($mu, $sigma, $points_no = 5, $sigma_dev = 3) {
		$factor = self::div (1.00, self::_normal_pdf ($mu, $mu, $sigma));
		$points = [];

		$min_x = $mu - $sigma_dev * $sigma;
		$max_x = $mu + $sigma_dev * $sigma;
		$interval_length = ($max_x - $min_x) / ($points_no - 1);

		$x = $min_x;
		for ($c = 0; $c < $points_no; $c++) {
			$points[] = (object) [
				'x'	=> $x,
				'y' => $factor * self::_normal_pdf ($x, $mu, $sigma),
			];
			$x += $interval_length;
		}

		return $points;
	}
}