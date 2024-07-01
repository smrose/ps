<?php
/* NAME
 *
 *  users.php
 *
 * CONCEPT
 *
 *  User management.
 *
 * FUNCTIONS
 *
 *  Manage       present the form for managing a user
 *  AbsorbManage absorb edit user form input
 *  Create       present the form for creating a user
 *  AbsorbCreate absorb user creation form input
 *  Upload       present the form for uploading a CSV file
 *  DoUpload     absorb an upload
 *
 * NOTES
 *
 *  This page can be accessed in two ways: by a superuser in a global
 *  context, which allows users to be created, deleted, assessments to
 *  be deleted, and memberships in any projects managed, or, in a project
 *  context, by a project manager, who can manage memberships in the project
 *  and delete assessments.
 *
 *  Arriving here without a suitable role silently redirects to the top of
 *  the project.
 *
 *  We offer a form for selecting a user on which to operate. That's
 *  implemented by a link with the user UID as the query parameter.
 *  That page offers a form in which the user's fullname, email, activation
 *  status, and role - but not UID - can be edited by POSTing the form.
 *  That form also offers a submit button for performing a forced delete.
 *
 * $Id: users.php,v 1.23 2023/03/22 20:43:25 rose Exp $
 */
 

/* Manage()
 *
 *  Present the form for managing a user.
 */

function Manage($uid) {
  global $auth, $project;
  
  $user = $auth->getUser($uid);

  // allow the selection of super role

  $role = 'Super';
  $roles = '<input type="checkbox" name="super" value="1" ' .
    (($user['role'] == 'super') ? ' checked="checked"' : '') .
    '>';

  // present a list of teams as a set of checkboxes
  
  $teams = GetTeams();
  $tm = UserTeams($user['id']);

  $p = '';
    
  // loop on teams, showing membership and role

  $p = '<div class="fieldlabel">Teams:</div>
<div class="three">
';

  foreach($teams as $tid => $team) {
    if(!$tid)
      continue;
    $mbrchecked = $mgrchecked = '';
    if(array_key_exists($tid, $tm)) {
      if($tm[$tid]['role'] == 'manager')
        $mgrchecked = ' checked';
      else
        $mbrchecked = ' checked';
    }    
    $p .= "<div style=\"font-weight: bold\">{$team['name']}</div>
<div><input type=\"checkbox\" name=\"$tid\" value=\"1\"$mbrchecked>member</div>
<div><input type=\"checkbox\" name=\"m$tid\" value=\"1\"$mgrchecked>manager</div>
";
  }
  $p .= "</div>
</div>
";

  $da = '';

  $isactive = '<input type="checkbox" name="isactive" value="1"' .
   ($user['isactive'] ? ' checked="checked"' : '') . '>';

  print '
<p class="alert">Here, you can edit metadata for this user, edit their
membership in teams, or delete them entirely. Press "Cancel" here to
leave the user as they were.</p>

<p>Deleting a user will discard all of their assessments (if any).</p>
';

  print "<form action=\"users.php\" method=\"POST\" class=\"gf\">
<div class=\"fieldlabel\">UID:</div>
<div><input type=\"hidden\" name=\"uid\" value=\"{$user['uid']}\">{$user['uid']}</div>
<div class=\"fieldlabel\">Email:</div> <div><input type=\"text\" name=\"email\" value=\"{$user['email']}\"></div>
<div class=\"fieldlabel\">Fullname:</div> <div><input type=\"text\" name=\"fullname\" value=\"{$user['fullname']}\"></div>
<div class=\"fieldlabel\">Username:</div> <div><input type=\"text\" name=\"username\" value=\"{$user['username']}\"></div>
<div class=\"fieldlabel\">Active:</div> <div>$isactive</div>
<div class=\"fieldlabel\">$role:</div> <div>$roles</div>
$p
<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"Apply changes\">
 <input type=\"submit\" name=\"submit\" value=\"Delete user\">
 $da
 <input type=\"submit\" name=\"cancel\" value=\"Cancel\">
</div>
</form>
";

} /* end Manage() */


/* AbsorbManage()
 *
 *  Absorb a form submission from Manage().
 */

function AbsorbManage($uid) {
  global $auth, $project;
  
  $user = $auth->getUser($uid);
    
//ob_start(); var_dump($_POST); error_log(ob_get_contents()); ob_end_clean();

  if($_POST['submit'] == 'Delete assessment') {

    // Just delete the assessment by this user, leaving the user intact.

    DeleteAssessment($uid, $project['id']);

  } else if($_POST['submit'] == 'Delete user') {

    /* Force-delete this user. But to do it, we first need to drop the
     * 'assessment' and 'teammember' records, if any. */

    DeleteAssessment($uid, null);
    DeleteTeamMember(['userid' => $uid]);

    $rval = $auth->deleteUserForced($uid);
    if($rval['error'])
      error_log("Deleting user $uid failed: {$rval['message']}");
    else
      print "<p>Deleted user $uid</p>\n";
      
  } elseif($_POST['submit'] == 'Apply changes') {

    // absorbing edits to a user
  
    $update = [];

    if($_POST['isactive'] != $user['isactive'])
      $update['isactive'] = $_POST['isactive']; // integer value
    if($_POST['super'] && $user['role'] != 'super')
      $update['role'] = 'super';
    elseif($user['role'] == 'super' && ! isset($_POST['super']))
      $update['role'] = 'user';

    if(count($update)) {
      $rval = $auth->updateUser($uid, $update);
      print "<p>{$rval['message']}</p>\n";
    } else {
      print "<p class=\"alert\">No changes to user record.</p>\n";
    }
    
    /* Deal with 'teammember' records, which may be added, deleted, or
     * role changed. POST params are "<teamid>" for member,
     * "m<teamid>" for manager. */

    $teams = GetTeams();
    $tms = UserTeams($uid);

    // set $nmembers to selected memberships and roles

    $nmembers = [];
    foreach($_POST as $k => $v) {
      if(preg_match('/^(\d+)$/', $k) && !isset($nmembers[$k]))
        $nmembers[$k] = 'user';
      elseif(preg_match('/^m(\d+)$/', $k, $matches))
        $nmembers[$matches[1]] = 'manager';
    }
    
    $inserts = $updates = $deletes = 0;
    
    foreach($nmembers as $teamid => $role) {
      if(array_key_exists($teamid, $tms)) {
        if($role != $tms[$teamid]['role']) {
          UpdateTeamMember($teamid, $uid, $role);
          $updates++;
        }
      } else {
        InsertTeamMember($teamid, $uid, $role);
        $inserts++;
      }
    }
    // Loop on existing memberships, looking for abandonments.

    foreach($tms as $tm) {
      if(!array_key_exists($tm['teamid'], $nmembers)) {
        DeleteTeamMember(['teamid' => $tm['teamid'], 'userid' => $uid]);
        $deletes++;
      }
    }
    if($inserts || $deletes || $updates)
      print "<p class=\"alert\">Inserted $inserts, updated $updates, deleted $deletes.</p>\n";
    else
      print "<p class=\"alert\">No changes to team memberships.</p>\n";
  }
  unset($uid);

} /* end AbsorbManage() */


/* Create()
 *
 *  Present a form for user creation.
 */
 
function Create() {
  $roles = "<select name=\"role\">\n";
  foreach(ROLE as $role => $roleval) {
    if($role == 'manager')
      continue;
    $roles .= " <option value=\"$role\">$role</option>\n";
  }
  $roles .= "</select>\n";
  
  print '<h2 id="add">Create a User</h2>

<p style="font-weight: bold">Use this form to create a user. If you
select "activate," they won\'t be required to go through the usual
email activation process.</p>

<form action="users.php" method="POST" class="gf">
<div class="fieldlabel">Email:</div> <div><input type="text" name="email"></div>
<div class="fieldlabel">Fullname:</div> <div><input type="text" name="fullname"></div>
<div class="fieldlabel">Username:</div> <div><input type="text" name="username"></div>
<div class="fieldlabel">Password:</div> <div><input type="password" name="password"></div>
<div class="fieldlabel">Activate:</div> <div><input type="checkbox" name="activate"></div>
<div class="fieldlabel">Role:</div> <div>' . $roles . '</div>
<div class="gs">
 <input type="submit" name="submit" value="Create user">
 <input type="submit" name="cancel" value="Cancel">
</div>
</form>
';
} /* end Create() */


/* AbsorbCreate()
 *
 *  Absorb creation of a user.
 */

function AbsorbCreate() {
  global $auth;
  
  $password = trim($_POST['password']);
  $fullname = trim($_POST['fullname']);
  $email = trim($_POST['email']);
  $username = strtolower(trim($_POST['username']));
  if(IsUsernameTaken($username)) {
    print "<p>We already have a user with the username <tt>$username</tt> and we can't have two. Pick another.</p>";
    exit;
  } else if(! IsUsernameValid($username)) {
    print "<p><tt>$username</tt> isn't a valid username. Pick another.</p>\n";
    exit;
  }
  $autoactivate = isset($_POST['activate']) ? false: true;
  $rval = $auth->register($email,
                          $password,
                          $password,
                          ['fullname' => $fullname, 'username' => $username],
                          '',
                          $autoactivate);
  if($rval['error']) {
    print "<p>Error: {$rval['message']}</p>\n";
    exit();
  }
  print "<p class=\"error\">{$rval['message']}</p>
";
} /* end AbsorbCreate() */


/* Upload()
 *
 *  Present a form to solicit a file upload of user data.
 */

function Upload() {
  print "<h2 id=\"upload\">Upload User Data</h2>

<p class=\"alert\">Use this screen to upload a CSV file of new
users. We will create user accounts for each of the users that meet
requirements, such a having unique email addresses and usernames and
passwords that meet complexity requirements. The uploaded file must
have all of the following fields: <tt>email</tt>, <tt>username</tt>,
<tt>fullname</tt>, <tt>password</tt>, and <tt>active</tt>. All the
users will have the <tt>user</tt> role. Set the <tt>active</tt> field
to <tt>1</tt> for those users that you wish to autoactivate.</p>

<form action=\"" . $_SERVER['SCRIPT_NAME'] . '" method="post" enctype="multipart/form-data" class="gf">
<input type="hidden" name="action" value="upload">
<div class="fieldlabel">
 <label for="filetoupload">Select file to upload:</label>
</div>
<div>
 <input type="file" name="filetoupload" id="filetoupload">
</div>
<div class="gs">
 <input type="submit" value="Cancel" name="cancel">
 <input type="submit" value="Upload" name="submit">
</div>
</form>
';

} /* end Upload() */


/* DoUpload()
 *
 *  Perform the upload.
 */

function DoUpload() {
  global $auth;

  // these are the fields we support.
    
  $fields = ['email', 'password', 'fullname', 'username', 'active'];
  $fname = $_FILES['filetoupload']['tmp_name'];
  $o = fopen($fname, 'r');

  // $filefields are the fields in the file; lowercase them

  $filefields = fgetcsv($o);
  for($i = 0; $i < count($filefields); $i++)
    $filefields[$i] = strtolower(trim($filefields[$i], '\'" '));

  // ensure that all the fields in the file are supported
    
  foreach($filefields as $filefield) {
    if(!in_array($filefield, $fields)) {
      error("Unsupported field '$filefield' found in file");
      exit;
    }
  }

  // ensure that all the fields we support are present

  foreach($fields as $field) {
    if(!in_array($field, $filefields)) {
      error("Required field '$field' not found in file");
      exit;
    }
  }
    
  // load all the data lines into $users, failing on malformed lines
    
  $users = [];
  while($data = fgetcsv($o)) {
    if(count($data) != count($fields)) {
      error('Data line ' . count($users)+1 . ' has ' . count($data) . ' fields but ' . count($fields) . ' are expected');
      exit;
    }
    $user = [];
    for($i = 0; $i < count($fields); $i++)
      $user[$filefields[$i]] = trim($data[$i], '\'" ');
    $users[] = $user;
  }

  $count = 0;

  foreach($users as $user) {
    $user['username'] = strtolower($user['username']);
    if(IsUsernameTaken($user['username'])) {
      print "<p class=\"alert\">We already have a user with the username <tt>{$user['username']}</tt> and we cannot have two; rejecting.</p>\n";
      continue;
    }
    if(! IsUsernameValid($user['username'])) {
      print "<p class=\"alert\"><tt>{$user['username']}</tt> isn't a valid username. Pick another.</p>\n";
      continue;
    }
    $rval = $auth->register($user['email'],
	      $user['password'],
	      $user['password'],
	      ['fullname' => $user['fullname'],
	       'username' => $user['username']],
	      '',
	      $user['active'] ? false : true);
    if($rval['error']) {
      print "<p class=\"alert\">Error: <span style=\"font-style: oblique\">{$rval['message']}</span> for username <tt>{$user['username']}</tt>.</p>\n";
    } else
      $count++;
      
  } // end loop on new users

  print "<p class=\"alert\">Inserted $count of attempted " .
  count($users) . " users.</p>\n";

} /* end DoUpload() */


set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

$return = ROOTDIR . '/';

if(isset($_POST) && isset($_POST['cancel'])) {
  header("Location: $return");
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(DEBUG && isset($_POST)) error_log(var_export($_POST, true));

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(!$user || $user['role'] != 'super') {
  header('Location: ./');
  exit;
}

/* This user is authorized. */

$title = 'User Management';
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
<img src="images/pattern-sphere-band.png" id="gravy">

<?php

if(isset($_REQUEST['uid'])) {

  # Operating on a single user.

  $uid = $_REQUEST['uid'];

  if(isset($_POST['submit'])) {

    // Process a form submission.

    AbsorbManage($uid);
   
  } else {

    // We are building a form for editing a selected user.

    Manage($uid);
  }
    
} elseif(isset($_POST['submit']) && $_POST['submit'] == 'Create user') {

  // Absorb user creation submission.
  
  AbsorbCreate();
  
} elseif(isset($_POST['action']) && $_POST['action'] == 'upload') {

  // Perform file upload.

  DoUpload();
}

if(! isset($uid)) {

  /* We are building a table of all users. */
  
  $users = GetUsers();           # array of users
}
  
if(isset($uid)) {
  1;

} else {

  /* Offer:
   *  a grid of users to select one for editing
   *  a form for adding a user
   */
  $ufclass = ($project['id']) ? 'uf7' : 'uf6';
?>

<ul>
 <li><a href="#edit">Edit an existing user</a></li>
 <li><a href="#add">Add a user</a></li>
 <li><a href="#upload">Add a file of users</a></li>
 <li style="margin-top: .5em"><a href="<?=$return?>">Return</a></li>
</ul>

<h2 id="edit">Edit an Existing User</h2>

<p style="font-weight: bold">Select a user to edit.</p>

<div class="<?=$ufclass?>">
<div class="gb">UID</div>
<div class="gb">Email</div>
<div class="gb">Fullname</div>
<div class="gb">Username</div>
<div class="gb">Active</div>
<?php

  if($project['id']) {
    print "<div class=\"gb\">Role</div>
<div class=\"gb\">Votes (in/out)</div>\n";
    $stats = Stats();
    $assessments = $stats['byuid'];
    $counts = [
      'total' => 0,
      'active' => 0,
      'inactive' => 0,
      'administrator' => 0,
      'voter' => 0,
      'abstainer' => 0,
    ];
  } else {
    print "<div class=\"gb\">Teams</div>\n";
  }

  foreach($users as $user) {

    if($project['id']) {
      $votes = '';
      if($user['isactive']) {
        $counts['active']++;
	if(isset($assessments[$user['uid']])) {
	  $class = 'voter';
	  $stat = $assessments[$user['uid']];
	  $votes = isset($stat['in']) ? $stat['in']['count'] : '0';
	  $votes .= '/';
	  $votes .= isset($stat['out']) ? $stat['out']['count'] : '0';
	} else {
	  $class = 'abstainer';
	}
      } else {
	$class = 'inactive';
      }
      $counts[$class]++;
      $counts['total']++;
    } else {
      if($uts = UserTeams($user['uid'])) {
        $p = '';
	foreach($uts as $ut) {
	  if(strlen($p))
	    $p .= ', ';
	  if($ut['role'] == 'manager')
	    $p .= '<strong>';
	  $p .= $ut['name'];
	  if($ut['role'] == 'manager')
	    $p .= '</strong>';
	}
      } else {
        $p = 'none';
      }
      $class = '';
    }
    
    $superclass = ($user['role'] == 'super')
      ? 'covid'
      : $class;
      
    print "<div class=\"$superclass\" style=\"text-align: center\">
 <a href=\"?uid={$user['uid']}\">{$user['uid']}</a>
</div>
<div class=\"$class\">{$user['email']}</div>
<div class=\"$class\">{$user['fullname']}</div>
<div class=\"$class\">{$user['username']}</div>
<div class=\"$class\" style=\"text-align: center\">" . ($user['isactive'] ? 'yes' : 'no') . "</div>
";
    if($project['id']) {
      print "<div class=\"$class\">{$user['role']}</div>
     <div class=\"$class\" style=\"text-align: center\">$votes</div>
";

      if($user['role'] == 'super')
        $counts['administrator']++;
    } else {
      print "<div>$p</div>\n";      
    }

  } // end loop on users

  print "</div>\n";

  Create();
  Upload();
}
?>

<p><a href="./">Return</a>.</p>

</div>

<?=FOOT?>
</body>
</html>
