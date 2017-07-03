<?php
class dota_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_dota($username = false) {

		if ($username === false) {

			$query = $this->db->get('dota_skill');
			return $query->result_array();
		}

		$query = $this->db->get_where('dota_skill', array('id' => $username));
		return $query->row_array();
	}

}