<?php
/* NAME
 *
 *  project/index.php
 *
 * CONCEPT
 *
 *  Pattern sphere application.
 *
 * FUNCTIONS
 *
 *  obsess        absorb an assessment submission
 *  assess        present the assessment form
 *  consideration view patterns under consideration
 *  inwork        view patterns in work
 *
 * $Id: index.php,v 1.47 2023/03/22 20:39:44 rose Exp $
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require "lib/ps.php";


/* obsess()
 *
 *  Absorb an assessment.
 *
 *  The per-passessment radio buttons are named with the pattern id and
 *  have values of 'on', 'off', or 'neutral'. The per-[assessment
 *  commentaries are named 'c<pid>'.
 *
 *  The 'assessment' fields - a comment and a checkbox - are named
 *  'acomment' and 'contact'.
 */

function obsess() {
  global $user, $project;

  # build an array of labels for the assessments
  #  0 => project.nulllable
  #  [1..n] => explode(project.labels)

  $labels = array_merge([$project['nulllabel']],
                        explode(':', $project['labels']));
			
  // Fetch the patterns used in this project.

  $patterns = GetProjPatterns($project['id'], true);

  # counts of patterns and each assessment value

  $counts = ['patterns' => count($patterns)];
  foreach($labels as $lid => $label) {
    $counts[$lid] = 0;
  }

  // Fetch the existing 'assessment' record, if any.

  $assessment = GetAssessment([
    'projid' => $project['id'],
    'userid' => $user['id']
  ]);

  $updates = [];
  $inserts = [];
  $deletes = [];
  
  // loop on patterns

  foreach($patterns as $pattern) {
  
    $pid = $pattern['id'];
    $apid = $pattern['apid'];
    
    if(isset($assessment)
        && isset($assessment['passessments'])
	&& isset($assessment['passessments'][$pid])) {
	
      // a 'passessment' record already exists; look for changes
      
      $passessment = $assessment['passessments'][$pid];
      $paid = $passessment['id'];

      // look at the commentary

      $newcom = isset($_POST["c$pid"]) ? $_POST["c$pid"] : '';
      $oldcom = $passessment['commentary'];
      if($newcom != $oldcom)
        $updates[$paid]['commentary'] = $newcom;
	
      // look at the radio buttons

      $newstate = isset($_POST[$pid]) ? $_POST[$pid] : '';
      if($newstate == 0)
          $newstate = null;
      else
         $newstate--;
      $oldstate = $passessment['assessment'];
      if($newstate != $oldstate)
        $updates[$paid]['assessment'] = $newstate;

      if(isset($updates[$paid])) {
        $updates[$paid]['id'] = $paid;

	// delete instead of update if there is no data

	if(! strlen(trim($newcom)) && is_null($newstate)) {
	  if(DEBUG) error_log("add passessment $paid to deletes");
	  array_push($deletes, $paid);
	  unset($updates[$paid]);
	}
      }
      
    } else {

      /* no 'passessment' record exists for this pattern; do an insert if
       * there is input data for it */
       
      if(isset($_POST[$pid]) && $_POST[$pid] != 'neutral')
        $inserts[$pid]['assessment'] = $_POST[$pid];
      if(isset($_POST["c$pid"]) && strlen($_POST["c$pid"]))
        $inserts[$pid]['commentary'] = $_POST["c$pid"];
      if(isset($inserts[$pid])) {
        $inserts[$pid]['pid'] = $pid;
        $inserts[$pid]['apid'] = $apid;
      }
    }
  } // end loop on patterns

  // The 'assessment' record has fields of its own.

  if(isset($_POST['acomment']) && strlen($_POST['acomment']))
    $acomment = $_POST['acomment'];
  else
    $acomment = '';
  if(isset($_POST['contact']) && $_POST['contact'])
    $contact = true;
      
  if(isset($assessment)) {

    // there is an existing 'assessment' record
    
    $assid = $assessment['id'];
    if(isset($acomment)) {
      if((isset($assessment['acomment']) &&
          $acomment != $assessment['acomment'])
          || !isset($assessment['acomment']))
	$update['acomment'] = $acomment;
      $ocontact = $assessment['contact'] == 'y';
      if($ocontact != $contact)
	$update['contact'] = $contact ? 'y' : 'n';
      if(isset($update)) {
        $update['id'] = $assid;
        UpdateAssessment($update);
      }
    }
  } else {
    if(count($inserts) || strlen($acomment)) {

      /* we have no existing 'assessment' record but do have inserts and/or
       * a comment, and/or permission to contact; create 'assessment' */

      $assessment = [
        'userid' => $user['id'],
        'projid' => $project['id']
      ];
      if(isset($acomment))
        $assessment['acomment'] = $acomment;
      if(isset($contact))
        $assessment['contact'] = 'y';
      $assid = InsertAssessment($assessment);
    } else {

      // no inserts, no comment, no permission to contact; do not create

      return;
    }
  }
  if(count($inserts)) {
    if(DEBUG) error_log(count($inserts) . ' inserts');
    foreach($inserts as $insert) {
      $insert['assid'] = $assid;
      InsertPAssessment($insert);
    }
  }
  if(count($updates)) {
    if(DEBUG) error_log(count($updates) . ' updates');
    foreach($updates as $update) {
      UpdatePAssessment($update);
    }
  }
  if(count($deletes)) {
    if(DEBUG) error_log(count($deletes) . ' deletes');
    foreach($deletes as $delete) {
      DeletePAssessment($delete);
    }
  }
  
  # Fetch the updated result.
  
  $assessment = GetAssessment([
    'projid' => $project['id'],
    'userid' => $user['id']
  ]);
  
  $ins = '';
  $outs = '';
  
  foreach($assessment['passessments'] as $passessment) {
  
    $vote = $passessment['assessment'];
    $pid = $passessment['pid'];
    $title = $patterns[$pid]['title'];
    
    if($vote == 'in') {
      $counts['in']++;
      $ins .= strlen($ins) ? ', ' : '';
      $ins .= $title;
    } else {
      $counts['out']++;
      $outs .= strlen($outs) ? ', ' : '';
      $outs .= $title;
    }
  } # end loop on passessments
  
  if(!strlen($ins)) $ins = '(none)';
  if(!strlen($outs)) $outs = '(none)';
  print '<p>Thank you for your thoughts. You have made 
decisions on ' . ($counts['in'] + $counts['out']) . ' of the ' .
  $counts['patterns'] . ' candidates. If you would like to ' .
  (($counts['in'] + $counts['out'] < $counts['patterns'])
   ? 'assess more patttern candidates, ' : '') .
 'change your answers or give more written input, you can <a href="./index.php?assess=1">
 edit</a> your responses - either now or at any time until the project
 closes.</p>
';

} /* end obsess() */


/* assess()
 *
 *  Present a form for soliciting an assessment or edits thereof.
 *
 *  If the user doesn't have a role, instead just show the patterns.
 */

function assess() {
  global $user, $project, $masq;

  # build an array of labels for the assessments
  #  0 => project.nulllable
  #  [1..n] => explode(project.labels)

  $labels = array_merge([$project['nulllabel']],
                        explode(':', $project['labels']));

  $participant = IsParticipant();
  
  // Fetch planguages implicated in this project.

  $planguages = GetProjPLanguages($project['id']);

  // Fetch the patterns used in this project.

  $patterns = GetProjPatterns($project['id']);

  if($participant) {
  
    // Fetch the existing 'assessment' record, if any.

    $assessment = GetAssessment([
      'projid' => $project['id'],
      'userid' => $user['id']
    ]);
    $title = 'Pattern Candidate Assessment';
  } else {
    $title = 'Pattern Candidates';
  }

  if($participant) {
    $welcome = '<p class="alert">Welcome' .
     (isset($assessment) ? ' back' : '') .
     " <span class=\"username\" title=\"{$user['email']}\">{$user['fullname']}</span></p>
<blockquote>
 {$project['assessment_text']}
</blockquote>
";
  } else {
    $welcome = '<p class="alert">Welcome. Below you can view the pattern set for this project.</p>
';
  }
  
  print "<h2>$title</h2>

$welcome
";
  if($participant) {
    print "<form id=\"assess\" method=\"POST\" action=\"{$_SERVER['SCRIPT_NAME']}\">
";
  }

  if($masq)
    print "<input type=\"hidden\" name=\"masq\" value=\"$masq\">\n";
  
  // Loop on languages.
  
  foreach($planguages as $planguage) {
    print "<div class=\"plang\">
 <div class=\"pltitle\">{$planguage['title']}</div>\n";

    // Loop on patterns in this language.
    
    foreach($patterns as $pattern) {
      if($pattern['plid'] != $planguage['id']) {
        continue;
      }
      $pid = $pattern['id'];
      if($participant) {
        $passessment = (isset($assessment) &&
                      isset($assessment['passessments']) &&
		      isset($assessment['passessments'][$pid]))
		      ? $assessment['passessments'][$pid] : null;
        $commentary = isset($passessment) ? $passessment['commentary'] : '';

	# If the 'passessment' record exists, the value of the
	# 'assessment' field is either null, or an integer. If it
	# doesn't exist, it's logically null. Set radio to the due
	# label for this passessment.

	$radio = (isset($passessment) && isset($passessment['assessment']))
	  ? $labels[$passessment['assessment']+1] : $labels[0];

	# Build the radio buttons in $radios here.

	$radios = '';
	foreach($labels as $lid => $state) {
	  if($radio == $state)
	    $checked = ' checked';
	  else
	    $checked = '';
	  $id = $pid . '_' . $lid;
	  $radios .= strlen($radios) ? "<br>\n    " : '';
	  $radios .= "<label for=\"$id\"><input type=\"radio\" id=\"$id\" name=\"$pid\" value=\"$lid\"$checked>$state</label>";
	  
	} // end loop on radio buttons
	
	print "
  <div class=\"pattern $radio\">
   <div class=\"ptitle\">{$pattern['title']}</div>
   <div class=\"synopsis\">{$pattern['synopsis']}</div>
   <div class=\"radios\">
    $radios
   </div>
   <div><textarea class=\"comm\" maxlength=\"1024\" title=\"Enter commentary\" placeholder=\"optional commentary\" name=\"c{$pattern['id']}\" rows=\"1\" cols=\"30\">$commentary</textarea></div>
  </div>
  ";
//        print "</div>\n";
      } else {

        // visitor

	print "<div class=\"pattern neutral\">
 <div class=\"ptitle\">{$pattern['title']}</div>
 <div class=\"synopsis\">{$pattern['synopsis']}</div>
</div>
";
      }
      
    } // end loop on patterns

    print "</div>\n";
    
  } // end loop on planguages

  if($participant) {
    $acomment = (isset($assessment) && isset($assessment['acomment'])) ? $assessment['acomment'] : '';
    $contact = (isset($assessment) && $assessment['contact'] == 'y')
      ? ' checked' : '';
  
    print "{$project['abovecomment_text']}
<blockquote>
<textarea class=\"comm\" rows=\"4\" cols=\"80\" name=\"acomment\">$acomment</textarea>
</blockquote>
<p class=\"alert\">May we contact you about your assessment? <input type=\"checkbox\" name=\"contact\"$contact></p>
 {$project['parting_text']}
<div align=\"center\">
 <input type=\"submit\" name=\"submit\" value=\"Save assessment\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>

</form>
<script>
  let inputs = document.querySelectorAll('input[type=text], input[type=radio], input[type=checkbox]')
  for(input of inputs) {
    input.addEventListener('keypress', (e) => {
      if(e.key === 'Enter')
        event.preventDefault()
    })
  }
</script>
";
  } else {

    // visitor
    
    print '<p class="alert"><a href="' . ROOTDIR . '">Return</a>.</p>
';
  }
    
} /* end assess() */


/* consideration()
 *
 *  View patterns under consideration.
 */

function consideration() {
  global $project;

  $paragraphs = [
    [
      'column' => 'synopsis',
      'destination' => false,
      'label' => 'Brief Description'
    ],
    [
      'column' => 'problem',
      'destination' => false,
      'label' => 'Problem'
    ],
    [
      'column' => 'discussion',
      'destination' => true,
      'label' => 'Discussion'
    ],
    [
      'column' => 'context',
      'destination' => false,
      'label' => 'Context'
    ],
    [
      'column' => 'solution',
      'destination' => false,
      'label' => 'Seed'
    ]
  ];
    
  print "<h2>{$project['title']} Patterns - In Work</h2>
";

  $results = Stats();
  $patterns = $results['bypid'];
  $ovs = '';

  $labels = explode(':', $project['labels']);
  
  foreach($patterns as &$pattern) {

    print "<h3 style=\"background-color: #eee; width: max-content\" title=\"{$pattern['pltitle']}\">{$pattern['ptitle']}</h3>
";

    $assstring = '';
    foreach($labels as $lid => $label) {
      if(strlen($assstring))
        $assstring .= ' / ';
      $assstring .= "{$pattern['assess'][$lid]} $label";
    }

    $vs = "<div style=\"background-color: #fcc; padding: 3px\">$assstring</div>\n";
    if($vs != $ovs) {
      print $vs;
      $ovs = $vs;
    }
  
    $p = GetPattern(['p.id' => $pattern['pid']]);
    $p = $p[0];
    $destination = $p['plid'] == $project['destination'];

    foreach($paragraphs as $paragraph) {

      /* if 'destination' is true, only display this paragraph if the pattern
       * is in the destination language */
       
      if($paragraph['destination'] && !$destination)
        continue;
      if(strlen($p[$paragraph['column']])) {
        print "<h4>{$paragraph['label']}</h4>
<blockquote style=\"white-space: pre-line\">{$p[$paragraph['column']]}</blockquote>
";
      }
    }
  } /* end loop on patterns */

  print '<p><a href="./">Return to the project</a>.</p>
';
  
} /* end consideration() */


/* inwork()
 *
 *  View a pattern in work. The argument is projpattern.id.
 */

function inwork($id) {
  $ap = GetProjPattern($id);

  # Look for images associated with this pattern.

  $images = [];
  $pats = [
    'intro' => '/^i_' . $ap['id'] . '\..*$/',
    'closing' => '/^c_' . $ap['id'] . '\..*$/'
  ];
  $dh = opendir('../images');
  
  while($direntry = readdir($dh))
    foreach($pats as $tag => $pat)
      if(preg_match($pat, $direntry))
        $images[$tag] = IMAGEROOT . "/$direntry";

  closedir($dh);

  print "<h2>{$ap['title']}</h2>

<div class=\"gg\">
";

  if(array_key_exists('intro', $images))
    // display intro image
    print "<div style=\"grid-column: span 2\">
<img src=\"{$images['intro']}\" class=\"icenter\" title=\"introductory image\">
</div>
";

  print "<div class=\"fieldlabel\">Language:</div>
<div style=\"font-style: oblique\">{$ap['pltitle']}</div>
";

  // display pattern metadata

  foreach([
    'synopsis' => 'Brief description',
    'discussion' => 'Discussion',
    'context' => 'Context',
    'solution' => 'Seed'
  ] as $field => $label) {
    $value = $ap[$field];
    if(!strlen($value))
      $value = '(no value)';
    print "<div class=\"fieldlabel\">$label:</div>
<div>$value</div>
";
  } // end loop on metadata fields
  
  if(array_key_exists('closing', $images))
    // display closing image
    print "<div style=\"grid-column: span 2\">
    <img src=\"{$images['closing']}\" class=\"icenter\" title=\"closing image\">
 </div>
 ";

  // Display any pattern comments. Those are passessment.commentary.

  $passessments = GetPassessments($id);

  print '<div class="fieldlabel">Commentary:</div>
<div>
';
  $ccount = 0;
  foreach($passessments as $passessment) {
    if(strlen($passessment['commentary'])) {
      print "<div style=\"background-color: #ddd; margin: .5em\">
  {$passessment['commentary']}
</div>
";
      $ccount++;
    }
  }
  if(!$ccount) {
    print 'No commentary.';
  }
  print "</div>
</div>

<p><a href=\"./\">Return</a>.</p>
";  

} /* end inwork() */


/* Main program. */

DataStoreConnect();
Initialize();

if(! isset($project) || !is_array($project)) {

  // can't find a project record for this path.
  
  # print "<p class=\"error\">Fatal error: cannot identify project.</p>\n<p><a href=\"../\">Continue.</a></p>\n";
  header('Location: ../');
  exit();
}

if($isLogged = $auth->isLogged()) {
  $userinfo = $auth->getCurrentSessionUserInfo();
  $timestamp = strtotime($userinfo['expiredate']);
  $expire = "<script>\n let exptime = $timestamp\n</script>\n";
} else
  $expire = '';

$SuppressMain = false;
if(DEBUG) error_log(var_export($_REQUEST, true));

print "<!doctype html>
<html lang=\"en\">

<head>
 <title>{$project['title']}</title>
 <link rel=\"stylesheet\" href=\"lib/ps.css\">
 <script src=\"lib/ps.js\"></script>
 $expire
</head>

<body>

<header>
<h1>{$project['title']}</h1>
</header>

<div id=\"poutine\">
<img src=\"../images/pattern-sphere-band.png\" id=\"gravy\">
";

if($isLogged) {

  // This user has a login.
  
  $user = $auth->getCurrentUser(true);
  $actual = $user;
  
  if($user['role'] == 'super' &&
     isset($_REQUEST['masq']) && $_REQUEST['masq']) {

    // masquerade as this user

    $masq = $_REQUEST['masq'];
    $user = $auth->getUser($masq);
    if(DEBUG) error_log("masquerading as {$user['fullname']}");
  }

  $action = array(' <li><a href="../log.php">Log out</a></li>',
    ' <li><a href="../profile.php">Edit profile</a></li>');

  if($user['role'] == 'manager' || $user['role'] == 'super') {

    $manager= '<div id="manager">
<div class="banner">Manager actions</div>
 <ul>
  <li><a href="teams.php">Manage teams</a></li>
  <li><a href="projpatterns.php">Manage patterns</a></li>
  <li><a href="results.php">View results</a></li>
 </ul>
</div>
';
  }

  if($user['role'] == 'user') {
    $instructions = $project['user_text'];
    $manager = $super = '';
  } else {
    $instructions =
      $project['user_text'] .
      $project['manager_text'];
  }
  
} else {

  // is not authenticated
  
  $action = [
             '<li><a href="../register.php">Register</a></li>',
             '<li><a href="../log.php">Log in</a></li>',
             '<li><a href="../reset.php">Reset password</a></li>'
	    ];
  $super = $manager = '';
  $instructions = $project['visitor_text'];
}

if(isset($_REQUEST['assess'])) {

  /* present the assessment form */

  assess();
  $SuppressMain = true;
  
} elseif(isset($_REQUEST['consideration'])) {

  /* View patterns under consideration. */
  
  consideration();
  $SuppressMain = true;

} elseif(isset($_POST['submit']) && $_POST['submit'] == 'Save assessment') {

  /* absorb assessment form submission */

  obsess();
} elseif(isset($_REQUEST['inwork'])) {

  /* display in-work pattern */

  inwork($_REQUEST['inwork']);
  $SuppressMain = true;
}

$actions = join('', $action);

if(!$SuppressMain) {
?>

<div id="actions">
<div id="authactions">
<div class="banner">User actions</div>
<ul>
<?=$actions?>
</ul>
</div>
<?=$manager?>
<?=$super?>
</div>

<h2>Welcome to the <span class="apptitle"><?=$project['title']?></span> project.</h2>

<div id="instructions" class="instructions">
<p><?=$instructions?></p>
</div>
<?php
  if(isset($user))
    $participant = IsParticipant();
    
  $querystring = 'assess=1';
  $querystring .= (isset($masq) && $masq) ? "&masq=$masq" : '';

  if(isset($user))
    $edit = GetAssessment([
      'userid' => $user['id'],
      'projid' => $project['id']
    ]) ? true : false;
  else
    $edit = false;

  if($participant) 
    print '<p>Welcome ' .
      ($edit ? 'back ' : '') .
      "<span class=\"username\" title=\"{$user['email']}\">{$user['fullname']}</span>. " .
       ($edit ? 'Edit your' : 'Submit an') .
      " assessment by clicking <a href=\"?$querystring\">here</a>.</p>
";

  if($participant || (isset($user) && $user['role'] == 'super')) {
    print "<p><a href=\"?consideration=1\">View patterns under consideration</a>.</p>
<h3>Patterns in work</h3>
";
    $projpatterns = GetProjPatterns($project['id']);
    $count = 0;
    foreach($projpatterns as $projpattern) {
      if($projpattern['status'] == 'inwork') {
        if(!$count)
	  print "<ul>\n";
	$count++;
        print "<li><a href=\"?inwork={$projpattern['apid']}\">{$projpattern['title']}</a></li>\n";
      }
    }
    if($count)
      print "</ul>\n";
    else
      print "<p style=\"font-style: oblique; margin-left: 1em\">None yet.</p>\n";
  }
  if(! $participant)
    if(isset($user))
      print "<p>Welcome <span class=\"username\" title=\"{$user['email']}\">{$user['fullname']}</span>.
";
    else
      print "<p>Welcome, visitor.\n";
    print "To enter an assessment, you must be a member of this project. You
 can view the pattern set <a href=\"?$querystring\">here</a>.</p>
";

  print "<p>Visit the <a href=\"../\">project directory</a>.</p>
";

  if(isset($actual) && $actual['role'] == 'super') {

    // Offer supers the ability to masquerade.
      
    $users = GetUsers();
    $masq = isset($_REQUEST['masq']) ? $_REQUEST['masq'] : 0;
    $nullopt = ($masq) ? 'Stop masquerading' : 'Choose a user';
    print "<div class=\"masq\"><p class=\"alert\">You can masquerade as a different user.</p>
 <form action=\"./\" method=\"POST\">
 <select name=\"masq\">
 <option value=\"0\"" . ($masq ? '' : 'selected') . ">$nullopt</option>
";
    foreach($users as $user) {
      $uid = $user['uid'];
      $style = ($user['role'] == 'super') ? 'font-weight: bold' : '';
      print "<option style=\"$style\" value=\"$uid\"" . 
	  (($masq == $uid) ? ' selected' : '') .
	  ">{$user['fullname']} ($uid)</option>\n";
    }
    print '</select>
  <input type="submit" name="submit" value="Masquerade">
  </form>
  <div>
  ';
  
  } // end role == super
    
} // end suppress main page

?>
</div>
<?=FOOT?>
</body>
</html>
