<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 *
 * Model Proposito_General_model
 *
 * This Model for ...
 *
 * @package		CodeIgniter
 * @category	Model
 * @author    Setiawan Jodi <jodisetiawan@fisip-untirta.ac.id>
 * @link      https://github.com/setdjod/myci-extension/
 * @param     ...
 * @return    ...
 *
 */

class Proposito_General_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function login_imagenes()
	{
		return $this->db
			->select(
				'opcion,
          IFNULL(valor, "") valor'
			)
			->where_in('opcion', ['login_logo', 'logo_dashboard', 'login_background'])
			->get('opciones_generales')
			->result_array();
	}

	public function condominio_default()
	{
		$response = $this->db->get_where('opciones_generales', ['opcion' => 'id_condominio_default'])->row();
		return !empty($response) ? intval($response->valor) : 0;
	}

	public function respaldar_db()
	{
		// Load the DB utility class
		$this->load->dbutil();

		// Backup your entire database and assign it to a variable
		$backup = $this->dbutil->backup();

		// Load the file helper and write the file to your server
		$this->load->helper('file');
		write_file('downloads/mybackup.gz', $backup);

		// Load the download helper and send the file to your desktop
		$this->load->helper('download');
		force_download('mybackup.gz', $backup);
	}
}

/* End of file Proposito_General_model.php */
/* Location: ./application/models/Proposito_General_model.php */
