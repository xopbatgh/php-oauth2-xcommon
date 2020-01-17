<?php
session_start();

// Find out the root directory for site
$_CFG['root'] = dirname(realpath(__FILE__)) . '/' ;

$_CFG['domain'] = 'example.com';

$_CFG['socialAuth'] = array(
    'google' => array(
        'clientSecret' => '',
        'clientId' => '',
    ),
    'ok' => array(
        'appid' => '',
        'publicKey' => '',
        'secretKey' => '',
    ),
    'vk' => array(
        'clientSecret' => '',
        'clientId' => '',
    ),
    'twitter' => array(
        'consumerKey' => '',
        'consumerSecret' => '',
        'accessToken' => '',
        'accessTokenSecret' => '',
    ),
    'facebook' => array(
        'appId' => '',
        'appSecret' => '',
    ),
);

function redirect($url, $timeout = 0, $params = []){

    print "
	<script type='text/javascript'>
	
		setTimeout(function(){
		    document.location.href = '" . $url . "';
		}, " . ($timeout * 1000) . ");	
		
	</script>";
    exit();
}

?>