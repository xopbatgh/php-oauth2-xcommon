<?php
//print '<pre>' . print_r($_CFG, true) . '</pre>';
include('../config.php');
header('Content-Type: text/html; charset=utf-8');


class authClass {
	private $redirect_uri = ''; // For API
	
	public function showError($error_text){
		Global $_CFG ;
		
		redirect('http://' . $_CFG['domain'] . '#authMessage=' . $error_text);
	}
	
	
    public function __construct() {
		Global $_CFG ;
		
		$this->redirect_uri = 'https://' . $_CFG['domain'] . '/auth/ok.php';

		if (isset($_GET['code']) AND $_GET['state'] == 'okAuth'){
			$this->doAuthOrRegister();
		}
		else {
			$this->doRedirectToOk();
		}
    }
	
	private function doRedirectToOk(){
		Global $_CFG ;
		
		$destUrl = 'https://connect.ok.ru/oauth/authorize?';
		$params = array(
			'response_type' => 'code' ,
			'client_id' => $_CFG['socialAuth']['ok']['appid'],
			'redirect_uri' => $this->redirect_uri,
			'state' => 'okAuth',
			'scope' => '',
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
		
		$okReply = $this->getCurrentUser($real_token);

		$newUser['email'] = $real_token['email'];
		$newUser['reg_type'] = 'ok';
		$newUser['social_id'] = $okReply['uid'] ;
		$newUser['details'] = $okReply ;
		
		include($_CFG['root'] . 'auth/authBrain.php');
		
	}
	
    private static function getCurrentUser($real_token) { 
		Global $_CFG ;
		
        $url = 'http://api.odnoklassniki.ru/fb.do'. 
            '?access_token=' . $real_token['access_token'] . 
            '&method=users.getCurrentUser' . 
            '&application_key=' . $_CFG['socialAuth']['ok']['publicKey'] . 
            '&sig=' . md5('application_key=' . $_CFG['socialAuth']['ok']['publicKey'] . 'method=users.getCurrentUser' . md5($real_token['access_token'] . $_CFG['socialAuth']['ok']['secretKey'])); 
  
        if (!($response = @file_get_contents($url))) { 
            return false; 
        } 
  
        $user = json_decode($response, true); 
  
        if (empty($user)) { 
            return false; 
        } 
  
		return $user ;
   } 
	
	private function exchangeOauthToken(){
		Global $_CFG ;
		
		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://api.ok.ru/oauth/token.do');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
			
			curl_setopt($curl, CURLOPT_POSTFIELDS, "code=" . urldecode($_GET['code']) . "&client_id=" . $_CFG['socialAuth']['ok']['appid'] . "&client_secret=" . $_CFG['socialAuth']['ok']['secretKey'] . "&redirect_uri=" . $this->redirect_uri . "&grant_type=authorization_code");
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