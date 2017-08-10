<?php

class FJB_Old {

	/**
	 * @var array
	 * Location yang hanya digunakan untuk asal barang (masih mengikuti old kaskus, sudah tidak akan diupdate lagi).
	 * JANGAN GUNAKAN UNTUK ALAMAT TUJUAN PENGIRIMAN
	 * UNTUK ALAMAT PENGIRIMAN GUNAKAN API KASKUS YANG BARU
	 */
	public $location;
	public $condition;

	public function __construct() {

		$this->location = array(17 => 'Bali', 16 => 'Banten', 7 => 'Bengkulu', 14 => 'Daerah Istimewa Yogyakarta', 11 => 'DKI Jakarta', 28 => 'Gorontalo', 5 => 'Jambi', 12 => 'Jawa Barat', 13 => 'Jawa Tengah', 15 => 'Jawa Timur', 20 => 'Kalimantan Barat', 22 => 'Kalimantan Selatan', 21 => 'Kalimantan Tengah', 23 => 'Kalimantan Timur', 34 => 'Kalimantan Utara', 9 => 'Kepulauan Bangka Belitung', 10 => 'Kepulauan Riau', 8 => 'Lampung', 30 => 'Maluku', 31 => 'Maluku Utara', 33 => 'N/A', 1 => 'Nanggroe Aceh Darussalam', 18 => 'Nusa Tenggara Barat', 19 => 'Nusa Tenggara Timur', 32 => 'Papua', 35 => 'Papua Barat', 4 => 'Riau', 24 => 'Sulawasi Utara', 29 => 'Sulawesi Barat', 26 => 'Sulawesi Selatan', 25 => 'Sulawesi Tengah', 27 => 'Sulawesi Tenggara', 3 => 'Sumatera Barat', 6 => 'Sumatera Selatan', 2 => 'Sumatera Utara');

		$this->condition = array(1 => 'New', 2 => 'Second', 3 => 'Refurbished');
	}
}
