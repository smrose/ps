<?php
/* NAME
 *
 *  login.php
 *
 * CONCEPT
 *
 *  Submit login information.
 *
 * $Id: login.php,v 1.7 2023/03/22 20:43:25 rose Exp $
 */

if(isset($_POST) && isset($_POST['cancel']))
  header('Location: ./');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

$title = "{$project['title']}: Login Result";

# Where we go after a successful login.

$redirect = isset($_POST['referer']) ? $_POST['referer'] : './';

$page = "<!doctype html>
<html lang=\"en\">

<head>
 <title>$title</title>
 <link rel=\"stylesheet\" href=\"" . ROOTDIR . '/lib/ps.css">
 <script src="' . ROOTDIR . "/lib/ps.js\"></script>
</head>

<body>

<header>
<h1>$title</h1>
</header>

<div id=\"poutine\">
<img src=\"images/pattern-sphere-band.png\" id=\"gravy\">
";

// Form data.

$email = $_POST['email'];
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

// Does this email exist?

if($auth->isEmailTaken($email)) {

  // An existing user. Is the password correct?

  $rval = $auth->login($email,
                       $password,
	               $remember);

  if($rval['error']) {
  
    error_log("error code {$rval['error']}");
    error_log("error message '{$rval['message']}'");
    
    if($rval['message'] == 'Account has not yet been activated.') {
      $url = "register.php?resend=1&email=$email";
      print "$page <p>Error: {$rval['message']} Shall I <a href=\"$url\">resend the activation email</a>?</p>\n";
    } else {
      print "$page <p>Error: {$rval['message']}. <a href=\"log.php\">Click here</a> to retry.</p>\n";
    }
  } else {
  
    // Successful login.
    
    if(true) {

      // Redirect to top page.
      
      header("Location: $redirect");
      exit();
    }
    print("<p>Logged in. <a href=\"./\">Click here</a> to continue.</p>\n");
  }
} else {

  // A new user (or misentered email).

  print "$page <p>There is no user with the email address <tt>$email</tt>. To retry login or create a new account, <a href=\"log.php\">click here</a>.</p>\n";
}
?>
</div>
</body>
</html>
