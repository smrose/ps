<?php
#
# NAME
#
#  projecteditor.php
#
# CONCEPT
#
#  Create/edit projects.
#
# FUNCTIONS
#
#  dmenu          callback for destination language popup
#  omenu          callback for organization popup
#  prform         present a form for editing or creating a project
#  AbsorbCreate   create a new project from form input
#  AbsorbEdit     edit an existing project from form input
#  ProjTeams      select which teams work this project
#  AbsorbTeams    absorb team selection
#  ProjManagers   present a form for setting project managers
#  AbsorbManagers absorb project managers
#
# NOTES
#
#  CREATE TABLE project (
#   id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
#   title varchar(255) NOT NULL COMMENT 'project title',
#   visitor_text longtext NOT NULL COMMENT 'HTML instructions for visitors',
#   user_text longtext NOT NULL COMMENT 'HTML instructions for users',
#   super_text longtext NOT NULL COMMENT 'HTML instructions for superusers',
#   assessment_text longtext NOT NULL COMMENT 'displayed above assessment form',
#   abovecomment_text longtext DEFAULT NULL COMMENT 'text above assessment comment area',
#   parting_text longtext DEFAULT NULL COMMENT 'text below assessment comment area',
#   manager_text longtext NOT NULL,
#   tag varchar(80) NOT NULL,
#   active tinyint(1) NOT NULL DEFAULT 1,
#   destination int(10) unsigned DEFAULT NULL,
#   PRIMARY KEY (id),
#   UNIQUE KEY tag (tag),
#   CONSTRAINT FOREIGN KEY(destination) REFERENCES planguage(id)
#  ) COMMENT='project metadata';
#
# $Id: projecteditor.php,v 1.11 2023/03/22 20:43:25 rose Exp $

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST) && isset($_POST['cancel'])) {
  header('Location: ./');
}
set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(!$user || $user['role'] != 'super') {
  header('Location: ./');
  exit;
}

$fields = [
  [
    'name' => 'tag',
    'label' => 'Short name',
    'type' => 'text',
    'maxlength' => 80,
    'size' => 20,
  ],
  [
    'name' => 'title',
    'label' => 'Project title',
    'type' => 'text',
    'maxlength' => 255,
    'size' => 60
  ],
  [
    'name' => 'orgid',
    'label' => 'Organization',
    'type' => 'popup_menu',
    'callback' => 'omenu'
  ],
  [
    'name' => 'visitor_text',
    'label' => 'HTML instructions for visitors',
    'type' => 'textarea'
  ],
  [
    'name' => 'user_text',
    'label' => 'HTML instructions for users',
    'type' => 'textarea'
  ],
  [
    'name' => 'super_text',
    'label' => 'HTML instructions for superusers',
    'type' => 'textarea'
  ],
  [
    'name' => 'assessment_text',
    'label' => 'Text displayed above assessment form',
    'type' => 'textarea'
  ],
  [
    'name' => 'abovecomment_text',
    'label' => 'Text above assessment comment area',
    'type' => 'textarea'
  ],
  [
    'name' => 'parting_text',
    'label' => 'Text below assessment comment area',
    'type' => 'textarea'
  ],
  [
    'name' => 'manager_text',
    'label' => 'HTML instructions for managers',
    'type' => 'textarea'
  ],
  [
    'name' => 'destination',
    'label' => 'Destination language',
    'type' => 'popup_menu',
    'callback' => 'dmenu'
  ],
  [
    'name' => 'active',
    'label' => 'Project is active',
    'type' => 'checkbox'
  ]
];


/* dmenu()
 *
 *  Build a popup menu for destination language.
 */
 
function dmenu() {
  global $pr;
  
  $destmenu = '<select name="destination">
  <option value="0">Select</option>
';
  $planguages = GetPLanguage();
  if(isset($pr))
    $destination = $pr['destination'];

  foreach($planguages as $planguage) {
    $title = $planguage['title'];
    $id = $planguage['id'];
    $selected = ($destination == $id)
     ? ' selected' : '';
    $destmenu .= "  <option value=\"$id\"$selected>$title</option>\n";
  }
  $destmenu .= '</select>
';
  return($destmenu);

} /* end dmenu() */


/* omenu()
 *
 *  Build a popup menu for organization.
 */
 
function omenu() {
  global $pr;
  
  $orgmenu = '<select name="orgid">
  <option value="0">Select</option>
';
  $organizations = GetOrganizations();
  if(isset($pr))
    $orgid = $pr['orgid'];

  foreach($organizations as $organization) {
    $id = $organization['id'];
    if($id) {
      $name = $organization['name'];
      $selected = ($orgid == $id) ? ' selected' : '';
      $orgmenu .= "  <option value=\"$id\"$selected>$name</option>\n";
    }
  }
  $orgmenu .= '</select>
';
  return($orgmenu);

} /* end omenu() */


/* prform()
 *
 *  Present a form for creating ($prid is null) or editing a project.
 */

function prform($prid = null) {
  global $fields, $pr;

  if(isset($prid))
    $pr = GetProject($prid);

  if(isset($prid)) {

    # edit existing
    
    $action = 'predit';
    $slabel = 'Absorb edits';
    $title = "Editing Project <span style=\"font-style: oblique\">${pr['title']}</span>";
    $prid = "<input type=\"hidden\" name=\"prid\" value=\"${pr['id']}\">
<input type=\"hidden\" name=\"action\" value=\"edit\">
";
  } else {

    # Create new. Start with the contents of the template project.

    $pr = GetProject(0);
    $title = 'Creating Project';
    $action = 'prcreate';
    $slabel = 'Create project';
  }
  print "<h2>$title</h2>

<p>NB: the <tt>short name</tt> field is used to set the URL of the project
under <tt>" . ROOTDIR . "</tt>. That needs to be created manually as a
symbolic link before the project will be implemented.</p>

<form method=\"post\" action=\"${_SERVER['SCRIPT_NAME']}\" class=\"gf\"enctype=\"multipart/form-data\" >
$prid
<input type=\"hidden\" name=\"action\" value=\"$action\">
";

  foreach($fields as $field) {
  
    $value = $pr[$field['name']];
    print "<div class=\"fieldlabel\"><label for=\"${field['name']}\">${field['label']}:</label></div>\n";
    
    if($field['type'] == 'text') {
      print "<div><input type=\"text\" name=\"${field['name']}\" size=\"${field['size']}\" maxlength=\"${field['maxlength']}\" value=\"$value\"></div>\n";
    } elseif($field['type'] == 'textarea') {
      print "<div><textarea name=\"${field['name']}\" rows=\"4\" cols=\"80\">$value</textarea></div>\n";
    } elseif($field['type'] == 'checkbox') {
      $checked = $value ? ' checked="checked"' : '';
      print "<div><input type=\"checkbox\" value=\"1\" name=\"${field['name']}\"$checked></div>\n";
    } elseif($field['type'] == 'image') {
      print "<div><input type=\"file\" name=\"${field['name']}\"></div>\n";
    } elseif($field['type'] == 'popup_menu') {
      print $field['callback']();
    }
  } // end loop on fields

  print "<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"$slabel\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  return(false);

} /* end prform() */


/* AbsorbCreate()
 *
 *  Create a new project from form input.
 */
 
function AbsorbCreate() {
  global $fields;
  
  $row = [];
  foreach($fields as $field) {
    $name = $field['name'];
    if($field['type'] == 'checkbox')
      $row[$name] = is_null($_POST[$name]) ? 0 : 1;
    else
      $row[$field['name']] = $_POST[$field['name']];
  }

  $id = InsertProject($row);
  print "<p class=\"alert\">Created a new project <tt>${row['title']}</tt> with ID <tt>$id</tt>.</p>\n";
  return true;
  
} /* end AbsorbCreate() */


/* AbsorbEdit()
 *
 *  Absorb project edits from form input.
 */
 
function AbsorbEdit($prid) {
  global $fields;
  
  $update = ['id' => $prid];
  foreach($fields as $field) {
    $name = $field['name'];
    if($field['type'] == 'checkbox')
      $update[$name] = is_null($_POST[$name]) ? 0 : 1;
    else
      $update[$field['name']] = $_POST[$field['name']];
  }
 
  if(UpdateProject($update))
    print "<p class=\"alert\">Updated project <tt>${update['title']} (ID ${update['id']})</tt>.</p>\n";
  else
    print "<p class=\"alert\">No updates to project <tt>${update['title']}</tt>.</p>\n";
  return true;
  
} /* end AbsorbEdit() */


/* ProjTeams()
 *
 *  Specify which teams work on this project.
 */
 
function ProjTeams($prid) {

  # present a list of teams as a set of checkboxes
  
  $teams = GetTeams();
  if(!count($teams)) {
    print "<p class=\"alert\">No teams are defined yet. 
<a href=\"{$_SERVER['SCRIPT_NAME']}\">Return</a>.</p>\n";
    return(0);
  }
  $pr = GetProject($prid);
  $projteams = GetProjTeams($prid);
  $title = "Editing <span style=\"font-style: oblique\">${pr['title']}</span> Teams";
  
  print "<h2>$title</h2>

<p class=\"alert\">
Select which teams shall participate in this project by checking the
boxes next to the team names. Teams alreaady participating (if any)
are shown with boxes already checked; uncheck those boxes to remove
those teams from participation.
</p>

<form action=\"${_SERVER['SCRIPT_NAME']}\" class=\"gf\" enctype=\"multipart/form-data\" method=\"POST\">
<input type=\"hidden\" name=\"prid\" value=\"$prid\">
<input type=\"hidden\" name=\"action\" value=\"predit\">
";

  foreach($teams as $id => $team) {
    $ismember = array_key_exists($id, $projteams);
    $checked = $ismember ? ' checked="checked"' : '';
    print "<div style=\"text-align: right; font-weight: bold\">${team['name']}</div>
<div><input type=\"checkbox\" name=\"$id\"$checked></div>
";
  }

  print "<input type=\"submit\" name=\"submit\" value=\"Cancel\">
<input type=\"submit\" name=\"submit\" value=\"Absorb project teams\">
</form>
";
  
} /* end ProjTeams() */


/* AbsorbTeams()
 *
 *  Absorb which teams work on this project.
 */
 
function AbsorbTeams($prid) {
  $teams = GetTeams();
  $pr = GetProject($prid);
  $projteams = GetProjTeams($prid);
  $changes = 0;  
  
  // Loop on checked teams, looking for new ones.

  foreach($_POST as $k => $v)
    if(preg_match('/^\d+$/', $k) && !array_key_exists($k, $projteams)) {
      InsertProjTeam($prid, $k);
      $name = $teams[$k]['name'];      
      print "<p class=\"alert\">Added team <i>$name</i> to <i>{$pr['title']}</i>.</p>\n";
      $changes++;
    }

  // Loop on participating teams, looking for discarded ones.

  foreach($projteams as $k => $v)
    if(!array_key_exists($k, $_POST)) {
      DeleteProjTeam(['id' => $v['id']]);
      $name = $v['name'];
      print "<p class=\"alert\">Removed team <i>$name</i> from <i>{$pr['title']}.</p>\n";
      $changes++;
    }
  if(!$changes)
    print "<p class=\"alert\">No changes.</p>\n";
  return true;
  
} /* end AbsorbTeams() */


/* ProjManagers()
 *
 *  Form for setting managers for a project.
 */

function ProjManagers($id) {
  $projmanagers = GetProjManagers(['projid' => $id]);
  $project = GetProject($id);
  $users = GetUsers();

  // $pms is an array keyed on userid of existing managers.

  $pms = [];
  foreach($projmanagers as $projmanager)
    $pms[$projmanager['userid']] = 1;

  print "<h2>Managers for Project <tt>${project['name']}</tt></h2>

<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\" enctype=\"multipart/form-data\" class=\"mem\">
<input type=\"hidden\" name=\"action\" value=\"predit\">
<input type=\"hidden\" name=\"prid\" value=\"$id\">
<div class=\"memh\">Name</div>
<div class=\"memh\">Username</div>
<div class=\"memh\">Email</div>
<div class=\"memh\">Manager</div>
";

  foreach($users as $user) {
    $checked = (array_key_exists($user['uid'], $pms)) ? ' checked' : '';
    print "<div>${user['fullname']}</div>
<div>${user['username']}</div>
<div>${user['email']}</div>
<div class=\"memc\"><input type=\"checkbox\" name=\"${user['uid']}\" value=\"1\" $checked></div>
";
  }

  print "<div class=\"mems\">
<input type=\"submit\" name=\"submit\" value=\"Absorb managers\">
<input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

} /* end ProjManagers() */


/* AbsorbManagers()
 *
 *  Absorb 'projmanager' settings.
 */

function AbsorbManagers($id) {
  $projmanagers = GetProjManagers(['projid' => $id]);

  # $nmanagers is an array keyed on userid of who will be a manager

  $nmanagers = [];

  # $pms is an array keyed on userid of who is already a manager
  
  $pms = [];
  foreach($projmanagers as $projmanager)
    $pms[$projmanager['userid']] = $projmanager;

  foreach($_POST as $k => $v)
    if(preg_match('/^\d+$/', $k))
      $nmanagers[$k] = $k;

  # Duly insert managers.
  
  $inserts = 0;
  $deletes = 0;
  foreach($nmanagers as $userid) {
    if(!array_key_exists($userid, $pms)) {
      InsertProjManager(['projid' => $id, 'userid' => $userid]);
      $inserts++;
    }
  }
  foreach($pms as $k => $v) {
    if(!array_key_exists($k, $nmanagers)) {
      DeleteProjManager($v['id']);
      $deletes++;
    }
  }
  if($inserts || $deletes)
    print "<p class=\"alert\">Inserted $inserts and deleted $deletes.</p>\n";
  else
    print "<p class=\"alert\">No changes.</p>\n";
  return(true);

} /* end AbsorbManagers() */


if(isset($_POST['submit']) && $_POST['submit'] == 'Cancel') {
  header("Location: ${_SERVER['SCRIPT_NAME']}\n");
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Project Management</title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <script src="project/lib/ps.js"></script>
</head>

<body>

<header>
<h1>Project Management</h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

<?php

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
        $enabled = IsProjectManager($project['id'], $user['id'])
	  || $user['role'] == 'super';
        $tag = $project['tag'] . ($project['active'] ? '' : '*');
        $disabled = $enabled ? '' : ' disabled';
        $options .= "<option value=\"${project['id']}\"$disabled>$tag</option>\n";
      }
    }
    $options .= "</select>\n";
    $form = "<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\">
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
init();
</script>
</div>
<?=FOOT?>
</body>
</html>
