<?php
#
# NAME
#
#  teameditor.php
#
# CONCEPT
#
#  Create/edit teams - metadata, memberships, and roles.
#
# FUNCTIONS
#
#  teamform      present a form for editing or creating a team
#  AbsorbCreate  create a new organization from form input
#  AbsorbEdit    edit an existing team from form input
#  Members       present a form for managing team membership
#  AbsorbMember  absorb input from Members()
#
# NOTES
#
#  Creating teams requires super privilege. Editing teams, team membership,
#  and team roles requires the manager 'role' in those teams.
#
#  CREATE TABLE team (
#   id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
#   name varchar(255) NOT NULL COMMENT 'team name',
#   created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when created',
#   PRIMARY KEY (id),
#  ) COMMENT 'team metadata';
#
#  CREATE TABLE teammember (
#   id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
#   userid int(11) NOT NULL COMMENT 'user id',
#   teamid int(10) unsigned NOT NULL COMMENT 'team id',
#   role enum('user','manager') NOT NULL DEFAULT 'user',
#   PRIMARY KEY (id),
#   KEY userid (userid),
#   KEY teamid (teamid),
#   CONSTRAINT FOREIGN KEY (userid) REFERENCES phpauth_users (id),
#   CONSTRAINT FOREIGN KEY (teamid) REFERENCES team (id)
#  ) COMMENT='ties a user to a team';
#
# $Id: teameditor.php,v 1.4 2023/03/22 20:43:25 rose Exp $

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

if(!$user) {
  header('Location: ./');
  exit;
}
if($user['role'] != 'super') {
  $managed = ManagedTeams($user['uid']);
  if(! count($managed)) {
    header('Location: ./');
    exit;
  }
}

$fields = [
  [
    'name' => 'name',
    'label' => 'Team title',
    'type' => 'text',
    'maxlength' => 255,
    'size' => 60
  ],
];


/* teamform()
 *
 *  Present a form for creating ($id is null) or editing a team.
 */

function teamform($id = null) {
  global $fields;

  if(isset($id)) {
    $team = GetTeam($id);

    # edit existing
    
    $action = 'edit';
    $slabel = 'Absorb edits';
    $title = "Editing Team <span style=\"color: #666\">${team['name']}</span>";
    $id = "<input type=\"hidden\" name=\"id\" value=\"${team['id']}\">
<input type=\"hidden\" name=\"action\" value=\"edit\">
";
  } else {

    # Create new.

    $title = 'Creating Team';
    $action = 'create';
    $slabel = 'Create Team';
  }
  print "<h2>$title</h2>

<form method=\"post\" action=\"${_SERVER['SCRIPT_NAME']}\" class=\"gf\" enctype=\"multipart/form-data\" >
$id
<input type=\"hidden\" name=\"action\" value=\"$action\">
";

  foreach($fields as $field) {
  
    $value = $team[$field['name']];
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
 *  Create a new team from form input.
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

  $id = InsertTeam($row);
  print "<p class=\"alert\">Created a new team <tt>${row['name']}</tt> with ID <tt>$id</tt>.</p>\n";
  return true;
  
} /* end AbsorbCreate() */


/* AbsorbEdit()
 *
 *  Absorb team edits from form input.
 */
 
function AbsorbEdit() {
  global $fields;
  
  $update = [];
  foreach($fields as $field) {
    $name = $field['name'];
    if($field['type'] == 'checkbox')
      $update[$name] = is_null($_POST[$name]) ? 0 : 1;
    else
      $update[$field['name']] = $_POST[$field['name']];
  }
  $update['id'] = $_POST['id'];    
 
  if(UpdateTeam($update))
    print "<p class=\"alrt\">Updated team <tt>${update['name']} (ID ${update['id']})</tt>.</p>\n";
  else
    print "<p class=\"alert\">No updates to team <tt>${update['name']}</tt>.</p>\n";
  return true;
  
} /* end AbsorbEdit() */


/* Members()
 *
 *  Present a form for editing the membership of a team.
 *
 *  We provide two checkboxes for each user: one for membership, named with
 *  the user UID, and a second for management, named with an 'm' followed by
 *  the user UID.
 */

function Members($id) {
  $team = GetTeam($id);
  $members = GetTeamMembers($id);
  $users = GetUsers();
  
  print "<h2>Membership Management for Team <tt>${team['name']}</tt></h2>
  
<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\" enctype=\"multipart/form-data\" class=\"mem\">
<input type=\"hidden\" name=\"action\" value=\"edit\">
<input type=\"hidden\" name=\"id\" value=\"$id\">
<div class=\"memh\">Name</div>
<div class=\"memh\">Username</div>
<div class=\"memh\">Email</div>
<div class=\"memh\">Member</div>
<div class=\"memh\">Manager</div>
";

  foreach($users as $uid => $user) {
    if(array_key_exists($uid, $members)) {
      $member = $members[$uid];
      $mchecked = ($member['role'] == 'manager') ? ' checked' : '';
      $checked =  ($member['role'] == 'user') ? ' checked' : '';
    } else {
      $checked = $mchecked = '';
    }
    print "<div>${user['fullname']}</div>
<div>${user['username']}</div>
<div>${user['email']}</div>
<div class=\"memc\"><input type=\"checkbox\" value=\"1\" name=\"${user['uid']}\"$checked></div>
<div class=\"memc\"><input type=\"checkbox\" value=\"1\" name=\"m${user['uid']}\"$mchecked></div>
";
  }
  print "<div class=\"mems\">
 <input type=\"submit\" name=\"submit\" value=\"Absorb Membership\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  
} /* end Members() */


/* AbsorbMember()
 *
 *  Absorb team memberships.
 */

function AbsorbMember($id) {
  $members = GetTeamMembers($id);

  // Collect all the userids and roles in $nmembers.

  $nmembers = [];
  foreach($_POST as $k => $v) {
    if(preg_match('/^\d+$/', $k) && !isset($nmembers[$k])) {
      $nmembers[$k] = 'user';
    } elseif(preg_match('/^m(\d+)$/', $k, $matches)) {
      $userid = $matches[1];
      $nmembers[$userid] = 'manager';
    }
  }

  // Duly insert or update members.
  
  $inserts = 0;
  $updates = 0;
  $deletes = 0;
  foreach($nmembers as $userid => $role) {
    if(array_key_exists($userid, $members)) {
      if($members[$userid]['role'] != $role) {
        UpdateTeamMember($id, $userid, $role);
	$updates++;
      }
    } else {
      InsertTeamMember($id, $userid, $role);
      $inserts++;
    }
  }

  // Duly delete members.

  foreach($members as $member)
    if(!array_key_exists($member['userid'], $nmembers)) {
      DeleteTeamMember(['teamid' => $id, 'userid' => $member['userid']]);
      $deletes++;
    }
  if($inserts || $deletes || $updates)
    print "<p class=\"alert\">Inserted $inserts, updated $updates, deleted $deletes.</p>\n";
  else
    print "<p class=\"alert\">No changes.</p>\n";

  return(true);
  
} /* end AbsorbMember() */


if(isset($_POST['submit']) && $_POST['submit'] == 'Cancel') {
  header("Location: ${_SERVER['SCRIPT_NAME']}\n");
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Team Management</title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <script src="project/lib/ps.js"></script>
 <style type="text/css">
  .mem {
   display: grid;
   grid-template-columns: repeat(5, auto);
   margin-left: 2em;
   margin-bottom: 2em;
   margin-top: 1em;
   width: max-content;
   background-color: #ffd;
   border: 2px solid #322;
  }
  .mem div {
   border: 1px solid #322;
   padding: .2em;
  }
  .memc {
    text-align: center;
  }
  .mems {
    text-align: center;
    grid-column: span 5;
  }
  .memh {
    font-weight: bold;
    text-align: center;
    background-color: #fcc;
  }
 </style>
</head>

<body>

<header>
<h1>Team Management</h1>
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
  if($action == 'create') {
    if(isset($_POST['submit']) && $_POST['submit'] == 'Create Team') {
      $rv = AbsorbCreate();
    } else {
      $rv = teamform();
    }
  } else if($action == 'edit') {
    $id = $_REQUEST['id'];
    if($id < 0)
      Error('Select a team to edit');
      
    if(isset($_POST['submit'])) {
      if($_POST['submit'] == 'Absorb edits') {
        $rv = AbsorbEdit();
      } elseif($_POST['submit'] == 'Edit team membership') {
        $rv = Members($id);
      } elseif($_POST['submit'] == 'Absorb Membership') {
        $rv = AbsorbMember($id);
      } else {
        $rv = teamform($id);
      }
    } else {
      Error('Unknown action');
    }
  }
}
if($rv) {

  /* Build a popup menu of teams, if any. */
  
  $teams = GetTeams();

  if(count($teams)) {
    $form = "<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\">
<select name=\"id\">
<option value=\"-1\" selected>Choose team</option>
";
    foreach($teams as $team) {
      if(isset($managed) && ! in_array($team['id'], $managed))
        continue; // a team not managed by this user
      if($team['id'])
        $name = $team['name'];
      $form .= "<option value=\"${team['id']}\">$name</option>\n";
    }
    $form .= "</select>
<input type=\"submit\" name=\"submit\" value=\"Edit this team\">
<input type=\"submit\" name=\"submit\" value=\"Edit team membership\">
<input type=\"hidden\" name=\"action\" value=\"edit\">
  </form>
";
  } else
    $form = '<br>(No teams defined yet.)';
?>
<h3>Actions:</h3>
<ul>
<?php
  if($user['role'] == 'super')
    print " <li><a href=\"?action=create\">Create a team</a></li>\n";
  
  print "<li><b>Edit a team</b> $form</li>
 <li><a href=\"./\">Return</a></li>
</ul>
";
}
?>
<script>
init();
</script>
</div>
<?=FOOT?>
</body>
</html>
