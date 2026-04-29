<?php
defined('BASEPATH') or exit('No direct script access allowed');

$active_group = 'default';
$query_builder = true;

$db['default'] = [
	'dsn' => '',
	'hostname' => 'localhost',
	'database' => 'ponteved_app_demo',
	'username' => 'ponteved_app_usr',
	'password' => '0hn#xS*L#GYj',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => false,
	'db_debug' => false,
	'cache_on' => false,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => true,
	'compress' => true,
	'stricton' => false,
	'failover' => [],
	'save_queries' => true,
];
