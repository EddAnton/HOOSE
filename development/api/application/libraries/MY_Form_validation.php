<?php

class MY_Form_validation extends CI_Form_validation
{
	function __construct($reglas = [])
	{
		parent::__construct($reglas);
		$this->ci = &get_instance();
		$this->ci->load->helper('Utils');
	}

	public function get_reglas()
	{
		return $this->_config_rules;
	}

	public function get_errores_arreglo()
	{
		return $this->_error_array;
	}

	public function get_campos($form_data)
	{
		$nombres_campos = [];

		$reglas = $this->get_reglas();
		$reglas = $reglas[$form_data];

		foreach ($reglas as $i => $info) {
			$nombres_campos[] = $info['field'];
		}

		return $nombres_campos;
	}

	public function fechaFormatoValido($fecha)
	{
		return fechaFormatoValido($fecha);
	}
	public function horaFormatoValido($hora, $formato = null)
	{
		return empty($formato) ? horaFormatoValido($hora) : horaFormatoValido($hora, $formato);
	}

	function tamanioMinimoArreglo($array, $tamanio)
	{
		return tamanioMinimoArreglo($array, $tamanio);
	}

	function tamanioMaximoArreglo($array, $tamanio)
	{
		return tamanioMaximoArreglo($array, $tamanio);
	}

	function md5Valido($md5Hash)
	{
		return md5Valido($md5Hash);
	}

	protected function _execute($row, $rules, $postdata = null, $cycles = 0)
	{
		// Lets check if a min or max selction rule has been set
		$validate_custom_rule = false;

		foreach ($rules as $key => $rule) {
			if (strpos($rule, 'tamanioMinimoArreglo') !== false or strpos($rule, 'tamanioMaximoArreglo') !== false) {
				$validate_custom_rule = true;
				unset($rules[$key]);
				break;
			}
		}

		// Found a min or max selection rule
		if ($validate_custom_rule) {
			// Set the method rule and param
			$param = false;
			if (preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
				$rule = $match[1];
				$param = $match[2];
			}
			// When empty of not an array, convert
			if (empty($postdata) or !is_array($postdata)) {
				$postdata = [$postdata];
			}

			// Run the rule
			$result = $this->$rule($postdata, $param);
			// Did the rule test negatively? If so, grab the error.
			if (!$result) {
				$line = $this->_get_error_message($rule, $row['field']);

				// Build the error message
				$message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']), $param);

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message . PHP_EOL;

				if (!isset($this->_error_array[$row['field']])) {
					$this->_error_array[$row['field']] = $message;
				}
			}
		}

		// Validate the rest of the rules as normal
		parent::_execute($row, $rules, $postdata, $cycles);
	}
}
