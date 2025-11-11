<?php
# NAME
#
#  ps.php
#
# CONCEPT
#
#  Libary functions and constants for the Pattern Sphere project.
#
# FUNCTIONS
#
#  DataStoreConnect  open a database connection
#  GetPLanguage      get some or all 'plangage' records
#  GetPattern        get some or all 'pattern' records
#  InsertPLanguage   create a 'planguage' record
#  InsertPattern     create a 'pattern' record
#  DeletePattern     delete pattern record
#  DeletePLanguage   delete planguage record
#  UpdatePLanguage   update a 'planguage' record
#  UpdatePattern     update a 'pattern' record
#  GetProjPLanguages fetch planguage records implicated in this project
#  GetProjPatterns   fetch projpattern records implicated in this project
#  GetProjPattern    fetch a single projpattern record with pattern fields
#  GetAssessment     load 'assessment' and all associated 'passessment'
#  GetPAssessment    load 'passessment' record
#  GetPAssessments   load 'passessment' records for argument projpattern
#  GetProject        load 'project' record
#  GetProjects       load 'project' records
#  UpdateProject     update 'project' record
#  InsertProject     insert a 'project' record
#  GetOrganization   load 'organization' record
#  GetOrganizations  load 'organization' records
#  UpdateOrganization update 'organization' record
#  InsertOrganization insert a 'organization' record
#  GetTeam           load 'team' record
#  GetTeams          load 'team' records
#  UpdateTeam        update 'team' record
#  InsertTeam        insert a 'team' record
#  GetTeamMembers    fetch teammember records
#  InsertTeamMember  insert a teammember record
#  UpdateTeamMember  update a teammember record
#  DeleteTeamMember  delete a teammember record
#  ManagedTeams      array of team.id values for teams this userid manages
#  UserTeams         teams this user belongs to
#  Initialize        load application data and initialize auth system
#  IsUsernameTaken   check for unique username
#  IsUsernameValid   check for username validity
#  GetUser           return one user
#  GetUsers          load phpauth_users to an array
#  ProjectMembers    users who are participating in a project
#  GetProjTeams      fetch 'projteam' records
#  InsertProjTeam    insert a 'projteam' record
#  Error             fatal error
#  InsertAssessment  insert an assessment record
#  InsertPAssessment insert a passessment record
#  UpdatePAssessment update a passessment record
#  DeletePAssessment delete a passessment record
#  DeleteAssessment  delete an assessment record
#  UpdateAssessment  update an assessment record
#  GetConfig         fetch all the config from PHPAuth, with 'descript'
#  Stats             data for a report
#  InsertProjPattern insert an projpattern record
#  DeleteProjPattern delete an projpattern record
#  UpdateProjPattern update an projpattern record
#  IsProjManager     true if the current user is a project manager
#  IsParticipant     true if the current user is on a participating team
#  GetAppConfig      read application configuration
#  UpdateAppConfig   update application configuration
#  InsertVolunteer   insert a volunteer record
#  GetVolunteers     return selected volunteers
#  GetVolunteer      return selected volunteer
#  UpdateVolunteer   update a volunteer record
#
# NOTES
#
#  Coming eventually.
#
# $Id: ps.php,v 1.1 2023/03/22 17:49:50 rose Exp $

define('DEBUG', true);
define('AUTOACTIVATE', false);
define('AUTH', true);
require 'lib/db.php';

define('PROOT', ROOTDIR . '/project/');
define('LIBDIR', PROOT . 'lib/');
if(preg_match('%^.+/project/[^/]+/([^/]+)$%',
              $_SERVER['PHP_SELF'],
              $matches)) {
    define('PROJECT', PROOT . 'index.php/' . $matches[1]);
}
define('ROLE', array('user' => 1, 'super' => 2, 'manager' => 3));
define('FOOT', '<div id="foot"><a href="https://www.publicsphereproject.org/">Public Sphere Project</a></div>');
define('MAXSIZE', 200000); # define elsewhere
define('IMAGEDIR', 'images');
define('DEFAULTLANG', 'en_GB');
define('ACLASSES', array('neutral', 'out', 'in'));
define('BLACKLIST', array('dictionary', 'recaptcha'));

require 'vendor/autoload.php';


/* DataStoreConnect()
 *
 *  Connect to the database (unless it's already been done).
 */
 
function DataStoreConnect() {
  global $pdo;

  if(isset($pdo))
    return;

  try {
    $pdo = new PDO(DSN, USER, PW);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), (int) $e->getCode());
  }
  
} /* end DataStoreConnect() */


/* GetPLanguage()
 *
 *  Fetch one or all planguage records.
 */

function GetPLanguage($which = null) {
  global $pdo;

  if(!isset($pdo))
    DataStoreConnect();

  if(isset($which)) {

    # fetch one language
    
    $q = '';
    $u = [];
    foreach($which as $column => $value) {
      if(strlen($q)) {
        $q .= ' AND ';
      }
      $q .= " $column = ?";
      array_push($u, $value);
    }
    $query = "SELECT * FROM planguage pl WHERE $q";
    if(DEBUG) error_log($query);
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    try {
      $rv = $sth->execute($u);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$rv)
      Error('System error: could not fetch pattern languages');
    $planguage = $sth->fetch(PDO::FETCH_ASSOC);
    $query = "SELECT max(prank) FROM pattern p WHERE plid = {$planguage['id']}";
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    try {
      $rv = $sth->execute();
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    $prank = $sth->fetch(PDO::FETCH_NUM);
    $planguage['maxprank'] = $prank[0];
    return $planguage;
    
  } else {

    # fetch all languages
    
    $query = 'SELECT * FROM planguage';
    try {
      $sth = $pdo->prepare($query);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    try {
      $rv = $sth->execute();
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    $planguages = $sth->fetchall(PDO::FETCH_ASSOC);
    return $planguages;
  }
  
} /* end GetPLanguage() */


/* GetPattern()
 *
 *  Fetch one or all patterns, augmented with the pattern language title
 *  as 'pltitle'.
 *
 *  We return an array of arrays or null if no matching patterns.
 */

function GetPattern($which = null) {
  global $pdo;

  if(!isset($pdo))
    DataStoreConnect();

  if(DEBUG && isset($which))
    error_log('GetPattern($which): ' . var_export($which, true));

  if(isset($which)) {
    $q = '';
    $u = [];
    foreach($which as $column => $value) {
      if(strlen($q)) {
	$q .= ' AND ';
      }
      $q .= " $column = ?";
      $u[] = $value;
    }
    $q = "WHERE $q";
  } else {
    $q = '';
    $u = [];
  }
    
  $query = "SELECT p.*, pl.title AS pltitle
 FROM pattern p JOIN planguage pl ON p.plid = pl.id $q";
  if(DEBUG) error_log($query);
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $patterns = $sth->fetchall(PDO::FETCH_ASSOC);
  return $patterns ? $patterns : null;
  
} /* end GetPattern() */


/* InsertPLanguage()
 *
 *  Create a new planguage record.
 */

function InsertPLanguage($params) {
  global $pdo;

  $sql = 'INSERT INTO planguage (title';
  if(isset($params['baseurl']))
    $sql .= ',baseurl';
  $sql .= ') VALUES(:title';
  if(isset($params['baseurl'])) {
    $sql .= ',:baseurl';
  }
  $sql .= ')';
  if(DEBUG) error_log($sql);
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($params)) Error('System errror; your submission was not accepted.');
  return($pdo->lastInsertId());

} /* end InsertPLanguage() */


/* InsertPattern()
 *
 *  Create a new pattern record.
 */

function InsertPattern($params) {
  global $pdo;

  $sql = 'INSERT INTO pattern (title, plid, synopsis, discussion, context, solution, prank, creator)
 VALUES(:title, :plid, :synopsis, :discussion, :context, :solution, :prank, :creator)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($params))
    Error('System errror; your submission was not accepted.');
  return($pdo->lastInsertId());

} /* end InsertPattern() */


/* DeletePattern()
 *
 *  Delete a pattern.
 *
 *  When a pattern is deleted, we also need to decrement the 'prank' of the
 *  patterns with a higher prank.
 */

function DeletePattern($pid) {
  global $pdo;

  $oldpattern = GetPattern(['p.id' => $pid])[0];
  if(! $oldpattern) Error('System failure: pattern deletion failed');
  $sth = $pdo->prepare('DELETE FROM pattern WHERE id = ?');
  if(!$sth->execute([$pid]))
    Error("System errror; deletion failed.");
    
  # now adjust the 'prank' values

  try {
    $sth = $pdo->prepare('UPDATE pattern
 SET prank = prank - 1
 WHERE prank > :prank AND plid = :plid
 ORDER By prank ASC');
  $sth->execute([
    'plid' => $oldpattern['plid'],
    'prank' => $oldpattern['prank']
  ]);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }

} /* end DeletePattern() */


/* DeletePLanguage()
 *
 *  Delete a planguage record and all associated patterns.
 */

function DeletePLanguage($plid) {
  global $pdo;

  try {
    $sth = $pdo->prepare('DELETE FROM pattern WHERE plid = ?');
    $sth2 = $pdo->prepare('DELETE FROM planguage WHERE id = ?');
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute([$plid]))
    Error("System errror; pattern deletion failed.");
  if(!$sth2->execute([$plid]))
    Error("System errror; pattern language deletion failed.");

} /* end DeletePLanguage() */


/* UpdatePLanguage()
 *
 *  Update a pattern language.
 */

function UpdatePLanguage($update) {
  global $pdo;

  $id = $update['id'];
  $planguage = GetPLanguage(['id' => $id]);
  if(! isset($planguage)) {
    Error('Patttern language not found');
  }
  $u = '';
  
  foreach($update as $column => $value) {
    if($column == 'id')
      continue;
    if($planguage[$column] == $update[$column]) {
      unset($update[$column]);
    } else {
      if(strlen($u)) {
        $u .= ',';
      }
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {

    # we found fields that changed

    $sql = "UPDATE planguage SET $u WHERE id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
    print "<p class=\"alert\">Updated language.</p>\n";
  } else {
    print "<p class=\"alert\">No changes to language.</p>\n";
  }

} /* end UpdatePLanguage() */


/* UpdatePattern()
 *
 *  Update a pattern.
 *
 *  Call with an array with fields for every column for which we have a
 *  value; this function will determine which have changed and apply an
 *  UPDATE if any have, returning true or, if no update, false. The 'id'
 *  field identifies the affected pattern.
 */

function UpdatePattern($update) {
  global $pdo;

  if(DEBUG)
    error_log('UpdatePattern(): $update = ' . var_export($update,  true));
  $pattern = GetPattern(['p.id' => $update['id']])[0];

  if(DEBUG)
    error_log('UpdatePattern(): $pattern = ' . var_export($pattern,  true));
  $u = ''; # SET clause

  foreach($update as $column => $value) {
    if($column == 'id') {
      continue;
    }
    if($pattern[$column] == $update[$column]) {
      unset($update[$column]);
    } else {
      if(strlen($u)) {
        $u .= ',';
      }
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {

    # we found fields that changed

    $sql = "UPDATE pattern SET $u WHERE id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error('System error; pattern update failed.');
    return(true);
  } else
    return(false);

} /* end UpdatePattern() */


/* GetProjPLanguages()
 *
 *  Fetch the planguage records implicated in the argument $projid.
 */

function GetProjPLanguages($projid) {
  global $pdo;

  $sth = $pdo->prepare('SELECT *
 FROM planguage
 WHERE id IN (SELECT DISTINCT plid
               FROM project a
                JOIN projpattern ap ON a.id = ap.projid
                JOIN pattern p ON ap.pid = p.id
	       WHERE projid = :projid)');
  $sth->execute(['projid' => $projid]);
  $planguages = $sth->fetchall(PDO::FETCH_ASSOC);
  return($planguages);
  
} /* end GetProjPLanguages() */


/* GetProjPatterns()
 *
 *  Fetch key fields from the pattern records implicated in the
 *  argument $projid:
 *
 *         id  pattern.id
 *       plid  planguage.id
 *      title  pattern.title
 *   synopsis  pattern.synopsis
 *    pltitle  planguage.title
 *       apid  projpattern.id
 *     status  projpattern.status
 *
 *  If $bypid is true, return an array of arrays keyed on pattern.id.
 *  If not, return an array of arrays in no particular order.
 */

function GetProjPatterns($projid, $bypid = false) {
  global $pdo;

  $sth = $pdo->prepare('SELECT p.id, p.plid, p.title, p.synopsis, pl.title AS pltitle, ap.id AS apid, ap.status
 FROM project a
  JOIN projpattern ap ON a.id = ap.projid
  JOIN pattern p ON ap.pid = p.id
  JOIN planguage pl ON p.plid = pl.id
 WHERE projid = :projid');
  $sth->execute(['projid' => $projid]);
  $projpatterns = $sth->fetchall(PDO::FETCH_ASSOC);
  
  if($bypid) {
  
    # Return them indexed by p.id.
  
    $projpatterns_by_pid = [];
    foreach($projpatterns as $projpattern) {
      $projpatterns_by_pid[$projpattern['id']] = $projpattern;
    }
    return($projpatterns_by_pid);
  }
  return($projpatterns);
  
} /* end GetProjPatterns() */


/* GetProjPattern()
 *
 *  Fetch a single projpattern record joined with the parent pattern
 *  selected by argument projpattern.id. Fields returned are projpattern.id,
 *  projpattern.status, pattern.*, and planguage.title.
 */

function GetProjPattern($id) {
  global $pdo;
  
  $sth = $pdo->prepare('SELECT ap.id AS apid, status, p.*, pl.title AS pltitle
 FROM projpattern ap
  JOIN pattern p ON ap.pid = p.id
  JOIN planguage pl ON plid = pl.id
 WHERE ap.id = :id');
  $sth->execute(['id' => $id]);
  return($sth->fetch(PDO::FETCH_ASSOC));
  
} /* end GetProjPattern() */


/* GetAssessment()
 *
 *  Call this function with a filter that will return at most one assessment
 *  record.
 *
 *  An 'assessment' record contains 'id', 'userid', 'projid', 'contact',
 *  and 'acomment' fields. 'projid' is a foreign key on project.id.
 *  'userid' is a foreign key on phpauth_users.id.
 *
 *  We perform a second query for associated 'passessment' records and
 *  store those - if any - as the 'passessment' field, which is an array
 *  keyed on 'pattern.id', the 'pid' field of the 'passessment' rec.
 */

function GetAssessment($which) {
  global $pdo;

  $query = 'SELECT * FROM assessment';
  if(isset($which)) {
    $q = '';
    $u = [];
    foreach($which as $column => $value) {
      if(strlen($q))
        $q .= ' AND';
      $q .= " $column = :$column";
      $u[$column] = $value;
    }
    $query .= " WHERE $q";
  }
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error: could not fetch assessment record');
  $assessment = $sth->fetch(PDO::FETCH_ASSOC);
  if(!$assessment) {
    return(null);
  }
  
  // now, any associated 'passessment' records

  try {
    $sth = $pdo->prepare('SELECT * FROM passessment WHERE assid = :assid');
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $sth->execute(['assid' => $assessment['id']]);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $passessments = $sth->fetchall(PDO::FETCH_ASSOC);
  if(count($passessments)) {
    foreach($passessments as $passessment) {
      $assessment['passessments'][$passessment['pid']] = $passessment;
    }
  } else
    $assessment['passessments'] = [];
    
  return($assessment);
  
} /* end GetAssessment() */


/* GetPAssessment()
 *
 *  An 'passessment' record contains 'id', 'pid', 'assid', 'assessment',
 *  and 'commentary' fields. We return the first one that matches the
 *  argument. 'assid' is a foreign key on passessment.id.
 */

function GetPAssessment($which) {
  global $pdo;

  $query = 'SELECT * FROM passessment';
  if(isset($which)) {
    $q = '';
    $u = [];
    foreach($which as $column => $value) {
      if(strlen($q))
        $q .= ' AND';
      $q .= " $column = :$column";
      $u[$column] = $value;
    }
    $query .= " WHERE $q";
  }
  if(false) {
    error_log("GetPAssessment($query)");
    error_log(var_export($u, true));
  }
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error: could not fetch passessment record');
  $passessment = $sth->fetch(PDO::FETCH_ASSOC);
  if(!$passessment) {
    return(null);
  }
  return($passessment);
  
} /* end GetPAssessment() */


/* GetPAssessments()
 *
 *  Return all passessment records matching the argument projpattern.id.
 */

function GetPAssessments($id) {
  global $pdo;

  $query = "SELECT * FROM passessment WHERE apid = :id";

  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute(['id' => $id]);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error: could not fetch passessment records');
  $passessments = $sth->fetchall(PDO::FETCH_ASSOC);

  return($passessments);
  
} /* end GetPAssessments() */


/* GetProject()
 *
 *  Load the row from the 'project' table that corresponds to this
 *  project into $project.
 */

function GetProject($projid = null) {
  global $pdo;

  if(isset($projid)) {

    // the project ID is a function argument
    
    $sth = $pdo->prepare('SELECT * FROM project WHERE id = :id');
    $sth->execute(['id' => $projid]);

  } else {
    if(isset($_SERVER['PATH_INFO'])) {

      // match PATH_INFO to a project.tag

      preg_match('%^/(.+)$%', $_SERVER['PATH_INFO'], $matches);
    } else {
  
      // tease out the trailing path component and match that to a project.tag

      preg_match('%/([^/]+)/[^/]*$%', $_SERVER['SCRIPT_NAME'], $matches);
    }
    $tag = $matches[1];
    if($tag == 'ps') {
      $tag = 'project';
    }
    $sth = $pdo->prepare('SELECT * FROM project WHERE tag = :tag');
    $sth->execute(['tag' => $tag]);
  }
  $project = $sth->fetch(PDO::FETCH_ASSOC);
  return($project);

} /* end GetProject() */


/* GetProjects()
 *
 *  Load key fields from the 'project' table, indexed by projid.
 */

function GetProjects() {
  global $pdo;

  $sth = $pdo->prepare('SELECT id, title, tag, active FROM project ORDER BY id');
  $sth->execute();
  $projs = [];
  while($proj = $sth->fetch(PDO::FETCH_ASSOC)) {
    $projs[$proj['id']] = $proj;
  }
  return($projs);

} /* end GetProjects() */


/* UpdateProject()
 *
 *  Update the project table.
 */

function UpdateProject($update) {
  global $pdo;

  if(!isset($update['id']))
    $update['id'] = 0;
  $project = GetProject($update['id']);
  if(!isset($project))
    Error('Cannot update anonymous project');
  
  $u = '';

  foreach($update as $column => $value) {
    if($column == 'id')
      continue;

    if($column == 'destination' && !$update['destination'])
      $update['destination'] = null;

    if($project[$column] == $update[$column])
      unset($update[$column]);
    else {
      if(strlen($u))
        $u .= ',';
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {
    $sql = "UPDATE project SET $u where id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
    return(true);
  } else
    return(false);

} /* end UpdateProject() */


/* InsertProject()
 *
 *  Insert a project record.
 */

function InsertProject($params) {
  global $pdo;

  unset($params['submit']);
  $columns = $values = '';
  foreach($params as $c => $v) {
    if(strlen($columns)) {
      $columns .= ',';
      $values .= ',';
    }
    $columns .= $c;
    $values .= ":$c";
  }
  $sql = "INSERT INTO project ($columns) VALUES($values)";
  if(DEBUG) error_log($sql);
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($params))
    Error('System errror; project insert failed.');
  return($pdo->lastInsertId());

} /* end InsertProject() */


/* GetOrganization()
 *
 *  Load the row from the 'organization' table that corresponds to this
 *  orgid.
 */

function GetOrganization($orgid) {
  global $pdo;

  $sth = $pdo->prepare('SELECT * FROM organization WHERE id = :id');
  $sth->execute(['id' => $orgid]);
  $org = $sth->fetch(PDO::FETCH_ASSOC);
  return($org);

} /* end GetOrganization() */


/* GetOrganizations()
 *
 *  Load key fields from the organization table, indexed by orgid.
 */

function GetOrganizations() {
  global $pdo;

  $sth = $pdo->prepare('SELECT * FROM organization ORDER BY id');
  $sth->execute();
  $orgs = [];
  while($org = $sth->fetch(PDO::FETCH_ASSOC)) {
    $orgs[$org['id']] = $org;
  }
  return($orgs);

} /* end GetOrganizations() */


/* UpdateOrganization()
 *
 *  Update the organization table.
 */

function UpdateOrganization($update) {
  global $pdo;

  $orgid = $update['id'];
  $organization = GetOrganization($orgid);
  if(!isset($organization))
    Error('Cannot update anonymous organization');
  
  $u = '';

  foreach($update as $column => $value) {
    if($column == 'id')
      continue;

    if($organization[$column] == $update[$column])
      unset($update[$column]);
    else {
      if(strlen($u))
        $u .= ',';
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {
    $sql = "UPDATE organization SET $u where id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
    return(true);
  } else
    return(false);

} /* end UpdateOrganization() */


/* InsertOrganization()
 *
 *  Insert a organization record.
 */

function InsertOrganization($params) {
  global $pdo;

  unset($params['submit']);
  $columns = $values = '';
  foreach($params as $c => $v) {
    if(strlen($columns)) {
      $columns .= ',';
      $values .= ',';
    }
    $columns .= $c;
    $values .= ":$c";
  }
  $sql = "INSERT INTO organization ($columns) VALUES($values)";
  if(DEBUG) error_log($sql);
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($params))
    Error('System errror; organization insert failed.');
  return($pdo->lastInsertId());

} /* end InsertOrganization() */


/* GetTeam()
 *
 *  Load the row from the 'team' table that corresponds to this id.
 */

function GetTeam($id) {
  global $pdo;

  $sth = $pdo->prepare('SELECT * FROM team WHERE id = :id');
  $sth->execute(['id' => $id]);
  $team = $sth->fetch(PDO::FETCH_ASSOC);
  return($team);

} /* end GetTeam() */


/* GetTeams()
 *
 *  Load key fields from the team table, indexed by id.
 */

function GetTeams() {
  global $pdo;

  $teams = [];
  $sth = $pdo->prepare('SELECT * FROM team ORDER BY id');
  $sth->execute();
  while($team = $sth->fetch(PDO::FETCH_ASSOC)) {
    $teams[$team['id']] = $team;
  }
  return($teams);

} /* end GetTeams() */


/* UpdateTeam()
 *
 *  Update the team table.
 */

function UpdateTeam($update) {
  global $pdo;

  $id = $update['id'];
  $team = GetTeam($id);
  if(!isset($team))
    Error('Cannot update anonymous team');
  
  $u = '';

  foreach($update as $column => $value) {
    if($column == 'id')
      continue;

    if($team[$column] == $update[$column])
      unset($update[$column]);
    else {
      if(strlen($u))
        $u .= ',';
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {
    $sql = "UPDATE team SET $u where id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
    return(true);
  } else
    return(false);

} /* end UpdateTeam() */


/* InsertTeam()
 *
 *  Insert a team record.
 */

function InsertTeam($params) {
  global $pdo;

  unset($params['submit']);
  $columns = $values = '';
  foreach($params as $c => $v) {
    if(strlen($columns)) {
      $columns .= ',';
      $values .= ',';
    }
    $columns .= $c;
    $values .= ":$c";
  }
  $sql = "INSERT INTO team ($columns) VALUES($values)";
  if(DEBUG) error_log($sql);
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($params))
    Error('System errror; team insert failed.');
  return($pdo->lastInsertId());

} /* end InsertTeam() */


/* GetTeamMembers()
 *
 *  Return 'teammember' records for this team.id, an array keyed on user
 *  UID.
 */
 
function GetTeamMembers($id) {
  global $pdo;

  try {
    $sth = $pdo->prepare("SELECT * FROM teammember WHERE teamid = :teamid");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $sth->execute(['teamid' => $id]);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }

  $teammembers = [];
  while($t = $sth->fetch(PDO::FETCH_ASSOC))
    $teammembers[$t['userid']] = $t;

  return($teammembers);

} /* end GetTeamMembers() */


/* InsertTeamMember()
 *
 *  Insert one 'teammember' record.
 */
 
function InsertTeamMember($teamid, $uid, $role) {
  global $pdo;

  try {
    $sth = $pdo->prepare("INSERT INTO teammember (teamid, userid, role)
 VALUES (:teamid, :userid, :role)");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['teamid' => $teamid, 'userid' => $uid, 'role' => $role]))
    Error('System error: failed to insert team member');
  return($pdo->lastInsertId());

} /* end InsertTeamMember() */


/* UpdateTeamMember()
 *
 *  Update the role in a 'teammember' record.
 */
 
function UpdateTeamMember($teamid, $uid, $role) {
  global $pdo;

  try {
    $sth = $pdo->prepare("UPDATE teammember SET role = :role WHERE userid = :userid AND teamid = :teamid");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['teamid' => $teamid, 'userid' => $uid, 'role' => $role]))
    Error('System error: update of teammember failed');

} /* end UpdateTeamMember() */


/* DeleteTeamMember()
 *
 *  Delete one 'teammember' record.
 */
 
function DeleteTeamMember($which) {
  global $pdo;

  $q = '';
  foreach($which as $c => $v) {
    if(strlen($q)) $q .= ' AND ';
    $q .= "$c = :$c";
  }
  try {
    $sth = $pdo->prepare("DELETE FROM teammember WHERE $q");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute($which))
    Error('System error: deletion of team member failed');
    
} /* end DeleteTeamMember() */


/* ManagedTeams()
 *
 *  Returns an array containing the team.id values for teams for which the
 *  argument user is a manager.
 */

function ManagedTeams($userid) {
  global $pdo;
  
  try {
    $sth = $pdo->prepare("SELECT teamid FROM teammember
 WHERE userid = :userid AND role = 'manager'");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['userid' => $userid]))
    Error('System error: failed to determine user team role');
  $teams = [];
  while($id = $sth->fetchColumn())
    $teams[] = $id;
  return($teams);

} /* end ManagedTeams() */


/* UserTeams()
 *
 *  An array of teams for which this user is a member, indexed by team.id,
 *  with 'teamid', 'role', and 'name' values.
 */

function UserTeams($userid) {
  global $pdo;
  
  try {
    $sth = $pdo->prepare("SELECT teamid, role, name
 FROM teammember tm
  JOIN team t ON tm.teamid = t.id
 WHERE userid = :userid");
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['userid' => $userid]))
    Error('System error: failed to determine user team memberships');
  $teams = [];
  while($t = $sth->fetch(PDO::FETCH_ASSOC))
    $teams[$t['teamid']] = $t;
  return($teams);

} /* end UserTeams() */


/* Initialize()
 *
 *  Application startup.
 */

function Initialize() {
  global $config, $auth, $pdo, $project;

  $project = GetProject();
  $config = new PHPAuth\Config($pdo);
  $auth = new PHPAuth\Auth($pdo, $config);

} /* end Initialize() */


/* IsUsernameTaken()
 *
 *  Returns true if we have a user record with with this username.
 */

function IsUsernameTaken($username) {
  global $pdo;

  $query = 'SELECT count(*) FROM phpauth_users WHERE username = :username';
  $query_prepared = $pdo->prepare($query);
  $query_prepared->execute(['username' => $username]);
  $row = $query_prepared->fetch();
  return($row[0] ? true : false);
  
} /* end IsUsernameTaken() */


/* IsUsernameValid()
 *
 *  Returns false if we don't like the username provided.
 */

function IsUsernameValid($username) {
  return(preg_match('/^[a-z0-9_-]{4,20}$/', $username));

} /* end IsUsernameValid() */


/* GetUser()
 *
 *  Return useful contents of the row in phpauth_users corresponding to
 *  this user id.
 */

function GetUser($id) {
  $users = GetUsers(['id' => $id]);
  if(count($users))
    return $users[$id];
  return false;
  
} // end GetUser()


/* GetUsers()
 *
 *  Return the useful contents of the phpauth_users table, indexed by uid.
 *
 *  If an argument is provided, it's an array of column names and values
 *  that will be used to create a WHERE clause in which the conditions are
 *  ANDed.
 */

function GetUsers($filter = null) {
  global $pdo;

  $sql = 'SELECT id AS uid, email, isactive, dt, fullname, username, role
 FROM phpauth_users';
  if(isset($filter)) {
    $conditions = '';
    foreach($filter as $name => $value) {
      if(strlen($conditions))
        $conditions .= ' AND ';
      $conditions .= "$name = $value";
    }
    $sql .= " WHERE $conditions";
  }
  $sth = $pdo->prepare($sql);
  $sth->execute();
  $users = [];
  while($user = $sth->fetch(PDO::FETCH_ASSOC)) {
    $users[$user['uid']] = $user;
  }
  return $users;
  
} /* end GetUsers() */


/* ProjectMembers()
 *
 *  Given a project.id value, find all the users who are members of a team
 *  that is participating in the project, returning user information as
 *  well as a comma-separated list of the teams to which they belong in
 *  an array keyed on userid.
 */

function ProjectMembers($projid) {
  global $pdo;

  $sql = "SELECT userid, fullname, username, email, isactive, name AS team
 FROM teammember tm
  JOIN phpauth_users u ON tm.userid = u.id
  JOIN team t ON tm.teamid = t.id
 WHERE teamid IN (SELECT teamid FROM projteam WHERE projid = :projid)";
    
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $sth->execute(['projid' => $projid]);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $users = [];
  while($user = $sth->fetch(PDO::FETCH_ASSOC)) {
    if(array_key_exists($user['userid'], $users))
      $users[$user['userid']]['team'] .= ",{$user['team']}";
    else
      $users[$user['userid']] = $user;
  }
  return($users);
  
} /* end ProjectMembers() */


/* GetProjTeams()
 *
 *  Return an array of projteam records, keyed on teamid.
 */

function GetProjTeams($projid = null) {
  global $pdo;

  $sql = 'SELECT pt.*, t.name, p.tag FROM projteam pt
 JOIN project p ON pt.projid = p.id
 JOIN team t on pt.teamid = t.id';
  
  if(isset($projid)) {
  
    /* operating in the context of a particular project */

    $sql .= " WHERE projid = $projid";
  }
  error_log($sql);  
  $sth = $pdo->prepare($sql);
  $sth->execute();
  $projteams = [];
  while($projteam = $sth->fetch(PDO::FETCH_ASSOC)) {
    $projteams[$projteam['teamid']] = $projteam;
  }
  return($projteams);

} /* end GetProjTeams() */


/* InsertProjTeam()
 *
 *  Insert a 'projteam' record given 'projid' and 'teamid' values.
 */

function InsertProjTeam($prid, $tid) {
  global $pdo;
  
  try {
    $sth = $pdo->prepare('INSERT INTO projteam(projid, teamid) VALUES(:projid, :teamid)');
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute(['projid' => $prid, 'teamid' => $tid]))
    Error('System error: your submission was not accepted.');
  return($pdo->lastInsertId());
  
} /* end InsertProjTeam() */


/* DeleteProjTeam()
 *
 *  Delete a 'projteam' record given either its 'id' or 'projid' and 'teamid'.
 */
 
function DeleteProjTeam($which) {
  global $pdo;

  $d = '';
  foreach($which as $k => $v) {
    if(strlen($d))
      $d .= ' AND ';
    $d .= "$k = :$k";
  }
  $sql = "DELETE FROM projteam WHERE $d";
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute($which))
    Error('System error; projteam delete failed');
  
} /* end DeleteProjTeam() */


/* GetProjUsers()
 *
 *  Return an array of projuser records, keyed on userid.
 */

function GetProjUsers($projid) {
  global $pdo;

  $sql = 'SELECT * FROM projuser au';
  
  if(isset($projid)) {
  
    /* operating in the context of a particular project */

    $sql .= " WHERE projid = $projid";
  }
  error_log($sql);  
  $sth = $pdo->prepare($sql);
  $sth->execute();
  $projusers = [];
  while($projuser = $sth->fetch(PDO::FETCH_ASSOC)) {
    $projusers[$projuser['userid']][] = $projuser;
  }
  return($projusers);

} /* end GetProjUsers() */


/* GetProjUser()
 *
 *  Return an projuser record if one exists for this userid and projid.
 */

function GetProjUser($projid, $userid) {
  global $pdo;

  $sql = 'SELECT * FROM projuser au WHERE userid = :userid AND projid = :projid';
  $sth = $pdo->prepare($sql);
  $sth->execute(['projid' => $projid, 'userid' => $userid]);
  $projuser = $sth->fetch(PDO::FETCH_ASSOC);
  return($projuser);

} /* end GetProjUser() */


/* Error
 *
 *  Display an error message with a "continue" link.
 */
 
function Error($msg) {
  print "<p class=\"error\">$msg</p>

<p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>
";
  exit();
  
} /* end Error() */


/* InsertAssessment()
 *
 *  Insert an assessment record.
 */

function InsertAssessment($insert) {
  global $pdo;

  $fields = '';
  $values = '';
  foreach($insert as $f => $v) {
    if(strlen($fields)) {
      $fields .= ',';
      $values .= ',';
    }
    $fields .= $f;
    $values .= ":$f";
  }
  $query = "INSERT INTO assessment($fields) VALUES($values)";
  if(DEBUG) error_log($query);
  try {
    $sth = $pdo->prepare($query);
    $rv = $sth->execute($insert);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error; your submission was not accepted');
  return($pdo->lastInsertId());
  
} /* end InsertAssessment() */


/* InsertPAssessment()
 *
 *  Insert a passessment record.
 */

function InsertPAssessment($insert) {
  global $pdo;

  $query = 'INSERT INTO passessment (pid,apid,assid';
  if(isset($insert['assessment']))
    $query .= ',assessment';
  if(isset($insert['commentary']))
    $query .= ',commentary';
  $query .= ') VALUES(:pid,:apid,:assid';
  if(isset($insert['assessment']))
    $query .= ',:assessment';
  if(isset($insert['commentary']))
    $query .= ',:commentary';
  $query .= ')';
  if(DEBUG) error_log($query);
  try {
    $sth = $pdo->prepare($query);
    $rv = $sth->execute($insert);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error; you submission was not accepted');
  return($pdo->lastInsertId());
  
} /* end InsertPAssessment() */


/* UpdatePAssessment()
 *
 *  Update a 'passessment' record.
 */

function UpdatePAssessment($update) {
  global $pdo;

  $id = $update['id'];
  if(DEBUG) error_log("UpdatePAssessment($id)");
  $passessment = GetPAssessment(['id' => $id]);
  if(! isset($passessment)) {
    Error('System error: pattern assessment not found');
  }
  $u = '';
  
  foreach($update as $column => $value) {
    if($column == 'id')
      continue;
    if($passessment[$column] == $update[$column]) {
      unset($update[$column]);
    } else {
      if(strlen($u)) {
        $u .= ',';
      }
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {

    # we found fields that changed

    $sql = "UPDATE passessment SET $u WHERE id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
  }

} /* end UpdatePAssessment() */


/* DeletePAssessment()
 *
 *  Delete this 'passessment' record.
 */

function DeletePAssessment($id) {
  global $pdo;

  if(DEBUG) error_log("DeletePassessment($id)");
  $query = 'DELETE FROM passessment WHERE id = :id';
  if(!($sth = $pdo->prepare($query)) || ! $sth->execute(['id' => $id]))
    Error('System error: cannot delete pattern assessment record');
    
} /* end PAssessment() */


/* DeleteAssessment()
 *
 *  Delete 'assessment' record for this user and project or all for
 *  this user if no project is specified.
 */

function DeleteAssessment($userid, $projid) {
  global $pdo;

  if(DEBUG) error_log("DeleteAssessment($userid, $projid)");
  $query = 'DELETE FROM assessment WHERE userid = :userid';
  $filter = ['userid' => $userid];
  if($projid) {
    $query .= ' AND projid = :projid';
    $filter['projid'] = $projid;
  }
  if(!($sth = $pdo->prepare($query)) || ! $sth->execute($filter))
    Error('System error: cannot delete assessment record');
    
} /* end DeleteAssessment() */


/* UpdateAssessment()
 *
 *  Update an 'assessment' record.
 */

function UpdateAssessment($update) {
  global $pdo;

  $id = $update['id'];
  if(DEBUG) error_log("UpdateAssessment($id)");
  $assessment = GetAssessment(['id' => $id]);
  if(! isset($assessment)) {
    Error('System error: assessment not found');
  }
  $u = '';
  
  foreach($update as $column => $value) {
    if($column == 'id')
      continue;
    if($assessment[$column] == $update[$column]) {
      unset($update[$column]);
    } else {
      if(strlen($u)) {
        $u .= ',';
      }
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {

    # we found fields that changed

    $sql = "UPDATE assessment SET $u WHERE id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
  }

} /* end UpdateAssessment() */


/* GetConfig()
 *
 *  Return the configuration settings from PHPAuth augmented by the
 *  'descript' values from the 'config_meta' table.
 */

function GetConfig() {
  global $pdo, $config;

  $settings = $config->getAll();
   foreach(BLACKLIST as $blacklist) {
    unset($settings[$blacklist]);
  }
  $sth = $pdo->prepare('SELECT * FROM config_meta');
  $sth->execute();
  $metas = $sth->fetchAll(PDO::FETCH_ASSOC);
  $mbs = [];
  foreach($metas as $meta) {
    $mbs[$meta['setting']] = $meta;    
  }
  $nsettings = [];
  foreach($settings as $setting => $value) {
    $nsettings[$setting] = ['value' => $value, 'setting' => $setting];
    if(isset($mbs[$setting]))
      $nsettings[$setting]['descript'] = $mbs[$setting]['descript'];
  }
  return($nsettings);
  
} /* end GetConfig() */


/* inlove()
 *
 *  Comparison function on pattern score.
 */

function inlove($a, $b) {
  return($b['score'] <=> $a['score']);
  
} /* end inlove() */


/* Stats()
 *
 *  Returns a 2-element array with:
 *
 *   a 'byuser' array keyed on userid to an array keyed on assessment values
 *   with a count of the 'passessment' elements for that user and
 *   vote.
 *
 *   a 'bypid' array keyed on patternid to arrays with these fields:
 *
 *           'pid'  pattern.id
 *        'ptitle'  pattern title
 *       'pltitle'  pattern language title
 *        'assess'  n-element array with assessment value counts
 *        'unattr'  n-element array with 'unattr_in' and 'unattr_out' values
 *    'commentary'  array of 3-element arrays with 'userid', 'commentary', and
 *                   'assess' fields
 */

function Stats() {
  global $pdo, $project;

  $projid = $project['id'];
  $acount = count(explode(':', $project['labels']));
  $lvalues = explode(':', $project['lvalues']);
  
  # User statistics - how many assessments of each value for each user for
  # this project.

  $sth = $pdo->prepare('SELECT userid, count(*) AS count, assessment
 FROM passessment pa
  RIGHT JOIN assessment a ON pa.assid = a.id
 WHERE projid = :projid
 GROUP BY userid, assessment');
  $sth->execute(['projid' => $projid]);
  $results = $sth->fetchall(PDO::FETCH_ASSOC);
  $byuid = [];
  foreach($results AS $result) {
    $byuid[$result['userid']][$result['assessment']] = $result;
  }

  # Pattern stats. First, fetch the patterns themselves.

  $sth = $pdo->query('SELECT pid, p.title AS ptitle, pl.title AS pltitle,
  unattr_in, unattr_out
 FROM projpattern ap
  JOIN pattern p ON ap.pid = p.id
  JOIN planguage pl ON pl.id = p.plid
 WHERE projid = ' . $projid . '
 ORDER BY plid, pid');
  $patterns = $sth->fetchAll(PDO::FETCH_ASSOC);

  # Now, key fields from the assessments, passessments, and phpauth_users.

  $sth = $pdo->prepare('SELECT pa.assessment, pa.commentary, a.userid
 FROM passessment pa
  JOIN assessment a ON pa.assid = a.id
 WHERE a.projid = ' . $projid . ' AND pa.pid = :pid');
  
  foreach($patterns as &$pattern) {
    $rv = $sth->execute(['pid' => $pattern['pid']]);
    $ass = $sth->fetchAll(PDO::FETCH_ASSOC);
    for($i = 0; $i < $acount; $i++)
      $pattern['assess'][$i] = 0;
    $pattern['commentary'] = [];
    
    foreach($ass as $as) {
      if(isset($as['assessment']))
        $pattern['assess'][$as['assessment']]++;
      if(isset($as['commentary']) && strlen($as['commentary'])) {
        array_push($pattern['commentary'], [
	  'userid' => $as['userid'],
	  'commentary' => $as['commentary'],
	  'assessment' => $as['assessment']
	]);
      }
    }

    // compute the pattern score

    $pattern['score'] = 0;
    for($i = 0; $i < $acount; $i++)
      $pattern['score'] += $pattern['assess'][$i] * $lvalues[$i];

  } // end loop on patterns

  usort($patterns, 'inlove');
  return(['byuid' => $byuid, 'bypid' => $patterns]);
  
} /* end Stats() */


/* InsertProjPattern()
 *
 *  Insert an projpattern record.
 */

function InsertProjPattern($ap) {
  global $pdo;

  $sql = 'INSERT INTO projpattern(projid, pid) VALUES(:projid, :pid)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($ap))
    Error('System errror; projpattern was not inserted.');
  return($pdo->lastInsertId());
  
} /* end InsertProjPattern() */


/* DeleteProjPattern()
 *
 *  Delete an projpattern record.
 */

function DeleteProjPattern($ap) {
  global $pdo;

  $sth = $pdo->prepare('DELETE FROM projpattern WHERE projid = :projid AND pid = :pid');
  if(!$sth->execute($ap))
    Error("System errror; projpattern deletion failed.");
    
} /* end DeleteProjPattern() */


/* UpdateProjPattern()
 *
 *  Update an projpattern record. (All that can change is the status value.)
 */

function UpdateProjPattern($update) {
  global $pdo;

  $id = $update['id'];
  $status = $update['status'];
  try {
    $sth = $pdo->prepare('UPDATE projpattern SET status = :status WHERE id = :id');
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute($update))
    Error("System error; projpattern update failed");
    
} /* end UpdateProjPattern() */


/* GetProjManagers()
 *
 *  Fetch projmanager records.
 *
 *  Return value is an array of associative arrays containing all columns
 *  from 'projmanager' plus the 'email', 'fullname', and 'username' columns
 *  of the associated 'phpauth_users' records.
 */

function GetProjManagers($which = null) {
  global $pdo;
  
  if(!isset($pdo))
    DataStoreConnect();

  $q = '';
  $u = [];
  if(isset($which)) {
    foreach($which as $column => $value) {
      if(strlen($q)) {
        $q .= ' AND ';
      }
      $q .= " $column = ?";
      array_push($u, $value);
    }
  }
  $query = 'SELECT pm.*, p.tag, u.email, u.fullname, u.username
 FROM projmanager pm
  JOIN project p ON pm.projid = p.id
  JOIN phpauth_users u ON pm.userid = u.id';
  
  if(strlen($q))
    $query .= " WHERE $q";
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $projmanagers = $sth->fetchall(PDO::FETCH_ASSOC);
  return($projmanagers);

} /* end GetProjManagers() */


/* GetOrgManagers()
 *
 *  Fetch orgmanager records.
 *
 *  Return value is an array of associative arrays containing all columns
 *  from 'orgmanager' plus the 'email', 'fullname', and 'username' columns
 *  of the associated 'phpauth_users' records.
 */

function GetOrgManagers($which = null) {
  global $pdo;
  
  if(!isset($pdo))
    DataStoreConnect();

  $q = '';
  $u = [];
  if(isset($which)) {
    foreach($which as $column => $value) {
      if(strlen($q)) {
        $q .= ' AND ';
      }
      $q .= " $column = ?";
      array_push($u, $value);
    }
  }
  $query = 'SELECT om.*, o.name, u.email, u.fullname, u.username
 FROM orgmanager om
  JOIN organization o ON om.orgid = o.id
  JOIN phpauth_users u ON om.userid = u.id';
  
  if(strlen($q))
    $query .= " WHERE $q";
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($u);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $orgmanagers = $sth->fetchall(PDO::FETCH_ASSOC);
  return($orgmanagers);

} /* end GetOrgManagers() */


/* InsertOrgManager()
 *
 *  Insert an 'orgmanager' record.
 */
 
function InsertOrgManager($om) {
  global $pdo;
  
  $sql = 'INSERT INTO orgmanager (orgid, userid) VALUES(:orgid, :userid)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($om))
    Error('System errror; orgmanager was not inserted.');
  return($pdo->lastInsertId());

} /* end InsertOrgManager() */


/* InsertProjManager()
 *
 *  Insert a 'projmanager' record.
 */
 
function InsertProjManager($pm) {
  global $pdo;
  
  $sql = 'INSERT INTO projmanager (projid, userid) VALUES(:projid, :userid)';
  try {
    $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(! $sth->execute($pm))
    Error('System errror; projmanager was not inserted.');
  return($pdo->lastInsertId());

} /* end InsertProjManager() */


/* DeleteOrgManager()
 *
 *  Delete an 'orgmanager' record specified by its 'id'.
 */

function DeleteOrgManager($id) {
  global $pdo;

  $sql = 'DELETE FROM orgmanager WHERE id = :id';
  $filter = ['id' => $id];
  try {
   $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['id' => $id]))
    Error("System error; orgmanager deletion failed");
  
} /* end DeleteOrgManager() */


/* DeleteProjManager()
 *
 *  Delete an 'projmanager' record specified by its 'id'.
 */

function DeleteProjManager($id) {
  global $pdo;

  $sql = 'DELETE FROM projmanager WHERE id = :id';
  $filter = ['id' => $id];
  try {
   $sth = $pdo->prepare($sql);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$sth->execute(['id' => $id]))
    Error("System error; projmanager deletion failed");
  
} /* end DeleteProjManager() */


/* IsProjectManager()
 *
 *  True if this user is a manager of this project.
 *
 *  We get the user.id and project.id either from the argument list or
 *  from global $user and $project.
 */

function IsProjectManager($userid = null, $projid = null) {
  global $user, $project;

  if(is_null($userid))
    $userid = $user['id'];
  if(is_null($projid))
    $projid = $project['id'];

  $pms = GetProjManagers([
   'userid' => $userid,
   'projid' => $projid
  ]);
  
  return(count($pms) ? true : false);
  
} /* end IsProjectManager() */


/* IsParticipant()
 *
 *  True for the current user if they may participate in this project,
 *  either by virtue of being a manager or by being a member of a
 *  participating team.
 */

function IsParticipant() {
  global $user, $project;

  if(IsProjectManager())
    return(true);
  $pts = GetProjTeams($project['id']);
  foreach($pts as $pt) {
    $tms = GetTeamMembers($pt['teamid']);
    if(array_key_exists($user['id'], $tms))
      return(true);
  }
  return(false);
  
} /* end IsParticipant() */


/* GetAppConfig()
 *
 *  Given an appitem.tag value and optional language, return the corresponding
 *  string or, if not found, the string for the default language.
 *
 *  If $tag isn't specified, return all the rows for this language, indexed
 *  by tag.
 */

function GetAppConfig($tag = null, $lang = DEFAULTLANG) {
  global $pdo;

  if(isset($tag)) {
    $sth = $pdo->prepare('SELECT value FROM appconfig ac
      JOIN appitem ai ON ac.appitemid = ai.id
     WHERE ac.lang = :lang AND ai.tag = :tag');
    $sth->execute(['lang' => $lang, 'tag' => $tag]);
    $ac = $sth->fetchColumn();
    if(isset($ac))
      return($ac);
    $sth->execute(['tag' => DEFAULTLANG]);
    $ac = $sth->fetchColumn();
    return($ac);
  }
  $sth = $pdo->prepare('SELECT value, tag, descr FROM appconfig ac
      JOIN appitem ai ON ac.appitemid = ai.id
     WHERE ac.lang = :lang');
  $sth->execute(['lang' => $lang]);
  $acs = $sth->fetchall(PDO::FETCH_ASSOC);
  
  $a = [];
  foreach($acs as $ac)
    $a[$ac['tag']] = $ac;
  return($a);
  
} /* end GetAppConfig() */


/* UpdateAppConfig()
 *
 *  Update a row in the 'appconfig' table. We currently only support
 *  an update on a row selected by appitem.tag and, optionally,
 *  appconfig.lang.  Best would be to also support appitemid.
 */

function UpdateAppConfig($update) {
  global $pdo;

  if(! array_key_exists('lang', $update))
    $update['lang'] = DEFAULTLANG;

  /* only perform an update if the value has changed */
  
  $ovalue = GetAppConfig($update['tag'], $update['lang']);
  if(isset($ovalue) && $ovalue == $update['value'])
    return(false);

  try {
    $sth = $pdo->prepare('UPDATE appconfig ac JOIN appitem ai ON ac.appitemid = ai.id SET value = :value WHERE tag = :tag AND lang = :lang');
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute($update);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  if(!$rv)
    Error('System error: could not update application configuration');
  return(true);
    
} /* end UpdateAppConfig() */


/* GetSessions()
 *
 *  Return an array of user/session data ordered by expiredate.
 */

function GetSessions() {
  global $pdo;
  $query = 'SELECT u.id AS uid, expiredate, email, username, fullname, expiredate < now() AS expired
 FROM phpauth_sessions s
  JOIN phpauth_users u ON s.uid = u.id
 ORDER BY expiredate DESC';
 
  try {
    $sth = $pdo->prepare($query);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  try {
    $rv = $sth->execute();
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  $sessions = $sth->fetchall(PDO::FETCH_ASSOC);
  return($sessions);
  
} /* end GetSessions() */


/* InsertVolunteer()
 *
 *  Create a record in the volunteer table.
 */

function InsertVolunteer($meta) {
  global $pdo;

  $fields = '';
  $values = '';

  foreach($meta as $f => $v) {
    if(strlen($fields)) {
      $fields .= ',';
      $values .= ',';
    }
    $fields .= $f;
    $values .= ":$f";
  }

  $sql = "INSERT INTO volunteer($fields) VALUES($values)";
  try {
    $sth = $pdo->prepare($sql);
    $rv = $sth->execute($meta);
  } catch(PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
  }
  return($rv);

} /* end InsertVolunteer() */


/* GetVolunteers()
 *
 *  Return the matching volunteer records.
 */

function GetVolunteers($filter = null) {
  global $pdo;

  $sql = 'SELECT v.*, email, isactive, dt, fullname, username, role
 FROM volunteer v
  LEFT JOIN phpauth_users u ON v.id = u.id';

  if(isset($filter)) {
    $conditions = '';
    foreach($filter as $name => $value) {
      if(strlen($conditions))
        $conditions .= ' AND ';
      $conditions .= "$name = $value";
    }
    $sql .= " WHERE $conditions";
  }
  $sth = $pdo->prepare($sql);
  $sth->execute();
  $volunteers = [];
  while($volunteer = $sth->fetch(PDO::FETCH_ASSOC))
    $volunteers[$volunteer['id']] = $volunteer;
  return $volunteers;

} // end GetVolunteers()


/* GetVolunteer()
 *
 *  Return the volunteer record matching the argument id, or false.
 */

function GetVolunteer($id) {
  $volunteers = GetVolunteers(['v.id' => $id]);
  if(count($volunteers))
    return $volunteers[$id];
  return false;

} // end GetVolunteer()


/* UpdateVolunteer()
 *
 *  Update a volunteer record.
 */

function UpdateVolunteer($update) {
  global $pdo;

  $id = $update['id'];
  $volunteer = GetVolunteer($id);
  $u = '';
  foreach($update as $column => $value) {
    if($column == 'id')
      continue;
    if($volunteer[$column] == $update[$column])
      unset($update[$column]);
    else {
      if(strlen($u))
        $u .= ',';
      $u .= "$column = :$column";
    }
  }
  if(strlen($u)) {

    # we found fields that changed

    $sql = "UPDATE volunteer SET $u WHERE id = :id";
    if(DEBUG) error_log($sql);
    try {
      $sth = $pdo->prepare($sql);
    } catch(PDOException $e) {
      throw new PDOException($e->getMessage(), $e->getCode());
    }
    if(!$sth->execute($update))
      Error("System error; update failed");
    return "Updated volunteer record.";
  } else
    return "No changes to volunteer record.";
  
} // end UpdateVolunteer()
