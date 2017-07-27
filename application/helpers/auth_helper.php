<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$data = [
	'id' => (string)$item['_id'],
	'item' => [
		'id' => (string)$item['thread_id'],
		'title' => (string)$item['thread_title'],
		'image' => (string)$threadImage,
		'discounted_price' => (int)discount_price((int)$item['item_price'], (int)$item['item_discount']),
		'is_instant_purchase' => !empty($item['instant_purchase']) ? (boolean) $item['instant_purchase'] : false
	],
	'seller' => [
		'id' => (string)$item['seller_id'],
		'username' => (string)$item['seller_username'],
		'avatar' => (string)$sellerProfpict,
		'is_vsl' => $sellerDetail['is_vsl'],
		'is_donatur' => $sellerDetail['is_donatur']
	],
	'quantity' => (int)$item['quantity'],
	'type' => (int)$item['type']
];
if (!empty($item['offer_id'])) {
	$data['offer'] = [
		'id' => (string)$item['offer_id'],
		'price' => (int)$item['offer_price']
	];
	$data['shipping'] = [
		'cost' => (int)$item['shipping_cost'],
		'insurance_cost' => (int)$item['insurance_cost']
	];
}
}