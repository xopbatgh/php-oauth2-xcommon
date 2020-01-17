<?php
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
		
		$this->redirect_uri = 'https://' . $_CFG['domain'] . '/auth/facebook.php';

		if (isset($_GET['code']) AND $_GET['state'] == 'facebookAuth'){
			$this->doAuthOrRegister();
		}
		else {
			$this->doRedirectToFacebook();
		}
    }
	
	private function doRedirectToFacebook(){
		Global $_CFG ;
		
		$scope = 'email';

		$destUrl = 'https://graph.facebook.com/oauth/authorize?';
		$params = array(
			'response_type' => 'code' ,
			'client_id' => $_CFG['socialAuth']['facebook']['appId'],
			'redirect_uri' => $this->redirect_uri,
			'state' => 'facebookAuth',
			'scope' => $scope,
		);

		foreach ($params as $_key => $_value)
			$destUrl .= $_key . '=' . $_value . '&';

		header('Location: ' . substr($destUrl, 0, -1));
	
	}
	
	private function doAuthOrRegister(){
		Global $_CFG, $_USER ;
		
		$real_token = $this->exchangeOauthTokenFacebook();

		if (empty($real_token) OR strpos($real_token, 'access_token') === false){
			$this->showError('Authorization error (STEP 1)');
			return ;
		}
		
		$jsoned = json_decode($real_token, true);
		
		if (is_array($jsoned)){
			$access_token = $jsoned['access_token'];
		}
		else {
			$params = explode('&', $real_token);
			$access_token = explode('=', $params[0]);
			$access_token = $access_token[1];
		}
		
		//print '<pre>' . print_r($jsoned, true) . '</pre>';
		
		$userInfo = $this->getUserInfo($access_token);
		
		if (empty($userInfo) OR !isset($userInfo['id'])){
			//$this->showError('Authorization error (STEP 2)');
			return ;		
		}

        $newUser['avatar_url'] = $this->getUserPicture($access_token, $userInfo['id']);

		//$newUser['email'] = $userInfo['email'];
		$newUser['reg_type'] = 'fb';
		$newUser['social_id'] = $userInfo['id'];
		$newUser['details'] = $userInfo ;
        $newUser['access_token'] = $access_token;
        $newUser['email'] = $userInfo['email'];

        $newUser['name'] = @$userInfo['name'] ;

        if (isset($userInfo['first_name']) AND $userInfo['first_name'] != '')
            $newUser['name'] = $userInfo['first_name'];

        if (isset($userInfo['last_name']) AND $userInfo['last_name'] != '')
            $newUser['surname'] = $userInfo['last_name'];

		//print '<pre>' . print_r($newUser, true) . '</pre>';
		//exit();

		include($_CFG['root'] . 'auth/authBrain.php');
		
	}

    private function getUserInfo($access_token){

        $out = file_get_contents('https://graph.facebook.com/me?fields=email,id,name,first_name,last_name&access_token=' . $access_token);
        return json_decode($out, true);

    }

    private function getUserPicture($access_token, $fb_user_id, $size = 'height=480&height=480'){

        return 'https://graph.facebook.com/v5.0/' . $fb_user_id . '/picture?access_token=' . $access_token . '&' . $size;

        $url = file_get_contents($avatarUrl);

        return $url;

    }
	
	private function exchangeOauthTokenFacebook(){
		Global $_CFG ;
		
		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);			
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, "code=" . urldecode($_GET['code']) . "&client_id=" . $_CFG['socialAuth']['facebook']['appId'] . "&client_secret=" . $_CFG['socialAuth']['facebook']['appSecret'] . "&redirect_uri=" . $this->redirect_uri);
			$out = curl_exec($curl);
			curl_close($curl);

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