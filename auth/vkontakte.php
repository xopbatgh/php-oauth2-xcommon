<?php
include('../config.php');
header('Content-Type: text/html; charset=utf-8');

class authClass {
	private $redirect_uri = ''; // For API
	
	public function showError($error_text){
		Global $_CFG ;
		
		$error_text = urlencode($error_text);
		
		redirect('http://' . $_CFG['domain'] . '#authMessage=' . $error_text);
	}
	
	
    public function __construct() {
		Global $_CFG ;
		
		$this->redirect_uri = 'http://' . $_CFG['domain'] . '/auth/vkontakte.php';

		if (isset($_GET['code']) AND $_GET['state'] == 'vkAuth'){
			$this->doAuthOrRegister();
		}
		else {
			$this->doRedirectToVK();
		}
    }
	
	private function doRedirectToVK(){
		Global $_CFG ;
		
		$destUrl = 'https://oauth.vk.com/authorize?';
		$params = array(
			'response_type' => 'code' ,
			'client_id' => $_CFG['socialAuth']['vk']['clientId'],
			'redirect_uri' => $this->redirect_uri,
			'state' => 'vkAuth',
			'scope' => 'email',
		);

		foreach ($params as $_key => $_value)
			$destUrl .= $_key . '=' . $_value . '&';
			
		header('Location: ' . substr($destUrl, 0, -1));
	
	}
	
	private function doAuthOrRegister(){
		Global $_CFG, $_USER ;
		
		$real_token = $this->exchangeOauthToken();
		
		if (!is_array($real_token) OR !isset($real_token['access_token'])){
			$this->showError('Authorization error (STEP 1)');
			return ;
		}

		//print 'Email is: ' ;
		//print $real_token['email'];
		//exit();
		
		$newUser['email'] = $real_token['email'];
		$_GET['vkId'] = $real_token['user_id'];
		$newUser['reg_type'] = 'vk';
		$newUser['social_id'] = $_GET['vkId'] ;

		include($_CFG['root'] . 'auth/authBrain.php');
		
	}
	
	private function exchangeOauthToken(){
		Global $_CFG ;
		
		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://oauth.vk.com/access_token');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
			
			curl_setopt($curl, CURLOPT_POSTFIELDS, "code=" . urldecode($_GET['code']) . "&client_id=" . $_CFG['socialAuth']['vk']['clientId'] . "&client_secret=" . $_CFG['socialAuth']['vk']['clientSecret'] . "&redirect_uri=" . $this->redirect_uri);
			$out = curl_exec($curl);
			curl_close($curl);

			$out = json_decode($out, true); 

			return $out ;
			
		}
		else {
			print 'Cannot initialize required modules' ;
			exit();	
		}


	}
}

$aux = new authClass;
	



?>