<?php
/* NAME
 *
 *  index.php
 *
 * CONCEPT
 *
 *  Top-level index file for the pattern selection projects.
 *
 * $Id: index.php,v 1.26 2023/03/22 20:43:25 rose Exp $
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require 'project/lib/ps.php';

DataStoreConnect();
Initialize();
$projects = GetProjects();

if($isLogged = $auth->isLogged()) {
  $userinfo = $auth->getCurrentSessionUserInfo();
  $timestamp = strtotime($userinfo['expiredate']);
  $expire = "<script>\n let exptime = $timestamp\n</script>\n";
} else {
  $expire = '';
}
print "<!doctype html>
<html lang=\"en\">

<head>
 <title>Pattern Sphere</title>
 <link rel=\"stylesheet\" href=\"project/lib/ps.css\">
 <script src=\"project/lib/ps.js\"></script>
 $expire
</head>

<body>

<header>
<h1>Pattern Sphere</h1>
</header>

<div id=\"poutine\">
<img src=\"images/pattern-sphere-band.png\" id=\"gravy\">
";

$intro = GetAppConfig('appintro');

print $intro;

print "<h2>Projects</h2>
";

$super = $manager = '';

if($isLogged) {

  // This user has a login.
  
  $user = $auth->getCurrentUser(true);
  $actual = $user;
  
  $action = array(' <li><a href="log.php">Log out</a></li>',
    ' <li><a href="profile.php">Edit profile</a></li>');
  
  if($user['role'] == 'super') {
    $super = '<div id="super">
<div class="banner">Super actions</div>
<ul>
 <li><a href="config.php">Edit config</a></li>
 <li><a href="users.php">Manage users</a></li>
 <li><a href="patterneditor.php">Manage patterns</a></li>
 <li><a href="projecteditor.php">Manage projects</a></li>
 <li><a href="orgeditor.php">Manage organizations</a></li>
 <li><a href="teameditor.php">Manage teams</a></li>
</ul>
</div>
';
  } else {
    $teams = ManagedTeams($user['id']);
    if(count($teams)) {

      // this user manages one or more teams

      $manager = '<div id="mange">
<div class="banner">Manager actions</div>
<ul>
 <li><a href="teameditor.php">Manage teams</a></li>
</ul>
</div>
';
    }
  }
  
} else {

  // is not authenticated
  
  $action = [
             '<li><a href="register.php">Register</a></li>',
             '<li><a href="log.php">Log in</a></li>',
             '<li><a href="reset.php">Reset password</a></li>'
	    ];
?>
<?php
}

$actions = join('', $action);

?>
<div id="actions">
<div id="authactions">
<div class="banner">User actions</div>
<ul>
<?=$actions?>
</ul>
</div>
<?=$manager?>
<?=$super?>
</div>

<ul>
<?php

# Present a menu of active projects to all comers.

foreach($projects as $project) {
  if($project['id'] && $project['active']) {
    print "<li><a href=\"${project['tag']}/\">${project['title']}</a></li>\n";
  }
}
print "</ul>\n";

# Present a menu of inactive projects to admins.

if(isset($user) && $user['role'] == 'super') {
  print '<h3>Inactive Projects</h3>
<ul>
';
  foreach($projects as $project) {
    if($project['id'] && !$project['active']) {
      print "<li><a href=\"${project['tag']}/\">${project['title']}</a></li>\n";
    }
  }
  print "</ul>\n";
}
?>
</ul>
</div>
<?=FOOT?>
</body>
</html>
