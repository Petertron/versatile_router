<?php

namespace versatile_router; // namespace to confine functions

class Router {
	public static $vars;
	public static $method;
	public static $page;

	// Run the router

	public static function run() {
		self::$vars = array_merge($_SERVER, $_REQUEST);
		self::$method = self::$vars['REQUEST_METHOD'];
		self::$page = self::$vars['symphony-page'];

		// Condition functions

		function when_set($var) {
			return array_key_exists(Router::$vars[$var], $var);
		}

		function when_not_set($var) {
			return !array_key_exists(Router::$vars[$var], $var);
		}

		function when_is($var) {
			return isset(Router::$vars[$var]);
		}

		function when_not($var) {
			return !isset(Router::$vars[$var]);
		}

		function when_equal($var, $value) {
			return Router::$vars[$var] == $value;
		}

		function when_not_equal($var, $value) {
			return !(Router::$vars[$var] == $value);
		}

		function when_match($var, $regexp) {
			if($var[0] == ":") {
				return (object) array('name' => $var, 'regexp' => $regexp);
			}
			else {
				return (boolean) preg_match($regexp, Router::$vars[$var]);
			}
		}

		// Routing functions

		function route($from, $to, $conditions = null) {
			Router::tryRoute($from, $to, $conditions);
		}

		function get($from, $to, $conditions = null) {
			if(Router::$method == 'GET') Router::tryRoute($from, $to, $conditions);
		}

		function post($from, $to, $conditions = null) {
			if(Router::$method == 'POST') Router::tryRoute($from, $to, $conditions);
		}

		function redirect($from, $to, $conditions = null, $status = true) {
			Router::tryRoute($from, $to, $conditions, $status);
		}

		function group($conditions = false, $group = null) {
			if(!($conditions === true or is_array($conditions))) return;
			if(!is_object($group)) return;

			if(is_array($conditions)) {
				foreach($conditions as $cond) {
					if($cond !== true) return;
				}
			}

			// Execute group
			$group();
		}

		// Perform routing

		$result = false;

		try {
			// Include routes.php
			include WORKSPACE . '/' . trim(\Symphony::Configuration()->get('routes_file_path', 'versatile_router'), '/');
		}
		catch(\Exception $exception) {
			if($exception->getCode() == 1) {
				$result = $exception->getMessage();
			}
		}

		return $result;
	}

	// Test a route

	public static function tryRoute($from, $to, $conditions = null, $status = null) {
		// Conditions
		$params = array();
		if($conditions !== null and $conditions !== true) {
			if(is_object($conditions)) {
				$params[$conditions->name] = $conditions->value;
			}
			elseif(is_array($conditions)) {
				foreach($conditions as $cond) {
					if($cond !== true) {
						if(is_object($cond)) {
							$params[$cond->name] = trim($cond->value, '()');
						}
						else return;
					}
				}
			}
			else return;
		}
		if($from != '') $from = '/' . trim($from, '/') . '/';
		$to = ($status ? '' : '/') . trim($to, '/') . '/';

		if(preg_match_all('/(:[\w-]+)/u', $from, $matches)) {
			foreach($matches[0] as $index => $name) {
				$regexp = isset($params[$name]) ? $params[$name] : '[\w\-]+';
				$from = str_replace($name, '(?P<' . substr($name, 1) . '>' . $regexp . ')', $from);
			}
		}

		$from = str_replace('/', '\/', $from);
		$from = str_replace('*', '([^\/\.]+)', $from); // replace asterisks with wildcard regexp
		$from = '/^' . $from . '$/u';

		if(!preg_match($from, self::$page, $matches)) return;

		// Match made
		// Store any parameter values in $vars array
		$vars = self::$vars;
		foreach($matches as $name => $value) {
			if(is_string($name)) $vars[':' . $name] = $value;
		}

		// Prepare destination string
		$to = preg_replace_callback(
			'/\{\:?\w+\}/u',
			function($match) use($vars){
				return $vars[trim($match[0], '{}')];
			},
			$to
		);

		if(is_int($status) or is_string($status)) {
			// Redirect with status code
			header('Location:' . $to, true, $status);
			exit;
		}
		else if($status) {
			// Redirect with unspecified status
			header('Location:' . $to);
			exit;
		}
		else {
			// Reroute
			throw new \Exception($to, 1);
		}
	}
}
