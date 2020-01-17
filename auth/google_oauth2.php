<?php
//print '<pre>' . print_r($_CFG, true) . '</pre>';
include_once('../config.php');
header('Content-Type: text/html; charset=utf-8');

class authClass {
	private $redirect_uri = ''; // For API
	
	public function showError($error_text){
		Global $_CFG ;
		
		redirect('https://' . $_CFG['domain'] . '#authMessage=' . $error_text);
	}
	
	
    public function __construct() {
		Global $_CFG ;
		
		$this->redirect_uri = 'https://' . $_CFG['domain'] . '/auth/google_oauth2.php';

		if (isset($_GET['code']) AND $_GET['state'] == 'googleAuth'){
			$this->doAuthOrRegister();
		}
		else {
			$this->doRedirectToGoogle();
		}
    }
	
	private function doRedirectToGoogle(){
		Global $_CFG ;
		
		$destUrl = 'https://accounts.google.com/o/oauth2/auth?';
		$params = array(
			'response_type' => 'code' ,
			'client_id' => $_CFG['socialAuth']['google']['clientId'],
			'redirect_uri' => $this->redirect_uri,
			'state' => 'googleAuth',
			'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
			'access_type' => 'offline',
		);

		foreach ($params as $_key => $_value)
			$destUrl .= $_key . '=' . $_value . '&';


		header('Location: ' . substr($destUrl, 0, -1));
	
	}

	private function getUserInfo($access_token){

        $q = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token;
        $json = file_get_contents($q);
        $userInfoArray = json_decode($json,true);

        return $userInfoArray;

    }
	
	private function doAuthOrRegister(){
		Global $_CFG, $_USER ;
		
		$real_token = $this->exchangeOauthToken();
		
		//print '<pre>' . print_r($real_token, true) . '</pre>';
		
		if (!is_array($real_token) OR !isset($real_token['access_token'])){
			$this->showError('Authorization error (STEP 1)');
			return ;
		}

		$gReply = array();
		list($gReply['rs256'], $gReply['userInfo'], $gReply['sig']) = explode('.', $real_token['id_token']);
		$gReply['userInfo'] = json_decode(base64_decode($gReply['userInfo']), true);
		$gReply['rs256'] = base64_decode($gReply['rs256']);

		// validate
		if ($gReply['userInfo']['aud'] != $_CFG['socialAuth']['google']['clientId']){
			$this->showError('Authorization error (STEP 2)');
			return ;		
		}

		$userInfo = $this->getUserInfo($real_token['access_token']);

        $newUser['email'] = $gReply['userInfo']['email'];
        $newUser['details'] = $gReply ;
        $newUser['userInfo'] = $userInfo;
		$newUser['reg_type'] = 'google';
        $newUser['name'] = @$userInfo['given_name'] ;
        $newUser['surname'] = @$userInfo['family_name'] ;


        $newUser['social_id'] = $userInfo['id'] ;

        $newUser['avatar_url'] = $userInfo['picture'] ;

		include($_CFG['root'] . 'auth/authBrain.php');
		
	}
	
	private function exchangeOauthToken(){
		Global $_CFG ;
		
		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v3/token');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, "code=" . urldecode($_GET['code']) . "&client_id=" . $_CFG['socialAuth']['google']['clientId'] . "&client_secret=" . $_CFG['socialAuth']['google']['clientSecret'] . "&redirect_uri=" . $this->redirect_uri . "&grant_type=authorization_code");
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