<?php
class Buy_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_buy($user = false) {

		if ($user === false) {

			$query = $this->db->get('Buy_Start');
			return $query->result_array();
		}

		$query = $this->db->get_where('Buy_Start', array('user' => $user));
		return $query->row_array();
	}

	public function create_buy($data = null){

		return $this->db->insert('Buy_Start', $data); //boolean
	}

  	public function update_buy($user, $data){

      	$this->db->where('user',$user);
      	return $this->db->update('Buy_Start', $data); //boolean
  }

  	public function delete_buy($user = null) {

  		return $this->db->where('user', $user)->delete('Buy_Start');
  	}
}