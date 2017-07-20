<?php

include_once 'Features.php';
include_once 'FJB_Old.php';

class FJB extends Features {


	public $fjb_old;

	public function __construct() {

		$this->fjb_old = new FJB_Old;
	}

	public function getProvinceNameFromOldKaskus($id) {

		return $this->fjb_old->location[$id];
	}

	public function getItemConditionName($id) {

		return $this->fjb_old->condition[$id];
	}

	public function getProvinceName($id) {

		$response = $this->get('v1/fjb/location/provinces/' . $id);
		if (! $response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(),$response->content['name']);
	}

	public function getCityName($id) {

		$response = $this->get('v1/fjb/location/cities/' . $id);
		if (! $response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(),$response->content['name']);
	}

	public function getAreaName($id) {

		$response = $this->get('v1/fjb/location/areas/' . $id);
		if (! $response->isSuccess()) {

			return $response;
		}

		return new Request_Result($response->getSuccess(),$response->content['name']);
	}
}