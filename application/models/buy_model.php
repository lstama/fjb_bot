<?php
class Buy_model extends CI_Model {

	public function __construct() {

		parent::__construct();
		$this->load->database();
	}

	public function find_buy($user = false) {

		if ($user === false) {

			$query = $this->db->get('buy');
			return $query->result_array();
		}

		$query = $this->db->get_where('buy', array('User_Account' => $user));
		return $query->row_array();
	}

	public function create_buy($data = null){

		return $this->db->insert('buy', $data); //boolean
	}

  	public function update_buy($user, $data){

      	$this->db->where('User_Account',$user);
      	return $this->db->update('buy', $data); //boolean
  }

  	public function delete_buy($user = null) {

  		return $this->db->where('User_Account', $user)->delete('buy');
  	}
}