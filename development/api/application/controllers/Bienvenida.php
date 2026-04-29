<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bienvenida extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$data['MySQL_version'] = $this->db->version();

		$this->load->view('Bienvenida', $data);
	}
}
