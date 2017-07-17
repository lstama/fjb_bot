<?php
class Create_alamat_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_create_alamat($user = false) {

		if ($user === false) {

			$query = $this->db->get('create_alamat');
			return $query->result_array();
		}

		$query = $this->db->get_where('create_alamat', array('User_Account' => $user));
		return $query->row_array();
	}

	public function create_create_alamat($data = null){

		return $this->db->insert('create_alamat', $data); //boolean
	}

  	public function update_create_alamat($user, $data){

      	$this->db->where('User_Account',$user);
      	return $this->db->update('create_alamat', $data); //boolean
  }

  	public function delete_create_alamat($user = null) {

  		return $this->db->where('User_Account', $user)->delete('create_alamat');
  	}
}