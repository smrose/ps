<?php
/* NAME
 *
 *  teams.php
 *
 * CONCEPT
 *
 *  Manage which teams participate in this project.
 *
 * FUNCTIONS
 *
 *  AbsorbManage  absorb form input
 *
 * NOTES
 *
 *  Arriving here without a suitable role - superuser or manager of this
 *  project - silently redirects to the top of the project.
 *
 * $Id: teams.php,v 1.2 2023/03/22 18:15:39 rose Exp $
 */


/* AbsorbManage()
 *
 *  Absorb a form submission.
 */

function AbsorbManage() {
  global $project, $projteams, $teams;

  $prid = $project['id'];
  $changes = 0;

  // loop on POST params looking for new teams
  
  foreach($_POST as $k => $v)
    if(preg_match('/^\d+$/', $k) && !array_key_exists($k, $projteams)) {
      InsertProjTeam($prid, $k);
      $name = $teams[$k]['name'];      
      print "<p class=\"alert\">Added team <i>$name</i>.</p>\n";
      $changes++;
    }

  // Loop on participating teams, looking for discarded ones.

  foreach($projteams as $k => $v)
    if(!array_key_exists($k, $_POST)) {
      DeleteProjTeam(['id' => $v['id']]);
      $name = $v['name'];
      print "<p class=\"alert\">Removed team <i>$name</i>.</p>\n";
      $changes++;
    }
    
  if(!$changes)
    print "<p class=\"alert\">No changes.</p>\n";
  else
    $projteams = GetProjTeams($prid);

  return(true);
  
} /* end AbsorbManage() */


require "lib/ps.php";

DataStoreConnect();
Initialize();

if(isset($_POST) && isset($_POST['cancel']))
  header("Location: ./");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(DEBUG && isset($_POST)) error_log(var_export($_POST, true));

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);
if($user['role'] != 'super' && !IsProjectManager())
  header('Location: ./');

/* This user is authorized. */

$title = "{$project['title']}: Membership Management";
?>
<!doctype html>
<html lang="en">

<head>
 <title><?=$title?></title>
 <link rel="stylesheet" href="<?=LIBDIR?>/ps.css">
 <link rel="script" href="<?=LIBDIR?>/ps.js">
</head>

<body>

<header>
<h1><?=$title?></h1>
</header>

<div id="poutine">
<img src="../images/pattern-sphere-band.png" id="gravy">

<?php

$teams = GetTeams();
$projteams = GetProjTeams($project['id']);

if(isset($_POST) && isset($_POST['submit'])) {

  // Process a form submission.

  AbsorbManage();
}

print '<p class="alert">Below is a table of all the teams in the
system. Check any unchecked team to add and uncheck any checked team
to remove. Press the Submit button to absorb your changes.<p>

<form action="teams.php" method="POST">
<div class="gf">
<div class="gb" style="border-bottom: 1px solid #644">Team</div>
<div class="gb" style="border-bottom: 1px solid #644">Member</div>
';

foreach($teams as $id => $team) {
  $checked = array_key_exists($id, $projteams) ? ' checked' : '';
  print "<div style=\"text-align: center\">{$team['name']}</div>
<div class=\"\" style=\"text-align: center\">
 <input type=\"checkbox\" name=\"{$team['id']}\" value=\"1\"$checked>
</div>
";

} // end loop on teams

print '<div style="grid-column: span 2; text-align: center">
 <input type="submit" name="submit" value="Absorb project teams">
 <input type="submit" name="cancel" value="Cancel">
</div></div>
</form>
';
?>

<p><a href="./">Return</a>.</p>
</div>
<?=FOOT?>
</body>
</html>
