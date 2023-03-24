<?php
#
# NAME
#
#  projpatterns.php
#
# CONCEPT
#
#  Manage the assignment of patterns to projects.
#
# FUNCTIONS
#
#  pmanage     present forms for managing projpatterns
#  passign     absorb projpattern assignments
#  pstatus     set status of projpattern records
#  destination set the destination field of the project
#  deletes     delete projpattern records known to have been assessed
#
# NOTES
#
#  The 'projpattern' table associated patterns with projects. This file
#  contains the UI for managing those associations.
#
#   CREATE TABLE projpattern (
#    id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
#    projid int(10) unsigned NOT NULL COMMENT 'application id',
#    pid int(10) unsigned NOT NULL COMMENT 'pattern id',
#    status enum('candidate','inwork','published') NOT NULL DEFAULT 'candidate',
#    unattr_in int(10) unsigned DEFAULT 0,
#    unattr_out int(10) unsigned DEFAULT 0,
#    PRIMARY KEY (id),
#    UNIQUE KEY appid (projid,pid),
#    KEY pid (pid),
#    CONSTRAINT FOREIGN KEY (projid) REFERENCES project(id),
#    CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern(id)
#   );
#
# $Id: projpatterns.php,v 1.5 2023/03/22 20:39:44 rose Exp $

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST) && isset($_POST['cancel'])) {
  error_log('CANCEL');
  header('Location: ./');
  exit;
}

require "lib/ps.php";

DataStoreConnect();
Initialize();

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(!$user) {
  error_log("${_SERVER['SCRIPT_NAME']}: user not defined; redirecting");
  header('Location: ./');
  exit;
}

$uid = $user['uid'];

if(! $project['id']) {

  // global context - require superuser
  
  if($user['role'] != 'super') {
    header('Location: ./');
    exit;
  }
} else {

  // project context - require superuser or project manager
  
  $pms = GetProjManagers([
   'projid' => $project['id'],
   'userid' => $user['uid']
  ]);
    
  if($user['role'] != 'super' && !isset($pms)) {
    header('Location: ./');
    exit;
  } 
}

/* Authorized. */

function bytitle($a, $b) {
  return strcmp($a['title'], $b['title']);

} /* end bytitle() */


/* pmanage()
 *
 *  Present forms to:
 *
 *   1. Manage which patterns are used in this project.
 *   2. Set a destination language.
 *   3. Set the status of patterns in this project.
 *
 *  The association between a project and a pattern in the project is
 *  established by a row in the 'projpattern' table:
 *
 *   CREATE TABLE projpattern (
 *    id int unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique id',
 *    projid int unsigned NOT NULL COMMENT 'project id',
 *    pid int unsigned NOT NULL COMMENT 'pattern id',
 *    PRIMARY KEY (id),
 *    CONSTRAINT FOREIGN KEY (projid) REFERENCES project (id),
 *    CONSTRAINT FOREIGN KEY (pid) REFERENCES pattern (id)
 *   );
 */

function pmanage() {
  global $project;

  /* First, show a list of the patterns that are associated with the project
   * with the ability to remove the association. */

  // Get all the projpatterns for this project.
 
  $aps = GetProjPatterns($project['id']);
  $apbyid = [];
  $apbyplid = [];
  foreach($aps as $ap) {
    $ap['pacount'] = count(GetPAssessments($ap['apid']));
    $apbyplid[$ap['plid']][] = $ap;
    $apbyid[$ap['id']] = $ap;
  }

  /* Get all the patterns in $pbypid, indexed by pattern.id and $pbyplid,
   *  indexed by pattern.plid. */
  
  $patterns = GetPattern();
  $pbypid = [];
  foreach($patterns as $pattern) {
    $pbypid[$pattern['id']] = $pattern;
    $pbyplid[$pattern['plid']][] = $pattern;
  }

  foreach($pbyplid as $pl => $p) {
    usort($p, 'bytitle');
    $pbyplid[$pl] = $p;
  }

  print '<h2>Set Pattern Assignments</h2>

<p class="alert">' .
    count($pbypid) . " patterns are assignable. " .
    count($apbyid) . " patterns are assigned.</p>

<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\" class=\"gf\">
<input type=\"hidden\" name=\"action\" value=\"passign\">
<div class=\"fieldlabel\"><label for=\"pid\"><b>Select patterns:</b></label></div>
<div>
 <select id=\"pid\" name=\"pid[]\" size=\"12\" multiple>
";

  // Loop on pattern languages, making OPTGROUPs.

  foreach($pbyplid as $plid => $p) {
    $pltitle = $pbyplid[$plid][0]['pltitle'];
    print "<optgroup label=\"$pltitle\">\n";

    // Loop on patterns, making OPTIONs.
    
    foreach($pbyplid[$plid] as $pattern) {
      $extra = '';
      if(isset($apbyid) && array_key_exists($pattern['id'], $apbyid)) {
        $selected = ' selected';
	$ap = $apbyid[$pattern['id']];
	if($ap['pacount'])
          $extra = " (${ap['pacount']} assessments)";	
      } else
        $selected = '';

      print "  <option value=\"${pattern['id']}\"$selected>${pattern['title']}$extra</option>\n";
    }
  }
  print ' </select>
</div>
<div class="gs">
  <input type="submit" name="cancel" value="Cancel">
  <input type="submit" name="submit" value="Absorb assignments">
</div>
</form>

<h2>Select Destination Language</h2>

<p class="alert">You can optionally select a pattern language to be the destination
language for this project.</p>

<form method="POST" action="' . $_SERVER['SCRIPT_NAME'] . '" class="gf">
<input type="hidden" name="action" value="destination">
<div class="fieldlabel">Destination language:</div>
<div>
 <select name="destination">
  <option value="0">Select</option>
';

  $planguages = GetPLanguage();
  $destination = $project['destination'];
  
  foreach($planguages as $planguage) {
    $title = $planguage['title'];
    $id = $planguage['id'];
    $selected = ($destination == $id)
     ? ' selected' : '';
    print "  <option value=\"$id\"$selected>$title</option>\n";
  }
  print '</select>
</div>
<div class="gs">
 <input type="submit" name="cancel" value="Cancel">
 <input type="submit" name="submit" value="Set destination">
</div>
</form>
';

  if(count($aps)) {
    $statuses = [
      'candidate' => 'candidate',
      'inwork' => 'in work',
      'published' => 'published'
    ];

    /* Offer a form for setting the status of each projpattern record - iff
     * any exist. */

    print("<h2>Set Pattern Status</h2>

<p class=\"alert\">Use this form to set the status of patterns.</p>

<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\" class=\"gf\">
<input type=\"hidden\" name=\"action\" value=\"pstatus\">
");
    foreach($aps as $ap) {
      print "<div>${ap['title']}</div>
<div><select name=\"${ap['apid']}\">
";
      foreach($statuses as $k => $v) {
        $selected = ($ap['status'] == $k) ? ' selected' : '';
        print "<option value=\"$k\"$selected>$v</option>\n";
      }
      print "</select>
</div>
";
    }
    print '<div class="gs">
  <input type="submit" name="cancel" value="Cancel">
  <input type="submit" name="submit" value="Absorb status">
</div>
</form>
';
  } // end status form
  
} /* end pmanage() */


/* passign()
 *
 *  Absorb pattern assignments.
 *
 *  pmanage() presents the user a form with a single popup menu named
 *  'pid'.  Each element in that menu has a pattern.id value for a
 *  name. Selected elements result in a POST variable, while
 *  unselected elements do not. The task of this function is to
 *  compare the set of selected form elements with the set of existing
 *  projpattern records to determine a set of INSERT and DELETE
 *  actions.
 */

function passign() {
  global $project;
  
  $aps = GetProjPatterns($projid = $project['id'], true);

  $inserts = [];
  $deletes = [];
  
  /* Loop on patterns selected in the form, looking for new ones and
   * issuing INSERTs for them. */

  foreach($_POST['pid'] as $pid) {
    if(!isset($aps[$pid])) {
      $inserts[] = $pid;
      InsertProjPattern(['projid' => $projid, 'pid' => $pid]);
    }
  }
  print '<p class="alert">' . count($inserts) . ' new pattern assignments.</p>
';

  // Refresh the list of projpatterns.
   
  $aps = GetProjPatterns($projid = $project['id'], true);

  // Loop on projpatterns, looking for those no longer selected.

  $defers = [];
  foreach($aps as $pid => $ap) {
    if(!in_array($pid, $_POST['pid'])) {
      $apid = $ap['apid'];
      $pas = GetPAssessments($apid);
      if(count($pas)) {
        $defers[] = $pid;
      } else {
        $deletes[] = $pid;
        DeleteProjPattern(['projid' => $projid, 'pid' => $pid]);
      }
    }
  }
  print '<p class="alert">' . count($deletes) . ' pattern assignments removed.</p>
';
  if(count($defers)) {
    print "<p class=\"alert\">The following patterns that you have marked for deletion have been assessed:</p>
<ul>
";
    foreach($defers as $defer) {
      $ap = $aps[$defer];
      print "<li>${ap['title']}</li>\n";
    }
    print "</ul>

<p class=\"alert\">Deleting these patterns will delete those assessments. Delete anyway?</p>
<form method=\"POST\" action=\"${_SERVER['SCRIPT_NAME']}\">
<input type=\"hidden\" name=\"action\" value=\"delete\">
";
    foreach($defers as $defer)
      print "<input type=\"hidden\" name=\"pid[]\" value=\"$defer\">\n";
      
    print "<input style=\"margin-left: 2em\" type=\"submit\" name=\"cancel\" value=\"Cancel\">
<input type=\"submit\" name=\"submit\" value=\"Delete patterns and assessments\">
</form>
";
    return(false);
  }
  return(true);

} /* end passign() */


/* pstatus()
 *
 *  Absorb form input to set the status of projpatterns.
 */

function pstatus() {
  global $project;
  
  $aps = GetProjPatterns($project['id']);
  foreach($aps as $ap)
    $apbyid[$ap['apid']] = $ap;
  
  $updates = array();
  
  foreach($_POST as $id => $status) {

    if(!preg_match('/^\d+$/', $id))
      continue;
    
    error_log("$id => $status");

    if($apbyid[$id]['status'] != $status)
      $updates[$id] = $status;
  }

  if(count($updates)) {
    foreach($updates as $id => $status)
      UpdateProjPattern(['id' => $id, 'status' => $status]);
    print '<p class="alert">' . count($updates) . " updates.</p>\n";
  } else
    print "<p class=\"alert\">No changes.</p>\n";

  return(false);
    
} /* end pstatus() */


/* destination()
 *
 *  Set the 'destination' field of the 'project' record.
 */
 
function destination() {
  global $project;
  
  $destination = $_POST['destination'];
  if($destination != $project['destination']) {
    UpdateProject([
      'id' => $project['id'],
      'destination' => $destination
     ]);
    print '<p class="alert">Destination updated.</p>
';
  } else
    print '<p class="alert">Destination unchanged.</p>
';
  return(true);

} /* end destination() */


/* Deletes()
 *
 *  Delete projpattern records known to have been assessed.
 */

function deletes() {
  global $project;

  print "<p class=\"alert\">Deleted these patterns from the project:</p>
<ul>
";

  foreach($_POST['pid'] as $pid) {
    DeleteProjPattern([
      'projid' => $project['id'],
      'pid' => $pid
    ]);
    $p = GetPattern(['p.id' => $pid]);
    $p = $p[0];
    print "<li>${p['title']} / ${p['pltitle']}</li>\n";
  }
  print "</ul>\n";
  
  return(true);
  
} /* end deletes() */


?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title><?=$project['title']?> : Pattern Management</title>
 <link rel="stylesheet" href="lib/ps.css">
 <script src="lib/ps.js"></script>
</head>

<body>

<header>
<h1><?=$project['title']?>: Manage Patterns</h1>
</header>

<div id="poutine">
<img src="../images/pattern-sphere-band.png" id="gravy">

<?php

$rv = true;

if(DEBUG && isset($_POST)) error_log(var_export($_POST, true));

if(isset($_POST) && isset($_POST['action'])) {
  if($_POST['action'] == 'passign') /* absorb projpattern assignments */
    $rv = passign();
  elseif($_POST['action'] == 'pstatus') /* set projpattern statuses */
    pstatus();
  elseif($_POST['action'] == 'destination') /* set destination language */
    destination();
  elseif($_POST['action'] == 'delete') /* process confirmed deletes */
    deletes();
}

/* If $rv is false, pmanage() isn't called. That's the case when passign()
 * finds projpattern deletions that would also delete passessments. */

if($rv) {

  /* present forms */

  pmanage();
}

?>
<p><a href="./">Return to project</a></p>

<script>
init();
</script>
</div>
<?=FOOT?>
</body>
</html>
