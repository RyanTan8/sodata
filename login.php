<?php
// Include configuration file
require_once  ("../connectsodb.php");
// Include Google API client library
require_once 'google-api-php-client/vendor/autoload.php';
// Call Google API
$gClient = new Google_Client();
$gClient->setClientId(GOOGLE_CLIENT_ID);
$gClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$gClient->setRedirectUri(GOOGLE_REDIRECT_URL);
$gClient->addScope(['email', 'profile']);
//$gClient->setScopes(array('https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/moderator'));


// Include User library file
require_once 'User.class.php';

if(isset($_GET['code'])){
    $gClient->authenticate($_GET['code']);
    $_SESSION['token'] = $gClient->getAccessToken();
    header('Location: ' . filter_var(GOOGLE_REDIRECT_URL, FILTER_SANITIZE_URL));
}

if(isset($_SESSION['token'])){
    $gClient->setAccessToken($_SESSION['token']);
}

if($gClient->getAccessToken()){
    // Get user profile data from google
		$google_oauth =new Google_Service_Oauth2($gClient);
    $gpUserProfile = $google_oauth->userinfo->get();

    // Initialize User class
    $user = new User($mysqlConn);

    // Getting user profile info
    $gpUserData = array();
    $gpUserData['oauth_uid']  = !empty($gpUserProfile['id'])?$gpUserProfile['id']:'';
    $gpUserData['first_name'] = !empty($gpUserProfile['given_name'])?$gpUserProfile['given_name']:'';
    $gpUserData['last_name']  = !empty($gpUserProfile['family_name'])?$gpUserProfile['family_name']:'';
    $gpUserData['email'] = !empty($gpUserProfile['email'])?$gpUserProfile['email']:'';
    $gpUserData['gender'] = !empty($gpUserProfile['gender'])?$gpUserProfile['gender']:'';
    $gpUserData['locale'] = !empty($gpUserProfile['locale'])?$gpUserProfile['locale']:'';
    $gpUserData['picture'] = !empty($gpUserProfile['picture'])?$gpUserProfile['picture']:'';

    // Insert or update user data to the database
    $gpUserData['oauth_provider'] = 'google';
    $userData = $user->checkUser($gpUserData);

    // Storing user data in the session
    $_SESSION['userData'] = $userData;

		if ($gClient->isAccessTokenExpired()) {
			$output .="<div style='color:red'>Token Expired...Attempting to Refresh</div>";
		  $gClient->fetchAccessTokenWithRefreshToken($gClient->getRefreshToken());
		}
    // Render user profile data
    if(!empty($userData)){
        //header("Location:index.php#user");
				exit;
    }else{
        $output = '<h3 style="color:red">Some problem occurred, please try again.</h3>';
    }
}else{
    // Get login url
    $authUrl = $gClient->createAuthUrl();

    // Render google login button
    $output = '<a href="'.filter_var($authUrl, FILTER_SANITIZE_URL).'"><img src="images/btn_google_signin_dark_normal_web.png" alt="Sign in with Google"/></a>';
}
?>

<div class="container">
    <!-- Display login button / Google profile information -->
    <?php echo $output; ?>
</div>
