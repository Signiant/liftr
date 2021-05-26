<?php
	session_name("liftr-prod");
	session_start();

	require 'vendor/autoload.php';
	require "lib/common.php";
    require "lib/config.php";
	require "lib/route53.php";
	require "auth.php";

    //require "auth_google.php";



    // init configuration
    $clientID = '482772100929-dr3s8l5v64e5p721kq7l3ms1idajp5mq.apps.googleusercontent.com';
    $clientSecret = '5n0JUv3I_XbmscWKVe5TKM6q';
    $redirectUri = 'http://localhost:8080/liftr.php';

    // create Client Request to access Google API
    $client = new Google_Client();
    $client->setClientId($clientID);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope("email");
    $client->addScope("profile");

    // authenticate code from Google OAuth Flow
    if (isset($_GET['code'])) {
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      $client->setAccessToken($token['access_token']);

      // get profile info
      $google_oauth = new Google_Service_Oauth2($client);
      $google_account_info = $google_oauth->userinfo->get();
      $email =  $google_account_info->email;
      $name =  $google_account_info->name;


//     $client = new \Google_Client();

//     $credentials_file = base_path() . '/liftr-service-account.json';
//
//     if ($credentials_file = $this->checkServiceAccountCredentialsFile($credentials_file)) {
//     $client->setAuthConfig($credentials_file);
//     }
//
//     $client->setApplicationName("liftr-login-tool");
//     $client->setSubject("gkeir@signiant.com");
//     $client->setScopes([
//     'https://www.googleapis.com/auth/admin.directory.group.readonly'
//     ]);
//
//     $service = new \Google_Service_Directory($client);
//
//     $groupKey = 'SRE';
//
//     $results = $service->groups->get($groupKey);
//
//     echo $results;



      // now you can use this profile info to create account in your website and make user logged in.
    } else {
      echo "<a href='".$client->createAuthUrl()."'>Google Login</a>";
    }

?>

