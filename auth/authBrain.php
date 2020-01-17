<?php
/*
 * Runs if Oauth 2 was succesful
 * In $newUser fields reg_type, social_id, email are always present
 */

if (!isset($newUser['details']))
	$newUser['details'] = array();

print '<pre>' . print_r($newUser, true) . '</pre>';
exit();



?>