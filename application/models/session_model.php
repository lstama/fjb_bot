<?php
class Session_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_session($username = false) {

		if ($username === false) {

			$query = $this->db->get('sessions');
			return $query->result_array();
		}

		$query = $this->db->get_where('sessions', array('username' => $username));
		return $query->row_array();
	}

	public function find_token($token = false) {

		if ($token === false) {

			$query = $this->db->get('sessions');
			return $query->result_array();
		}

		$query = $this->db->get_where('sessions', array('token' => $token));
		return $query->row_array();
	}

	public function create_session($data = null){

		$data['last_change'] = time();
		return $this->db->insert('sessions', $data); //boolean
	}

  	public function update_session($user, $data){

      	$this->db->where('username',$user);
      	$data['last_change'] = time();
      	return $this->db->update('sessions', $data); //boolean
  }

  	public function delete_session($username = null) {

  		return $this->db->where('username', $username)->delete('sessions');
  	}
}