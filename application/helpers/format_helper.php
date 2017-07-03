<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('button'))
{
    function button($reply, $label, $show = 'all') {

    	$b['reply'] = $reply;
    	$b['text']  = $label;
    	$b['show']	= $show;
    	$b['client']= 'OTHER';

    	return $b;
	}   
}

if ( ! function_exists('interactive'))
{
    function interactive($image = null, $title = null, $caption = null, $buttons = null, $placeholder = null) {

    	if ($image != null)       $i['image']          = $image;
    	if ($title != null)       $i['title']          = $title;
    	if ($caption != null)     $i['caption']        = $caption;
    	if ($buttons != null)     $i['buttons']        = $buttons;
    	if ($placeholder != null) $i['placeholder']    = $placeholder;

    	return $i;
	}   
}