<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo APP_FULLNAME . ' (' . APP_NAME . ')'; ?> - API</title>

	<style type="text/css">

	::selection { background-color: #E13300; color: white; }
	::-moz-selection { background-color: #E13300; color: white; }

	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Arial, Helvetica, sans-serif;
		color: #4F5155;
	}

	h1 {
		color: white;
		background-color: #444;
		border-bottom: 1px solid #D0D0D0;
		font-size: 22px;
		font-weight: normal;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	#body {
		margin: 0 15px 0 15px;
	}

	p.footer {
		text-align: right;
		font-size: 11px;
		border-top: 1px solid #D0D0D0;
		line-height: 32px;
		padding: 0 10px 0 10px;
		margin: 20px 0 0 0;
	}

	#container {
		margin: 10px;
		border: 1px solid #D0D0D0;
		box-shadow: 0 0 8px #D0D0D0;
	}
	</style>
</head>
<body>

<div id="container">
	<h1><?php echo APP_FULLNAME . ' (' . APP_NAME . ')'; ?> - API</h1>

	<div id="body">
		<p>Entorno de ejecución: <strong><?php echo ENVIRONMENT; ?></strong></p> 
		<p>MySQL Server v<strong><?php echo $MySQL_version; ?></strong></p> 
	</div>

	<p class="footer">Página renderizada en <strong>{elapsed_time}</strong> segundos. <?php echo ENVIRONMENT ===
 'development'
 	? '<br />CodeIgniter Version <strong>' . CI_VERSION . '</strong>'
 	: ''; ?></p>
</div>

</body>
</html>