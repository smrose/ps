<?php
#
# NAME
#
#  projecteditor.php
#
# CONCEPT
#
#  Create/edit projects.

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST) && isset($_POST['cancel']))
  header('Location: ./');

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";
require "lib/project.php";

DataStoreConnect();
Initialize();

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);
$project = GetProject();
$role = ProjectRole();

if($role < PRMANAGER) {
  header('Location: ./');
  exit;
}


if(isset($_POST['submit']) && $_POST['submit'] == 'Cancel') {
  header("Location: {$_SERVER['SCRIPT_NAME']}\n");
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Project Management</title>
 <link rel="stylesheet" href="<?=ROOTDIR?>/project/lib/ps.css">
 <script src="<?=ROOTDIR?>/project/lib/ps.js"></script>
 <script src="<?=ROOTDIR?>/project/lib/projedit.js"></script>
</head>

<body>

<header>
<h1>Project Management</h1>
</header>

<div id="poutine">
<img src="<?=ROOTDIR?>/images/pattern-sphere-band.png" id="gravy">

<?php
if(DEBUG && count($_POST)) {
  print "<div class=\"ass\" id=\"ass\">Show POST parameters</div>
<div id=\"posterior\">\n";
  foreach($_POST as $k => $v) {
    print " <div>$k</div>\n<div>$v</div>\n";
  }
  print "</div>\n";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && DEBUG)
  error_log("POST: " . var_export($_POST, true));
if($_SERVER['REQUEST_METHOD'] == 'GET' && DEBUG)
  error_log("GET: " . var_export($_GET, true));

$rv = true;

if(isset($_REQUEST['action'])) {
  $action = $_REQUEST['action'];
  if($action == 'prcreate') {
    if(isset($_POST['submit']) && $_POST['submit'] == 'Create project') {
      $rv = AbsorbCreate();
    } else {
      $rv = prform();
    }
  } elseif($action == 'predit') {

    # Handle editing project metadata, project managers, and project teams.
    
    $prid = $_REQUEST['prid'];
    if($prid < 0)
      Error("Select a project to edit before submitting.");
    if(isset($_REQUEST['submit'])) {
      if($_REQUEST['submit'] == 'Edit this project')
        $rv = prform($prid);
      elseif($_REQUEST['submit'] == 'Absorb edits')
        $rv = AbsorbEdit($prid);
      elseif($_REQUEST['submit'] == 'Edit project teams')
        $rv = ProjTeams($prid);
      elseif($_REQUEST['submit'] == 'Project managers')
        $rv = ProjManagers($prid);
      elseif($_REQUEST['submit'] == 'Absorb managers')
        $rv = AbsorbManagers($prid);
      else
        $rv = AbsorbTeams($prid);
    }
  } else {
     Error('Unknown action');
  }
}
if($rv) {
  $projects = GetProjects();
  if(count($projects)) {
    $options = "<select name=\"prid\">
<option value=\"-1\" selected>Choose project</option>
";
    foreach($projects as $project) {

      if($project['id']) {
        $enabled = IsProjectManager($user['id'], $project['id'])
	  || $user['role'] == 'super';
        $tag = $project['tag'] . ($project['active'] ? '' : '*');
        $disabled = $enabled ? '' : ' disabled';
        $options .= "<option value=\"{$project['id']}\"$disabled>$tag</option>\n";
      }
    }
    $options .= "</select>\n";
    $form = "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\">
  <input type=\"hidden\" name=\"action\" value=\"predit\">
   $options
  <input type=\"submit\" name=\"submit\" value=\"Edit this project\">
  <input type=\"submit\" name=\"submit\" value=\"Edit project teams\">
  <input type=\"submit\" name=\"submit\" value=\"Project managers\">
  </form>
";
  } else
    $form = '<p class="alert" style="left-margin: 1em">No projects exist.</p>
';
?>
<h3>Actions:</h3>

<ul>
 <li><a href="?action=prcreate">Create a project</a></li>
 <li class="spacer"></li>
 <li><b>Edit a project</b><br>
 Use this form to select and act on a project. Inactive projects are shown
 with an asterisk. Press <tt>Edit this project</tt> to make changes to the
 project metadata, such as instructions shown to users or whether it's active.
 Press  the <tt>Edit project teams</tt> to select which user teams participate
 in the project.
 <?=$form?>
 </li>
 <li><a href="./">Return</a></li>
</ul>
<?php
}
?>
<script>
  init()

  function post(event) {
    document.querySelector('#ass').style.display = 'none'
    document.querySelector('#posterior').style.display = 'grid'
  }

  if(ass = document.querySelector('#ass'))
    ass.addEventListener('click', post)
</script>
</div>
<?=FOOT?>
</body>
</html>
