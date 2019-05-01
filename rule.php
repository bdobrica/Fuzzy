<?php
namespace Fuzzy;

class Rule {
	const KEYWORD	= 1;
	const OPERATOR	= 2;
	const LBRACKET	= 3;
	const RBRACKET	= 4;
	const VARS	= 5;

	private static $OPERATORS = [
		'is'	=> [ ['variable', 'fuzzy_set'], ['bool'] ],
		'are'	=> [ ['variable', 'fuzzy_set'], ['bool'] ],
		'not'	=> [ ['bool'], ['bool'] ],
		'and'	=> [ ['bool', 'bool'], ['bool'] ],
		'xor'	=> [ ['bool', 'bool'], ['bool'] ],
		'or'	=> [ ['bool', 'bool'], ['bool'] ],
		];
	private static $KEYWORDS = [
		'if',
		'then',
		'else'
		];
	private static $LBRACKETS = [ '(' ];
	private static $RBRACKETS = [ ')' ];
	private static $WHITESPACES = [ ' ', "\t", "\n", "\r" ];

	private $S;

	public function __construct ($rule) {
		$this->S = [];
		$K = [];
		$S = [];
		$T = [];

		while ($type = self::token ($rule, $token)) {
			switch ($type) {
			case self::KEYWORD:
				while (!empty ($T) && in_array (end ($T), self::$LBRACKETS))
					array_pop ($T);
				if (!empty ($T))
					$S[] = array_pop ($T);
				if (!empty ($S) && !empty ($K)) {
					$this->S[array_pop ($K)] = $S;
					$S = [];
				}
				$K[] = $token;
				break;
			case self::VARS:
				$S[] = $token;
				break;
			case self::LBRACKET:
				$T[] = $token;
				break;
			case self::OPERATOR:
				$token = (object) array ('token' => $token, 'signature' => self::$OPERATORS[$token]);
				if (empty ($T)) {
					$T[] = $token;
				}
				else {
					while (self::cmp (end ($T), $token)) {
						$S[] = array_pop ($T);
						}
					$T[] = $token;
				}
				break;
			case self::RBRACKET:
				while (!empty ($T) && !in_array (end ($T), self::$LBRACKETS))
					$S[] = array_pop ($T);
				if (!empty ($T))
					array_pop ($T);
				break;
			}
		}
		while (!empty ($T) && in_array (end ($T), self::$LBRACKETS))
			array_pop ($T);
		if (!empty ($T))
			$S[] = array_pop ($T);
		if (!empty ($K) && !empty ($S))
			$this->S[array_pop($K)] = $S;
	}

	public function get ($key = null, $opts = null) {
		var_dump ($this->S);
	}

	public function eval ($vars) {
		$T = [];
		foreach ($this->S['if'] as $token) {
			if (!is_object ($token)) {
				$T[] = $token;
				continue;
			}

			switch ($token->token) {
				case 'is':
				case 'are':
					$set = array_pop ($T);
					$var = array_pop ($T);

					if (!isset ($vars[$var]) || !isset ($vars[$var]->sets[$set]))
						throw new Exception ();
					
					$bool = $vars[$var]->sets[$set]->has ($vars[$var]->crisp);

					$T[] = $bool;
					break;
				case 'not':
					$bool = array_pop ($T);
					$T[] = 1 - $bool;
					break;
				case 'and':
					$bool_b = array_pop ($T);
					$bool_a = array_pop ($T);
					$T[] = min ($bool_a, $bool_b);
					break;
				case 'or':
					$bool_b = array_pop ($T);
					$bool_a = array_pop ($T);
					$T[] = max ($bool_a, $bool_b);
					break;
				case 'xor':
					$bool_b = array_pop ($T);
					$bool_a = array_pop ($T);
					$T[] = max (min ($bool_a, 1-$bool_b), min (1 - $bool_a, $bool_b));
					break;
			}
		}

		$bool = array_pop ($T);
		
		$outputs = [];

		$T = [];
		if (!empty ($this->S['then']))
		foreach ($this->S['then'] as $token) {
			if (!is_object ($token)) {
				$T[] = $token;
				continue;
			}
			switch ($token->token) {
				case 'is':
				case 'are':
					$B = array_pop ($T);
					$A = array_pop ($T);

					if (!isset ($outputs[$A]))
						$outputs[$A] = [];
					
					if (!isset ($outputs[$A][$B]))
						$outputs[$A][$B] = [];
					
					$outputs[$A][$B][] = $bool;
					break;
			}
		}

		$bool = 1 - $bool;
		$T = [];
		if (!empty ($this->S['else']))
		foreach ($this->S['else'] as $token) {
			if (!is_object ($token)) {
				$T[] = $token;
				continue;
			}
			switch ($token->token) {
				case 'is':
				case 'are':
					$B = array_pop ($T);
					$A = array_pop ($T);

					if (!isset ($outputs[$A]))
						$outputs[$A] = [];
					
					if (!isset ($outputs[$A][$B]))
						$outputs[$A][$B] = [];
					
					$outputs[$A][$B][] = $bool;
					break;
			}
		}

		return $outputs;
	}

	private static function token (&$string, &$token) {
		$string = trim ($string, implode ('', self::$WHITESPACES));
		$separators = array_merge (self::$LBRACKETS, self::$RBRACKETS, self::$WHITESPACES);

		if (strlen ($string) == 0) {
			return null;
		}

		$token = '';

		if (in_array ($string[0], self::$LBRACKETS)) {
			$token = $string[0];
			$string = substr ($string, 1);
			$string = trim ($string, implode ('', self::$WHITESPACES));
			return self::LBRACKET;
		}

		if (in_array ($string[0], self::$RBRACKETS)) {
			$token = $string[0];
			$string = substr ($string, 1);
			$string = trim ($string, implode ('', self::$WHITESPACES));
			return self::RBRACKET;
		}

		while (strlen ($string) && !in_array ($string[0], $separators)) {
			$token .= $string[0];
			$string = substr ($string, 1);
		}

		$string = trim ($string, implode ('', self::$WHITESPACES));

		if (in_array ($token, self::$KEYWORDS))
			return self::KEYWORD;
		if (in_array ($token, array_keys (self::$OPERATORS)))
			return self::OPERATOR;
		if (!empty ($token))
			return self::VARS;

		return 0;
	}

	private static function cmp ($opA, $opB) {
		$operators = array_keys (self::$OPERATORS);
		return array_search ($opA, $operators) > array_search ($opB, $operators) ? 1 : 0;
	}
}
