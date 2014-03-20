<?php

namespace versatile_router;

define('versatile_router\when_present', 'pr1_');
define('versatile_router\when_not_present', 'pr0_');
define('versatile_router\when_true', 'tr1_');
define('versatile_router\when_not_true', 'tr0_');
define('versatile_router\when_number', 'nu1_');
define('versatile_router\when_not_number', 'nu0_');
define('versatile_router\when_equal', 'eq1%');
define('versatile_router\when_not_equal', 'eq0%');
define('versatile_router\when_match', 'ma1%');
define('versatile_router\when_no_match', 'ma0%');

$param_pool = array();

class Router {
	public static $vars;
	public static $method;
	public static $page;
	public static $param_pool;

	public static function getParams() {
		return self::$param_pool;
	}

	/*
	* Run the router
	*/
	public static function run() {
		self::$vars = array_merge($_SERVER, $_REQUEST);
		self::$method = self::$vars['REQUEST_METHOD'];
		self::$page = self::$vars['symphony-page'];
		self::$param_pool = array();

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

		function group($group = null, $conditions = null) {
			if(!is_object($group)) return;
			if(is_array($conditions)) {
				if(!Router::testConditions($conditions)) return false;
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
				$route_to = $exception->getMessage();
			}
		}

		return array('route_to' => $route_to, 'params' => self::$param_pool);
	}

	/*
	* Test a route
	*/
	public static function tryRoute($from, $to, $conditions = null, $status = null) {
		if($from != '') $from = '/' . trim($from, '/') . '/';
		
		// If not redirecting, make sure $to has leading and trailing slashes
		if(!$status) $to = '/' . trim($to, '/') . '/';

		if(preg_match_all('/(:\w+)/', $from, $matches)) {
			foreach($matches[0] as $index => $name) {
				$from = str_replace($name, '(?P<' . substr($name, 1) . '>[\w\-:;@~!\[\](){}]+)', $from);
			}
		}

		$from = str_replace('/', '\/', $from);
		$from = str_replace('*', '([^\/\.]+)', $from); // replace asterisks with wildcard regexp
		$from = '/^' . $from . '$/';
		if(!preg_match($from, self::$page, $matches)) return;

		// * Match made *

		// Store any parameter values in $vars array
		foreach($matches as $name => $value) {
			if(is_string($name)) {
				self::$vars[':' . $name] = $value;
			}
		}

		// Test conditions
		if(is_array($conditions)){
			if(!self::testConditions($conditions)) return false;
		}

		// * Prepare destination string *

		if(preg_match('/\[[^\]]*\]/', $to, $matches) == 1) {
			$match = $matches[0];
			$to = str_replace($match, '', $to);
			preg_match_all('/:\w+/', $match, $matches);
			if(is_array($matches[0]) and !empty($matches[0])) {
				foreach($matches[0] as $param_name) {
					self::$param_pool[$param_name] = self::$vars[$param_name];
				}
			}
		}

		$to = preg_replace_callback(
			'/\{\:?[\w\-]+\}/',
			function($match) use($vars){
				return self::$vars[trim($match[0], '{}')];
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

	/*
	* Test conditions
	*/
	static function testConditions($conditions){
		$success = true;
		foreach($conditions as $condition){
			$test = substr($condition, 0, 3);
			$var_name = substr($condition, 4);
			if($condition[3] == '%') {
				$split_point = strpos($var_name, ' ');
				$test_arg = substr($var_name, $split_point + 1);
				$var_name = substr($var_name, 0, $split_point);
			}
			switch($test) {
				case 'pr1':
					$success = (boolean) array_key_exists($var_name, self::$vars);
					break;
				case 'pr0':
					$success = (boolean) !array_key_exists($var_name, self::$vars);
					break;
				case 'tr1':
					$success = isset(self::$vars[$var_name]);
					break;
				case 'tr0':
					$success = !isset(self::$vars[$var_name]);
					break;
				case 'nu1':
					$success = is_numeric(self::$vars[$var_name]);
					break;
				case 'nu0':
					$success = !is_numeric(self::$vars[$var_name]);
					break;
				case 'eq1':
					$success = (self::$vars[$var_name] == $test_arg);
					break;
				case 'eq0':
					$success = !(self::$vars[$var_name] == $test_arg);
					break;
				case 'ma1':
					$success = (boolean) preg_match($test_arg, self::$vars[$var_name]);
					break;
				case 'ma0':
					$success = (boolean) !preg_match($test_arg, self::$vars[$var_name]);
					break;
			}
			if(!$success) break;
		}
		return $success;
	}

}
