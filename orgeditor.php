<?php
#
# NAME
#
#  orgeditor.php
#
# CONCEPT
#
#  Create/edit organizations
#
# FUNCTIONS
#
#  orgform       present a form for editing or creating an organization
#  AbsorbCreate  create a new organization from form input
#  AbsorbEdit    edit an existing organization from form input
#
# NOTES
#
#  Creating and editing organizations currently requires top privilege.
#  Future work: define owners of each organization that can edit
#  them.
#
#  CREATE TABLE organization (
#   id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
#   name varchar(255) NOT NULL COMMENT 'organization name',
#   created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'when created',
#   mission varchar(80) NOT NULL,
#   PRIMARY KEY (id),
#  ) COMMENT 'organization metadata';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST) && isset($_POST['cancel']))
  header('Location: ./');

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);
if($user['role'] == 'super')
  $orgs = GetOrganizations();
else
    $orgs = ManagedOrgs($user['id']);

if(!$user || ($user['role'] != 'super' && !count($orgs))) {

  /* not logged in, or neither are a super nor org manager */

  header('Location: ./');
  exit;
}

$fields = [
  [
    'name' => 'name',
    'label' => 'Organization title',
    'type' => 'text',
    'maxlength' => 255,
    'size' => 60
  ],
  [
    'name' => 'mission',
    'label' => 'Organization mission',
    'type' => 'textarea',
  ]
];


/* orgform()
 *
 *  Present a form for creating ($orgid is null) or editing an organization.
 */

function orgform($orgid = null) {
  global $fields, $org;

  $pnames = '';
  
  if(isset($orgid)) {
    $org = GetOrganization($orgid);

    # edit existing
    
    $action = 'orgedit';
    $slabel = 'Absorb edits';
    
    # For an organization without projects, offer a delete option.

    $projects = GetProjects(['orgid' => $orgid]);

    if(count($projects)) {
      foreach($projects as $project) {
        if(strlen($pnames))
          $pnames .= ', ';
        $pnames .= "<i>{$project['title']}</i>";
      }
      $pnames = "<p>This organization has these projects: $pnames</p>\n";
    } else
      $pnames = "<p>This organization has no projects.</p>\n";
    $disabled = count($projects) ? 'disabled' : '';
    $delete = "<input type=\"submit\" $disabled name=\"submit\" value=\"Delete\">";
    $title = "Editing Organization <span style=\"font-style: oblique\">{$org['name']}</span>";
    $orgid = "<input type=\"hidden\" name=\"orgid\" value=\"{$org['id']}\">
<input type=\"hidden\" name=\"action\" value=\"edit\">
";
  } else {

    # Create new. Start with the contents of the template organization.

    $org = GetOrganization(0);
    $title = 'Creating Organization';
    $action = 'orgcreate';
    $slabel = 'Create Organization';
    $delete = '';
  }
  print "<h2>$title</h2>
$pnames
<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\"enctype=\"multipart/form-data\" >
$orgid
<input type=\"hidden\" name=\"action\" value=\"$action\">
";

  foreach($fields as $field) {
  
    $value = $org[$field['name']];
    print "<div class=\"fieldlabel\"><label for=\"{$field['name']}\">{$field['label']}:</label></div>\n";
    
    if($field['type'] == 'text') {
      print "<div><input type=\"text\" name=\"{$field['name']}\" size=\"{$field['size']}\" maxlength=\"{$field['maxlength']}\" value=\"$value\"></div>\n";
    } elseif($field['type'] == 'textarea') {
      print "<div><textarea name=\"{$field['name']}\" rows=\"4\" cols=\"80\">$value</textarea></div>\n";
    } elseif($field['type'] == 'checkbox') {
      $checked = $value ? ' checked="checked"' : '';
      print "<div><input type=\"checkbox\" value=\"1\" name=\"{$field['name']}\"$checked></div>\n";
    } elseif($field['type'] == 'image') {
      print "<div><input type=\"file\" name=\"{$field['name']}\"></div>\n";
    } elseif($field['type'] == 'popup_menu') {
      print $field['callback']();
    }
  } // end loop on fields

  print "<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"$slabel\">
 $delete
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  return(false);

} /* end prform() */


/* AbsorbCreate()
 *
 *  Create a new organization from form input.
 */
 
function AbsorbCreate() {
  global $fields, $user;

  if($user['role'] != 'super')
    Error('Unauthorized');
  
  $row = [];
  foreach($fields as $field) {
    $name = $field['name'];
    if($field['type'] == 'checkbox')
      $row[$name] = is_null($_POST[$name]) ? 0 : 1;
    else
      $row[$field['name']] = $_POST[$field['name']];
  }

  $id = InsertOrganization($row);
  print "<p class=\"alert\">Created a new organization <tt>{$row['name']}</tt> with ID <tt>$id</tt>.</p>\n";
  return true;
  
} /* end AbsorbCreate() */


/* AbsorbEdit()
 *
 *  Absorb organization edits from form input.
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
  $update['id'] = $_POST['orgid'];    
 
  if(UpdateOrganization($update))
    print "<p class=\"alrt\">Updated organization <tt>{$update['name']} (ID {$update['id']})</tt>.</p>\n";
  else
    print "<p class=\"alert\">No updates to organization <tt>{$update['name']}</tt>.</p>\n";
  return true;
  
} /* end AbsorbEdit() */


/* OrgManagers()
 *
 *  Form for setting managers for an organization.
 */

function OrgManagers($id) {
  $orgmanagers = GetOrgManagers(['orgid' => $id]);
  $organization = GetOrganization($id);
  $users = GetUsers();

  // $oms is an array keyed on userid of existing managers.

  $oms = [];
  foreach($orgmanagers as $orgmanager)
    $oms[$orgmanager['userid']] = 1;

  print "<h2>Managers for Organization <tt>{$organization['name']}</tt></h2>

<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" enctype=\"multipart/form-data\" class=\"mem\">
<input type=\"hidden\" name=\"action\" value=\"orgedit\">
<input type=\"hidden\" name=\"orgid\" value=\"$id\">
<div class=\"memh\">Name</div>
<div class=\"memh\">Username</div>
<div class=\"memh\">Email</div>
<div class=\"memh\">Manager</div>
";

  foreach($users as $user) {
    $checked = (array_key_exists($user['uid'], $oms)) ? ' checked' : '';
    print "<div>{$user['fullname']}</div>
<div>{$user['username']}</div>
<div>{$user['email']}</div>
<div class=\"memc\"><input type=\"checkbox\" name=\"{$user['uid']}\" value=\"1\" $checked></div>
";
  }

  print "<div class=\"mems\">
<input type=\"submit\" name=\"submit\" value=\"Absorb managers\">
<input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

} /* end OrgManagers() */


/* AbsorbManagers()
 *
 *  Absorb 'orgmanager' settings.
 */

function AbsorbManagers($id) {
  $orgmanagers = GetOrgManagers(['orgid' => $id]);

  # $nmanagers is an array keyed on userid of who will be a manager

  $nmanagers = [];

  # $oms is an array keyed on userid of who is already a manager
  
  $oms = [];
  foreach($orgmanagers as $orgmanager)
    $oms[$orgmanager['userid']] = $orgmanager;

  foreach($_POST as $k => $v)
    if(preg_match('/^\d+$/', $k))
      $nmanagers[$k] = $k;

  # Duly insert managers.
  
  $inserts = 0;
  $deletes = 0;
  foreach($nmanagers as $userid) {
    if(!array_key_exists($userid, $oms)) {
      InsertOrgManager(['orgid' => $id, 'userid' => $userid]);
      $inserts++;
    }
  }
  foreach($oms as $k => $v) {
    if(!array_key_exists($k, $nmanagers)) {
      DeleteOrgManager($v['id']);
      $deletes++;
    }
  }
  if($inserts || $deletes)
    print "<p class=\"alert\">Inserted $inserts and deleted $deletes.</p>\n";
  else
    print "<p class=\"alert\">No changes.</p>\n";
  return(true);

} /* end AbsorbManagers() */


if(isset($_POST['submit']) && $_POST['submit'] == 'Cancel')
  header("Location: {$_SERVER['SCRIPT_NAME']}\n");

?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Organization Management</title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <script src="project/lib/ps.js"></script>
</head>

<body>

<header>
<h1>Organization Management</h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

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
  if($action == 'orgcreate') {
    if(isset($_POST['submit']) && $_POST['submit'] == 'Create Organization') {
      $rv = AbsorbCreate();
    } else {
      $rv = orgform();
    }
  } else if($action == 'orgedit') {
    $orgid = $_REQUEST['orgid'];
    if($orgid < 0)
      Error('Select an organization to edit');
    if(isset($_POST['submit'])) {
      $submit = $_POST['submit'];
      if($submit == 'Absorb edits')
	$rv = AbsorbEdit();
      elseif($submit == 'Organization managers')
	$rv = OrgManagers($orgid);
      elseif($submit == 'Delete')
	$rv = DeleteOrganization($orgid);
      elseif($submit == 'Absorb managers')
	$rv = AbsorbManagers($orgid);
    } else
      $rv = orgform($orgid);
  } else {
    Error('Unknown action');
  }
}
if($rv) {

  /* Build a popup menu of organizations, if any. */

  if(count($orgs)) {
    $options = "<select name=\"orgid\">
<option value=\"-1\" selected>Choose organization</option>
";
    foreach($orgs as $org) {
      if($org['id'])
        $name = $org['name'];
      else
        $name = 'organization template';
      $options .= "<option value=\"{$org['id']}\">$name</option>\n";
    }
    $options .= "</select>\n";
    $form = "<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\">
  <input type=\"hidden\" name=\"action\" value=\"orgedit\">
$options
  <input type=\"submit\" name=\"submit\" value=\"Edit this organization\">
  <input type=\"submit\" name=\"submit\" value=\"Organization managers\">
  </form>
";
  } else
    $form = "<p class=\"alert\" style=\"margin-left: 1em\">No organizations exist.</p>\n";

  print '<h3>Actions:</h3>
<ul>

' . (($user['role'] == 'super') ? ' <li><a href="?action=orgcreate">Create an organization</a></li>
<li class="spacer"></li>' : '') . "
 <li><b>Edit an organization</b>
$form
 </li>
 <li><a href=\"./\">Return</a></li>
</ul>
";
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
