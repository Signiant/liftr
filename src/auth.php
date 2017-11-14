<?php

function logIn($auth_realm,$config)
{
  if (!isset($_SESSION['username']))
  {
    if (!isset($_SESSION['login']))
    {
      $_SESSION['login'] = TRUE;
      header('WWW-Authenticate: Basic realm="'.$auth_realm.'"');
      header('HTTP/1.0 401 Unauthorized');
      writePage("invaliduser");
      exit;
    } else {
      $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
      $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';

      $result = authenticate($user, $password, $config);

      if ($result == 0)
      {
        $_SESSION['username'] = $user;
      } else
      {
        session_unset($_SESSION['login']);
        if ($result == 1) {
          writePage("missingconfig");
        } else if($result == 2) {
          writePage("invaliduser");
        } else if($result == 3) {
          writePage("nopermission");
        } else
          writePage("unknown");
          exit;
      }
    }
  }
}


// RETS: 1 - bad config.  2 - invalid user.  3 - no permissions
function authenticate($user, $password, $configuration)
{
  $authenticated = 99; //start off as unknown
  $configOK = True;

  $account_suffix = $configuration['active_directory']['account_suffix'];
  $base_dn = $configuration['active_directory']['base_dn'];
  $domain_controllers = $configuration['active_directory']['domain_controllers'];

  if ( empty($account_suffix) || empty($base_dn) || empty($domain_controllers)  )
  {
    $configOK = False;
    $authenticated = 1;
  }

  if ($configOK)
  {
    $adldap = new adLDAP(array( "account_suffix" => $account_suffix,
                                "base_dn" => $base_dn,
                                "domain_controllers" => $domain_controllers));

    if($adldap->authenticate($user, $password))
    {
      $u = $adldap->user()->info($user, array("memberOf"));
      $authenticated = 3; // since the user+pw is correct, start off as no permission
      if(!empty($configuration["auth_groups"]))
      {
        foreach($u[0]["memberof"] as $group){
          foreach($configuration["auth_groups"] as $ag)
          {
            if(strpos($group, $ag) !== FALSE)
              $authenticated = 0; //part of the group
          }
        }
      } else
          $authenticated = 0; //no group specified lets auth everyone
    } else {
        $authenticated = 2;  // invalid user
    }
  }
  return $authenticated;
}

function logOut()
{
  error_log("LOGOUT: " . session_name());
  session_destroy();
  if (isset($_SESSION['username']))
  {
    session_unset($_SESSION['username']);
    writePage("logout");
  } else {
    header("Location: ?action=logIn", TRUE, 301);
  }

  if (isset($_SESSION['login']))
  {
    session_unset($_SESSION['login']);
  }

  exit;
}

function writePage ($type)
{
  $type = strtolower($type);
  $message = array();

  // common header
  print <<< COMMONHEADER
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="images/liftr.png">

    <title>Liftr</title>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.9.0/bootstrap-slider.min.js"></script>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.9.0/css/bootstrap-slider.min.css">
    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">
  </head>

  <body>

    <div class="container">
COMMONHEADER;
    if (file_exists("menu.php")) { include 'menu.php'; }

    // case specific alert
    switch ($type)
    {
        case "logout":
            $buttonText = "Log In";
            $ref = '/';
            $message['type'] = 'success';
            $message['text'] = 'You have successfully logged out';
            break;
        case "nopermission":
            $buttonText = "Try Again";
            $ref = '/';
            $message['type'] = 'danger';
            $message['text'] = 'You do not have the permission to access the feature flag editor';
            break;
        case "invaliduser":
            $buttonText = "Try Again";
            $ref = '/';
            $message['type'] = 'danger';
            $message['text'] = 'The username or password you entered is incorrect';
            break;
        case "missingconfig":
            $buttonText = "Try Again";
            $ref = '/';
            $message['type'] = 'danger';
            $message['text'] = 'The configuration file is missing critical information for active directory. Fix it and try again';
            break;
        case "nofunction":
            $message['type'] = 'danger';
            $message['text'] = 'The specified function does not exist; request terminated';
            break;
        default:
            $buttonText = "Log In";
            $ref = '/';
            $message['type'] = 'danger';
            $message['text'] = 'An unexpected error has occured';
    }

    doAlert($message['type'],false,$message['text']);

    if (!empty($buttonText)) {
        print "<a class='btn btn-primary' href='" . $ref . "'>" . $buttonText . "</a>\n";
    }

    print <<< COMMONFOOTER
  </body>
</html>
COMMONFOOTER;
}

?>
