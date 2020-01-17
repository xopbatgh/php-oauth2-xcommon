<?php
//print '<pre>' . print_r($_CFG, true) . '</pre>';
include_once('../config.php');
header('Content-Type: text/html; charset=utf-8');


require_once($_CFG['root'] . './twitteroauth/twitteroauth.php');

class authClass {
	private $redirect_uri = ''; // For API
	
	public function showError($error_text){
		Global $_CFG ;
		
		redirect('http://' . $_CFG['domain'] . '#authMessage=' . $error_text);
	}
	
    public function __construct() {
		Global $_CFG ;
		
		$this->redirect_uri = 'http://' . $_CFG['domain'] . '/auth/twitter.php';


		if (empty($_GET)){
			$this->receiveRequestToken();
			exit();
		}
			
		if (isset($_GET['oauth_token']) AND isset($_GET['oauth_verifier'])){
			$this->doAuthOrRegister();
		}
    }

	
	private function receiveRequestToken(){
		Global $_CFG ;

		/* Build TwitterOAuth object with client credentials. */
		$connection = new TwitterOAuth($_CFG['socialAuth']['twitter']['consumerKey'], $_CFG['socialAuth']['twitter']['consumerSecret']);

		/* Get temporary credentials. */
		$request_token = $connection->getRequestToken($this->redirect_uri);

		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		$url = $connection->getAuthorizeURL($request_token['oauth_token']);
		header('Location: ' . $url);

	}
	
	private function getTwitterAccess(){
		Global $_CFG, $_USER ;
		
		/* Create a TwitterOauth object with consumer/user tokens. */
		$connection = new TwitterOAuth($_CFG['socialAuth']['twitter']['consumerKey'], $_CFG['socialAuth']['twitter']['consumerSecret'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		
		$access_token = $connection->getAccessToken($_GET['oauth_verifier']);

		/* Save the access tokens. Normally these would be saved in a database for future use. */
		$_SESSION['access_token'] = $access_token;

		//$connection->get('users/show', array('screen_name' => 'abraham'));
		return $content = $connection->get('account/verify_credentials');	
	}
	
	private function doAuthOrRegister(){
		Global $_CFG, $_USER ;
		
		$twitterReply = (array) $this->getTwitterAccess();
		
		if (!isset($twitterReply['screen_name'])){
			$this->showError('Authorization error (STEP 1)');
			return ;		
		}
		
		$newUser['email'] = $twitterReply['screen_name'] . '@notwittermail';
		$newUser['login'] = $twitterReply['screen_name'];
		$newUser['reg_type'] = 'tw';
		$newUser['social_id'] = $twitterReply['id'] ;
		$newUser['details'] = $twitterReply ;
		
		include($_CFG['root'] . 'auth/authBrain.php');
		
	}
	
	private function exchangeOauthToken(){
		Global $_CFG ;
		
		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://oauth.vk.com/access_token');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);			
			curl_setopt($curl, CURLOPT_POST, true);
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
	  
	  
	  
		print 'token';
	}
}

$aux = new authClass;
	



?>