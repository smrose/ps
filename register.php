<?php
/*
 * NAME
 *
 *  register.php
 *
 * CONCEPT
 *
 *  1. Solicit registration information.
 *  2. Absorb registration information.
 *  3. Request activation email resend.
 *
 * $Id: register.php,v 1.8 2023/03/22 18:29:42 rose Exp $
 */

if(isset($_POST) && isset($_POST['cancel'])) {
  header('Location: ./');
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

$title = 'Register';

$page = "<!doctype html>
<html lang=\"en\">

<head>
 <title>$title</title>
 <link rel=\"stylesheet\" href=\"" . LIBDIR . "/ps.css\">
 <script src=\"" . LIBDIR . "/ps.js\"></script>
</head>

<body>

<header>
<h1>$title</h1>
</header>

<div id=\"poutine\">
<img src=\"images/pattern-sphere-band.png\" id=\"gravy\">
";

// Form data.

if(isset($_REQUEST['resend'])) {

  // We are here to resend an activation email.
  
  $email = trim($_REQUEST['email']);
  $rval = $auth->resendActivation($email, 1);
  if($rval['error']) {
    print "$page <p>${rval['message']}</p>\n";
  } else {
    print "$page <p>${rval['message']} Click <a href=\"activate.php\">here</a> to enter it.</p>\n";
  }
  
} else if(isset($_POST['register'])) {

  // We are here to register a new account.
    
  $email = trim($_REQUEST['email']);
  $password = trim($_POST['password']);
  $fullname = trim($_POST['fullname']);
  $username = strtolower(trim($_POST['username']));

  // Does this email exist?

  if($auth->isEmailTaken($email)) {

    // An existing user.

    print "$page
<p>We already have a user <tt>$email</tt>. If this is your email address and you need to reset your password, <a href=\"reset.php\">click here</a>. To register with a different email address, <a href=\"log.php\">click here</a>.</p>\n";

  } else if(IsUsernameTaken($username)) {
    
    print "$page
<p>We already have a user with the username <tt>$username</tt>. If this is your username and you need to reset your password, <a href=\"reset.php\">click here</a>. To register with a different username, <a href=\"log.php\">click here</a>.</p>\n";

  } else if(! IsUsernameValid($username)) {
    
    print "$page
<p>We require a username to consist of between four and twenty characters consisting of lower-case letters, digits, underscores, and dashes. To register with a different username, <a href=\"log.php\">click here</a>.</p>\n";

  } else {

    // A new user.

    if(!strlen($fullname)) {
      print "$page\n<p>We require that you include your name.</p>\n";
      exit();
    }
  
    $rval = $auth->register($email,
                            $password,
                            $password,
                            array(
		             'fullname' => $fullname,
		             'username' => $username
			    ),
                            '',
			    !AUTOACTIVATE);
    if($rval['error']) {
      print "$page
<p>Error: ${rval['message']}</p>
<p><a href=\"register.php\">Click here</a> to try again.</p>\n";
    } else {

      // Registration is accepted.

      if(AUTOACTIVATE) {

        // If the just-registered user is auto-activated, log them in and
        // redirect.
      
        $rval = $auth->login($email,
                             $password,
                             false);
        header('Location: ./');
        exit();
      }

      // Not auto-activating.
	
      print "$page
<p>Registration accepted. You will immedately be receiving an email with a 
link containing the key required to activate your account. Please follow 
that link promptly, as the activation key has a limited lifetime.</p>
";
    }
  }
} else {

  # Solicit registration information.

  print $page;
?>    
<h2 id="register">Register</h2>

<p>If you don't yet have an account, you can create one here. Enter your
email, name, a username and the password you wish to use. We'll send an email
to that account with the link and a key that you will use to activate the
new account. Note: the activation key is only valid for a few minutes.</p>

<form method="POST" action="register.php" class="gf">
<div class="fieldlabel">Email address:</div> <div><input type="text" size="35" name="email"></div>
<div class="fieldlabel">Password:</div> <div><input type="password" name="password" id="regpassword">
<input type="button" value="Unmask" id="regpassword-mask" onclick="Mask('regpassword')"></div>
<div class="fieldlabel">Your name:</div> <div><input type="text" size="35" name="fullname"></div>
<div class="fieldlabel">Your username:</div> <div><input type="text" size="35" name="username" size="20"></div>
<div class="gs">
<input type="Submit" name="register" value="Register">
<input type="submit" name="cancel" value="Cancel">
</div>
</form>

<h3>Resend the Activation Email</h3>

<p>If you need another copy of the activation email, enter your email in the
form below.</p>

<form method="POST" action="register.php" class="gf">
<div class="fieldlabel">Email address:</div> <div><input type="text" size="35" name="email"></div>
<div class="gs">
<input type="Submit" name="resend" value="Resend activation email">
<input type="submit" name="cancel" value="Cancel">
</div>
</form>
<?php
}
?>
</div>
<?=FOOT?>
</body>
</html>
