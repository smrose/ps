<?php
# NAME
#
#  project.php
#
# CONCEPT
#
#  Functions for managing projects.
#
# FUNCTIONS
#
#  dmenu          callback for destination language popup
#  omenu          callback for organization popup
#  prform         present a form for editing or creating a project
#  labels         process labels, values, and vcolors
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
#   unattr_voter int(10) unsigned DEFAULT 0,
#   orgid int(10) unsigned DEFAULT NULL,
#   nulllabel varchar(20) NOT NULL DEFAULT 'neutral/not assessed' COMMENT 'how null assessments are labeled',
#   labels varchar(255) NOT NULL DEFAULT 'out:in' COMMENT 'how assessments are labeled',
#   vcolors varchar(255) DEFAULT '#080:#0f0',
#   lvalues varchar(255) NOT NULL DEFAULT '2:3',
#   PRIMARY KEY (id),
#   UNIQUE KEY tag (tag),
#   CONSTRAINT FOREIGN KEY(destination) REFERENCES planguage(id)
#  ) COMMENT='project metadata';

/* $fields is used to build the form. The 'callback' field, when present,
 * is the name of a function that is called to build the form element for
 * that field. */

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
    'name' => 'labels',
    'label' => 'Labels',
    'type' => 'labels',
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
    $title = "Editing Project <span style=\"font-style: oblique\">{$pr['title']}</span>";
    $prid = "<input type=\"hidden\" name=\"prid\" value=\"{$pr['id']}\">
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

<p>Label/value/color triads are used to determine how the assessments are
presented and scored. The labels are assigned to radio buttons in the
assessment form. The values are used to determine how the assessments
are weighted in scoring each pattern. The colors are used in presenting
assessments and results.</p>

<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\" class=\"gf\"enctype=\"multipart/form-data\" >
$prid
<input type=\"hidden\" name=\"action\" value=\"$action\">
";

  foreach($fields as $field) {
  
    $value = $pr[$field['name']];
    $flabel = "<div class=\"fieldlabel\"><label for=\"{$field['name']}\">{$field['label']}:</label></div>\n";
    
    if($field['type'] == 'text') {
      print "$flabel<div><input type=\"text\" name=\"{$field['name']}\" size=\"{$field['size']}\" maxlength=\"{$field['maxlength']}\" value=\"$value\"></div>\n";
    } elseif($field['type'] == 'textarea') {
      print "$flabel<div><textarea name=\"{$field['name']}\" rows=\"4\" cols=\"80\">$value</textarea></div>\n";
    } elseif($field['type'] == 'checkbox') {
      $checked = $value ? ' checked="checked"' : '';
      print "$flabel<div><input type=\"checkbox\" value=\"1\" name=\"{$field['name']}\"$checked></div>\n";
    } elseif($field['type'] == 'image') {
      print "$flabel<div><input type=\"file\" name=\"{$field['name']}\"></div>\n";
    } elseif($field['type'] == 'popup_menu') {
      print $flabel . $field['callback']();
    } elseif($field['type'] == 'labels') {

      /* Assessment labels e.g. [out, in] and associated weights e.g. [2, 3],
       * and colors e.g. [#080, #0f0]. */

      $labels = explode(':', $value);
      $lvalues = explode(':', $pr['lvalues']);
      $vcolors = explode(':', $pr['vcolors']);

      $count = 1;

      foreach($labels as $label) {
        $l = $labels[$count-1];
	$v = $lvalues[$count-1];
	$c = $vcolors[$count-1];
        print "<div class=\"fieldlabel\">Label/value/color $count:</div>
<div>
 <input type=\"text\" name=\"l$count\" style=\"background-color: $c\" size=\"20\" value=\"$l\">
 <input type=\"text\" name=\"v$count\" style=\"background-color: $c\" size=\"4\" value=\"$v\">
 <input type=\"text\" name=\"c$count\" style=\"background-color: $c\" size=\"4\" value=\"$c\">
</div>
";
	$count++;
	
      } // end loop on labels

      print "<div id=\"newld\">
 <input type=\"button\" value=\"Add another\" id=\"newl\">
</div>
";
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


/* labels()
 *
 *  $_POST[l[1..n]] => project.labels
 *  $_POST[v[1..n]] => project.lvalues.
 *  $_POST[c[1..n]] => project.vcolors.
 */

function labels() {
  $labels = [];
  $values = [];
  $vcolors = [];
  
  for($i = 1; 1; $i++) {
    $l = strtolower(trim($_POST["l$i"]));
    $v = trim($_POST["v$i"]);
    $c = strtolower(trim($_POST["c$i"]));
    if(!isset($l) || !strlen($l) )
      break;

    // check that the label is unique

    if(in_array($l, $labels))
      Error("Duplicate labels are not allowed (<tt>$l</tt>).");

    // check that the value is an integer
    
    if(! preg_match('/^\d+$/', $v))
      Error("Values must be integers (<tt>$v</tt>)");

    $labels[] = $l;
    $values[] = $v;
    $vcolors[] = $c;
  }
  $ls = implode(':', $labels);
  $vs = implode(':', $values);
  $cs = implode(':', $vcolors);
  return [$ls, $vs, $cs];
     
} /* end labels() */


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
    elseif($field['type'] == 'labels')
      [$row['labels'], $row['lvalues'], $row['vcolors']] = labels();
    else
      $row[$field['name']] = $_POST[$field['name']];
  }

  $id = InsertProject($row);
  print "<p class=\"alert\">Created a new project <tt>{$row['title']}</tt> with ID <tt>$id</tt>.</p>\n";
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
    elseif($field['type'] == 'labels')
      [$update['labels'], $update['lvalues'], $update['vcolors']] = labels();
    else
      $update[$field['name']] = $_POST[$field['name']];
  }
 
  if(UpdateProject($update))
    print "<p class=\"alert\">Updated project <tt>{$update['title']} (ID {$update['id']})</tt>.</p>\n";
  else
    print "<p class=\"alert\">No updates to project <tt>{$update['title']}</tt>.</p>\n";
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
  $title = "Editing <span style=\"font-style: oblique\">{$pr['title']}</span> Teams";
  
  print "<h2>$title</h2>

<p class=\"alert\">
Select which teams shall participate in this project by checking the
boxes next to the team names. Teams alreaady participating (if any)
are shown with boxes already checked; uncheck those boxes to remove
those teams from participation.
</p>

<form action=\"{$_SERVER['PHP_SELF']}\" class=\"gf\" enctype=\"multipart/form-data\" method=\"POST\">
<input type=\"hidden\" name=\"prid\" value=\"$prid\">
<input type=\"hidden\" name=\"action\" value=\"predit\">
";

  foreach($teams as $id => $team) {
    $ismember = array_key_exists($id, $projteams);
    $checked = $ismember ? ' checked="checked"' : '';
    print "<div style=\"text-align: right; font-weight: bold\">{$team['name']}</div>
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

  print "<h2>Managers for Project <tt>{$project['name']}</tt></h2>

<form method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\" enctype=\"multipart/form-data\" class=\"mem\">
<input type=\"hidden\" name=\"action\" value=\"predit\">
<input type=\"hidden\" name=\"prid\" value=\"$id\">
<div class=\"memh\">Name</div>
<div class=\"memh\">Username</div>
<div class=\"memh\">Email</div>
<div class=\"memh\">Manager</div>
";

  foreach($users as $user) {
    $checked = (array_key_exists($user['uid'], $pms)) ? ' checked' : '';
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
