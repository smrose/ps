<?php
/* NAME
 *
 *  reset.php
 *
 * CONCEPT
 *
 *  Reset a password.
 *
 * NOTES
 *
 *  Users that need a password reset do this:
 *
 *   1. Enter their email and submit the reset form in with this
 *      script as the action and their email as the query parameter.
 *      $state == 'start'.
 *
 *   2. We call requestReset(), which generates an email to the provided
 *      address containing a link with reset key (if the email is known).
 *      We show a dead-end page setting expectations. $state == 'request'.
 *
 *   3. Following the link from the email offers a form with a field to
 *      enter the new password. $state == 'newpass'.
 *
 *   4. Submitting that form passes the key forward and calls resetPass().
 *      We present (either an error message or) a confirmation with a
 *      link to the main page. $state == 'reset'.
 *
 * $Id: reset.php,v 1.10 2023/03/22 18:32:46 rose Exp $
 */

preg_match('%^(.*)/[^/]+%', $_SERVER['SCRIPT_NAME'], $matches);
$top = $matches[1];
error_log("\$top $top");

if(isset($_SERVER['PATH_INFO']) &&
  preg_match('%^/(.{20})$%', $_SERVER['PATH_INFO'], $matches)) {
  
  /* We followed a link from an emailed password reset request, which puts
   * the reset key in $PATH_INFO, banjaxing relative links. Stash the key
   * and reformulate links as absolute ones. */

  $key = $matches[1];
  $css = $top . '/project/lib/ps.css';
  $js = $top . '/project/lib/ps.js';
  $state = 'newpass';
} else {
  $css = "project/lib/ps.css";
  $js = "project/lib/ps.js";
}

if(isset($_POST) && isset($_POST['cancel']))
  header("Location: $top");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

if($auth->isLogged()) {

  // Logged-in users shouldn't be here.
  
  header("Location: $top");
  exit;
}

if(DEBUG && isset($_POST)) error_log('POST: ' . var_export($_POST, true));

$title = "Password Reset";

$email = (isset($_POST['email'])) ? $_POST['email'] : null;
$password = (isset($_POST['password'])) ? $_POST['password'] : null;

if(isset($state))
  true;
elseif($email) {

  /* An email address was provided. Send the email containing the
   * reset key, then display a page setting expectations. */
  
  if(DEBUG) error_log("requestReset('$email')");
  $rval = $auth->requestReset($email, true);
  if(DEBUG) error_log('requestReset() returns ' . var_export($rval, true));
  $state = 'request';
  
} elseif(isset($password)) {

  // A new password was provided. Perform the reset.

  $password = $_POST['password'];
  $key = $_POST['key'];
  $rval = $auth->resetPass($key, $password, $password);
  $state = 'reset';
} else {
  $state = 'start';
}
$headimg = ROOTDIR . '/' . IMAGEDIR . '/pattern-sphere-band.png';
?>
<!doctype html>
<html lang="en">

<head>
 <title><?=$title?></title>
 <link rel="stylesheet" href="<?=$css?>">
 <script src="<?=$js?>"></script>
</head>

<body>

<header>
<h1><?=$title?></h1>
</header>

<div id="poutine">
<img src="<?=$headimg?>" id="gravy">

<?php

if($state == 'reset') {

  // After processing new password.
  
  if($rval['error']) {

    // Error submitting new password via resetPass().
  
    print "<p class=\"error\">Error: {$rval['message']}</p>

<p><a href=\"./\">Return</a> or <a href=\"reset.php\">retry</a>.</p>
";
    if(DEBUG) {
      error_log(var_export($rval, true));
      error_log(var_export("key: $key", true));
    }
  } else {
  
    // Successfully set new password.

    print "<p class=\"alert\">Password reset. <a href=\"log.php\">Click here to login</a>.</p>\n";
  }
} else if($state == 'request') {

  // Dead-end page; email is in transit.
  
  print "<p class=\"alert\">We have sent the password reset email to <code>$email</code>. That
email contains a link that you should follow to reset the password.</p>\n";
  
} else if($state == 'newpass') {

  // Get the password from the user.
?>
<p class="alert">Enter your new password in the form below.</p>

<form method="POST" class="gf" action="<?=$top?>/reset.php">
<div class="fieldlabel">Reset key:</div><div><input type="text" size="20" name="key" value="<?=$key?>" disabled></div>
<div class="fieldlabel">Enter new password:</div><div><input id="logpassword" type="password" name="password"> <input type="button" name="mask" value="Unmask" id="logpassword-mask" onclick="Mask('logpassword')"></div>
<div class="gs">
 <input type="submit" name="submit" value="Reset password">
 <input type="submit" name="cancel" value="Cancel">
</div>
<input type="hidden" name="key" value="<?=$key?>">
</form>

<?php
} else {

  // $state == 'start'
?>
<h3 id="recover">Recover Password</h3>

<p class="alert">If you have forgotten your password, enter your email address
below and we will send you an email with a link to a page where you can reset
it. That link contains a key with a limited lifetime, so please act
promptly.</p>

<form method="POST" action="reset.php" class="gf">
<div class="fieldlabel">Email address:</div> <div><input type="text" size="35" name="email"></div>
<div class="gs">
<input type="submit" name="reset" value="Send password reset email">
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
