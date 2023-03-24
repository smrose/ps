<?php
/* NAME
 *
 *  log.php
 *
 * CONCEPT
 *
 *  Combined login/logout page.
 *
 *  A user arriving with valid credentials is assumed to be here
 *  to logout. We don't need any other information, so we simply call
 *  the logout method and then redirect to the top application page.
 *
 *  A user arriving without credentials is here to login to an existing
 *  account.
 *
 * $Id: log.php,v 1.12 2023/03/22 20:43:25 rose Exp $
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
if(is_array($project)) {
  $atitle = $project['title'];
} else {
  $atitle = 'Pattern Selection Projects';
}

$isLogged = $auth->isLogged();

if($isLogged) {

  // This user is authenticated; they are presumed to be here to log out.

  $hash = $_COOKIE['phpauth_session_cookie'];
  $auth->logout($hash);
  header('Location: ./');
  exit;
}

// This user is not authenticated; offer a login form.
  
$title = 'Pattern Sphere Login';
?>
<!doctype html>
<html lang="en">

<head>
 <title><?=$title?></title>
 <link rel="stylesheet" href="<?=ROOTDIR?>/project/lib/ps.css">
 <script src="<?=ROOTDIR?>/project/lib/ps.js"></script>
</head>

<body>

<header>
<h1><?=$title?></h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

<h2 id="login">Login</h2>

<p>If you already have an account, enter your credentials below to login.
(If not, go <a href="register.php">here</a> to create one.)
Checking the "remember me" box will keep you logged in on the browser you
are using across sessions, and for about a month.</p>

<form method="POST" action="<?=ROOTDIR?>/login.php" class="gf">
<div class="fieldlabel">Email address:</div>
<div><input type="text" size="35" name="email"></div>
<div class="fieldlabel">Password:</div>
<div><input type="password" name="password" id="logpassword"><input type="button" value="Unmask" id="logpassword-mask" onclick="Mask('logpassword')"></div>
<div class="fieldlabel">Remember me:</div>
<div><input type="checkbox" name="remember" value="Remember me"></div>
<div class="gs">
<input type="submit" name="login" value="Login">
<input type="submit" name="cancel" value="Cancel">
</div>
</form>

</div>

<?=FOOT?>
</body>
</html>
