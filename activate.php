<?php
/*
 * NAME
 *
 *  activate.php
 *
 * CONCEPT
 *
 *  User activation page.
 *
 * NOTES
 *
 *  This page serves two functions:
 *
 *   1. Solicit the activation key in a form.
 *   2. Process the activation key.
 *
 *  The key may be provided in the PATH_INFO.
 *
 * $Id: activate.php,v 1.6 2023/03/22 18:20:25 rose Exp $
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

if(isset($_SERVER['PATH_INFO']) &&
   preg_match('%^/(.{20})$%', $_SERVER['PATH_INFO'], $matches)) {
   $key = $matches[1];   
}

$title = 'Activate';
preg_match('%^(.+)/[^/]+$%', $_SERVER['SCRIPT_NAME'], $matches);
$css = LIBDIR . "/ps.css";
$js = LIBDIR . "/ps.js";
$page = "<!doctype html>
<html lang=\"en\">

<head>
 <title>$title</title>
 <link rel=\"stylesheet\" href=\"$css\">
 <script src=\"$js\"></script>
</head>

<body>

<header><h1>$title</h1></header>

<div id=\"poutine\">
<img src=\"" . IMAGEROOT . "/pattern-sphere-band.png\" id=\"gravy\">
";

if(isset($_POST['submit']) || isset($key)) {
  if(! isset($key))
    $key = trim($_POST['key']);
  $rval = $auth->activate($key);
  if(isset($rval['error'])) {
    print "$page\n<p>{$rval['message']}</p>\n";
    
    if($rval['error'] == 'Activation key is invalid.') {
      print "<p>The activation key you entered is not valid. Click <a href=\"activate.php\">here</a> to try again.</p>\n";
    } else {
      print '<p><a href="' . ROOTDIR . '/log.php">Proceed to login page.</a></p>
';
    }
  }
} else {
  print $page;
?>
<p>You should have received an activation key in your email. Enter it
below to activate your account.</p>

<form method="POST" class="gf">
<div class="fieldlabel">Enter your activation key:</div> <div><input type="text" size="20" name="key"></div>
<div class="gs"><input type="submit" name="submit" value="Activate"></div>
</form>
<?php
}
?>
</div>
<?=FOOT?>
</body>
</html>

