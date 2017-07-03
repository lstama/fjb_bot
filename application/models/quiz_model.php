<?php
class quiz_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_quiz($username = false) {

		if ($username === false) {

			$query = $this->db->get('dota_quiz');
			return $query->result_array();
		}

		$query = $this->db->get_where('dota_quiz', array('username' => $username));
		return $query->row_array();
	}

	public function create_quiz($data = null){

		#$data['last_change'] = time();
		return $this->db->insert('dota_quiz', $data); //boolean
	}

  	public function update_quiz($user, $data){

      	$this->db->where('username',$user);
      	#$data['last_change'] = time();
      	return $this->db->update('dota_quiz', $data); //boolean
  }

  	public function delete_quiz($username = null) {

  		return $this->db->where('username', $username)->delete('dota_quiz');
  	}
}