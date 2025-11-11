<?php
/* NAME
 *
 *  test.php
 *
 * CONCEPT
 *
 *  Provide an overview of system data structures: teams, projects, etc.
 */
 
set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require 'lib/ps.php';
DataStoreConnect();
Initialize();

# This view is for superusers only.

$user = $auth->getCurrentUser(true);

if(!$user || $user['role'] != 'super') {
  header('Location: ./');
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
 <title>Test</title>
 <style type="text/css">
  body {
   font-family: sans-serif;
  }
  .two {
   display: grid;
   grid-template-columns: repeat(2, auto);
   width: max-content;
   border: 1px solid #322;
   margin-left: 2em;
  }
  .two div {
   padding: .4em;
   border: 1px dashed #644;
  }
  .three {
   display: grid;
   grid-template-columns: repeat(3, auto);
   width: max-content;
   border: 1px solid #322;
   margin-left: 2em;
  }
  .three div {
   padding: .4em;
   border: 1px dashed #644;
  }
  .four {
   display: grid;
   grid-template-columns: repeat(4, auto);
   width: max-content;
   border: 1px solid #322;
   margin-left: 2em;
  }
  .four div {
   padding: .4em;
   border: 1px dashed #644;
  }
  .five {
   display: grid;
   grid-template-columns: repeat(5, auto);
   width: max-content;
   border: 1px solid #322;
   margin-left: 2em;
  }
  .five div {
   padding: .4em;
   border: 1px dashed #644;
  }
  .six {
   display: grid;
   grid-template-columns: repeat(6, auto);
   width: max-content;
   border: 1px solid #322;
   margin-left: 2em;
  }
  .six div {
   padding: .4em;
   border: 1px dashed #644;
  }
  .b {
   font-weight: bold;
   background-color: #ccc;
  }
 </style>
</head>
<body>

<h1>Test</h1>

<h3>
ROOTDIR <?=ROOTDIR?><br>
LIBDIR <?=LIBDIR?><br>
SCRIPT_NAME: <?=$_SERVER['SCRIPT_NAME']?><br>
DSN <?=DSN?>
</h3>



<ul>
 <li><a href="#teams">Teams</a></li>
 <li><a href="#projects">Projects</a></li>
 <li><a href="#organizations">Organizations</a></li>
 <li><a href="#projmanagers">Project Managers</a></li>
 <li><a href="#orgmanagers">Organization Managers</a></li>
 <li><a href="#users">Users</a></li>
 <li><a href="#patterns">Patterns</a></li>
</ul>

<?php
  preg_match('%(/[^/]+/[^/]+)/%', $_SERVER['SCRIPT_NAME'], $matches);

  $organizations = GetOrganizations();
  $projects = GetProjects();
  $teams = GetTeams();
  $users = GetUsers();
  $patterns = GetPattern();
  $projmanagers = GetProjManagers();
  $orgmanagers = GetOrgManagers();
?>

<h2 id="teams">Teams</h2>

<div class="three">
<div class="b">ID</div>
<div class="b">Name</div>
<div class="b">Members</div>

<?php
  foreach($teams as $team) {
    $tms = GetTeamMembers($team['id']);
    $members = '';
    foreach($tms as $tm) {
      if(strlen($members)) {
        $members .= ", {$tm['username']}";
      } else {
        $members = $tm['username'];
      }
    }
    print "
<div>{$team['id']}</div>
<div>{$team['name']}</div>
<div>$members</div>
";
  }
?>
</div>

<h2 id="projects">Projects</h2>

<div class="five">
 <div class="b">Tag</div>
 <div class="b">Title</div>
 <div class="b">ID</div>
 <div class="b">Active</div>
 <div class="b">Teams</div>

<?php
  foreach($projects as $project) {
    $projTeams = GetProjTeams($project['id']);
    $pts = '';
    foreach($projTeams as $projTeam) {
      if(strlen($pts)) {
        $pts .= ", {$projTeam['name']}";
      } else {
        $pts = $projTeam['name'];
      }
    }
    print "
 <div>{$project['tag']}</div>
 <div>{$project['title']}</div>
 <div>({$project['id']})</div>
 <div style=\"text-align: center\">{$project['active']}</div>
 <div>$pts</div>
 ";
  }
?>
</div>

<h2 id="organizations">Organizations</h2>

<div class="three">
<div class="b">ID</div>
<div class="b">Name</div>
<div class="b">Mission</div>

<?php
  foreach($organizations as $organization) {
    print "<div>{$organization['id']}</div>
<div>{$organization['name']}</div>
<div>{$organization['mission']}</div>
";
  }
?>
</div>

<h2 id="projmanagers">Project Managers</h2>

<div class="three">
<div class="b">Tag</div>
<div class="b">Email</div>
<div class="b">Name</div>

<?php
  foreach($projmanagers as $projmanager) {
    print "
<div>{$projmanager['tag']}</div>
<div>{$projmanager['email']}</div>
<div>{$projmanager['fullname']}</div>
";
  }
?>
</div>

<h2 id="orgmanagers">Organization Managers</h2>

<div class="three">
<div class="b">Tag</div>
<div class="b">Email</div>
<div class="b">Name</div>

<?php
  foreach($orgmanagers as $orgmanager) {
    print "
<div>{$orgmanager['name']}</div>
<div>{$orgmanager['email']}</div>
<div>{$orgmanager['fullname']}</div>
";
  }
?>
</div>

<h2 id="users">Users</h2>

<div class="six">
<div class="b">UID</div>
<div class="b">Name</div>
<div class="b">Email</div>
<div class="b">Username</div>
<div class="b">Role</div>
<div class="b">Active</div>

<?php
  foreach($users as $user) {
    print "<div>{$user['uid']}</div>
<div>{$user['fullname']}</div>
<div>{$user['email']}</div>
<div>{$user['username']}</div>
<div>{$user['role']}</div>
<div>{$user['isactive']}</div>
";
  }
?>
</div>

<h2 id="patterns">Patterns</h2>

<div class="three">
<div class="b">ID</div>
<div class="b">Title</div>
<div class="b">PL Title</div>

<?php

foreach($patterns as $pattern) {
  print "<div>{$pattern['id']}</div>
<div>{$pattern['title']}</div>
<div>{$pattern['pltitle']}</div>
";
}

?>
</div>

</body>
</html>
