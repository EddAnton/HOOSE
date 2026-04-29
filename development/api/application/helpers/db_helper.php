<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('validar_campos')) {
	function validar_campos($tabla, $data)
	{
		$CI = &get_instance();
		$CI->load->database();

		foreach ($data as $nombre => $valor) {
			if (!$CI->db->field_exists($nombre, $tabla)) {
				// echo $nombre . PHP_EOL;
				return false;
			}
		}
		return true;
	}
}
