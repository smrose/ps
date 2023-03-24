<?php
/* NAME
 *
 *  profile.php
 *
 * CONCEPT
 *
 *  User profile management.
 *
 * NOTES
 *
 *  The PHPAuth model requires that the user provide their current password,
 *  despite they have authenticated to get here. We follow that.
 *
 *  The changes that can be made: email, password, and - our extension to
 *  the user object - fullname and username, but not role.
 *
 *  There are methods for changing email and password. For changing fullname
 *  and username we use Update(), our own GP SQL function.
 *
 * $Id: profile.php,v 1.7 2023/03/22 20:43:25 rose Exp $
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

/* Redirect if unauthenticated. */

if(! $auth->isLogged()) {
  header('Location: ./');
  exit;
}

$user = $auth->getCurrentUser(true);

$title = "Profile Management";
?>
<!doctype html>
<html lang="en">

<head>
 <title><?=$title?></title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <script src="project/lib/ps.js"></script>
</head>

<body>

<header>
<h1><?=$title?></h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

<?php

if(isset($_POST['submit'])) {

  ob_start(); var_dump($_POST); error_log(ob_get_contents()); ob_end_clean();

  // Possible updates. Check current password.

  if(isset($_POST['password'])) {
    $curpass = $_POST['password'];
    if(! $auth->comparePasswords($user['uid'], $curpass)) {
      print("<p>Error: the password you provided does not match your current password. <a href=\"profile.php\">Try again</a>.</p>\n");
      exit();
    }
  } else {
    print("<p>Error: you must provide your current password to make any changes. <a href=\"profile.php\">Try again</a>.</p>\n");
    exit();
  }

  $clean = true;
  $updates = array();
  
  $fullname = trim($_POST['fullname']);
  if(strlen($fullname) && $user['fullname'] != $fullname) {
    $updates['fullname'] = $fullname;
  }
  
  $username = strtolower(trim($_POST['username']));
  if($username != $user['username']) {
    if(IsUsernameTaken($username)) {
      print "<p>We already have a user with the username <tt>$username</tt>.
You must choose another.</p>\n";
      exit;
    }
    if(!IsUsernameValid($username)) {
      print "<p>We require a username to consist between four and twenty 
characters consisting of of lower-case letters, digits, underscores, and 
dashes. Choose another.</a>\n";
      exit;
    }
  }
  if(count($updates)) {
    $clean = false;
    $rVal = Update('phpauth_users',
                   $updates,
                   array('uid' => $user['uid']));
    if($rVal['error']) {
      print "<p>Error: ${rVal['message']}</p>\n";
      exit();
    }
    foreach($updates as $k => $v) {
      print "<p>Updated <tt>$k</tt> to <tt>$v</tt></p>\n";
    }
  } /* end case of one or more updates to custom fields */

  if(isset($_POST['newpass']) && strlen($_POST['newpass'])) {
    $newpass = $_POST['newpass'];
    $rVal = $auth->changePassword($user['uid'],
                                  $curpass,
			          $newpass,
			          $newpass);
    if($rVal['error']) {
      print "<p>Error: ${rVal['message']}</p>\n";
      exit();
    } else {
      print "<p>Password updated.</p>\n";
    }
    $clean = false;
  }

  if(isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if(strlen($email) && $user['email'] != $email) {
      $rVal = 
        changeEmail($user['uid'], $email, $curpass);
      if($rVal['error']) {
        print "<p>Error: ${rVal['message']}</p>\n";
        exit();
      }
    }
    $clean = false;
  }

  // Refresh settings to reflect changes and offer the form again.
  
  if($clean) {
    print "<p>No changes made.</p>\n";
  } else {
    $user = $auth->getCurrentUser(true);
  }
  
} # end case of submitted form

//print 'User: <pre>'; print_r($user); print "</pre>\n";

?>

<p>You can change your fullname, username, email, and/or password on this page.
Provide your current password to reconfirm your identity.</p>

<form method="POST" class="gf">
<div class="fieldlabel">Current password:</div> <div><input type="password" name="password" id="password"> <input type="button" value="Unmask" id="password-mask" onclick="Mask('password')"></div>
<div class="fieldlabel">Fullname:</div> <div><input type="text" name="fullname" value="<?=$user['fullname']?>"></div>
<div class="fieldlabel">Username:</div> <div><input type="text" name="username" value="<?=$user['username']?>"></div>
<div class="fieldlabel">Email:</div> <div><input type="text" name="email>" value="<?=$user['email']?>"></div>
<div class="fieldlabel">New password:</div> <div><input type="password" name="newpass" id="newpass"> <input type="button" value="Unmask" id="newpass-mask" onClick="Mask('newpass')"></div>
<div class="gs">
 <input type="submit" name="submit" value="Process changes">
 <input type="submit" name="cancel" value="Cancel">
</div>
</form>
</div>
<?=FOOT?>
</body>
</html>
