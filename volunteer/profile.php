<?php
/* NAME
 *
 *  volunteer/profile.php
 *
 * CONCEPT
 *
 *  Volunteer interest form.
 *
 * FUNCTIONS
 *
 *  Interests       grid of interests in radio buttons
 *  Volunteer       detail this volunteer
 *  ListVolunteers  table of volunteers
 *  radio           form-construction helper
 *
 * NOTES
 *
 *  A visit to this page by an unauthenticated user displays a form that
 *  solicits information needed to build both a phpauth_users and volunteer
 *  record. Submitting the form creates those records. If the query parameter
 *  'status' is present with the value 'approved', the record in phpauth_users
 *  is activated.
 *
 *  A visit by an authenticated user checks to see if they have a volunteer
 *  record. If so, they edit that record; if not, the record is created.
 *
 *  A visit by an authenticated user with the 'super' role also offers a
 *  link to display a list of volunteers. Each volunteer in the list is
 *  linked to a detail page.
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
preg_match('$(.+)/volunteer/profile.php$', $_SERVER['SCRIPT_FILENAME'], $matches);
set_include_path(get_include_path() . PATH_SEPARATOR . $matches[1] . '/project');
require 'lib/ps.php';

DataStoreConnect();
Initialize();


if($isLogged = $auth->isLogged()) {

  // An existing user, possibly with an existing volunteer record.
  
  $user = $auth->getCurrentUser(true);
  $userdata = GetUser($user['id']);
  $super = ($userdata['role'] == 'super') ? true : false;
  $volunteer = GetVolunteer($user['id']);
}


define('RADIO', true); # display interests in volunteer detail as radio buttons


/* Interests()
 *
 *  Grid of interests.
 */

function Interests($volunteer) {
  $Interests = [
    [
      'name' => 'ui',
      'label' => 'Page and Site UI',
    ],
    [
      'name' => 'design',
      'label' => 'Process and Site Design',
    ],
    [
      'name' => 'ux',
      'label' => 'UX Design and Research',
    ],
    [
      'name' => 'code',
      'label' => 'Database and Coding',
    ],
    [
      'name' => 'market',
      'label' => 'Outreach and Marketing',
    ],
    [
      'name' => 'pm',
      'label' => 'Project Management',
    ],
    [
      'name' => 'vs',
      'label' => 'Volunteer Support',
    ],
    [
      'name' => 'content',
      'label' => 'Content Development',
    ]
  ];
  
  print '<div class="int">
  <div></div>
  <div class="ir">Very Low</div>
  <div class="ir" style="grid-column: span 3"></div>
  <div class="ir">Very High</div>
';

  foreach($Interests as $radio) {
    $value = (isset($volunteer) && isset($volunteer[$radio['name']]))
      ? $volunteer[$radio['name']] : null;
    print "<div class=\"i\">{$radio['label']}</div>\n" .
      radio($radio['name'], range(1,5), 'ir', $value, true);
  }
  print "</div>\n";

} // end Interests()


/* Volunteer()
 *
 *  Display all the details for this volunteer.
 */

function Volunteer($id) {
 print "<div style=\"margin-left: 4vw\">\n";

  if(!($volunteer = GetVolunteer($id))) {
    print "<p>System error: failed to locate this volunteer record.</p>
<p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>
</div>
";
    return false;
  }
  print "<h1>Volunteer Details for <em>{$volunteer['fullname']}</em></h1>

<div id=\"deetz\">

 <div class=\"dhh\">Basic Information</div>

 <div class=\"dh\">Fullname:</div>
 <div>{$volunteer['fullname']}</div>

 <div class=\"dh\">Activated:</div>
 <div>" . ($volunteer['isactive'] ? 'yes' : 'no') . "</div>

 <div class=\"dh\">Username:</div>
 <div>{$volunteer['username']}</div>

 <div class=\"dh\">Email:</div>
 <div>{$volunteer['email']}</div>

 <div class=\"dh\">How heard:</div>
 <div>{$volunteer['heard']}</div>
 
 <div class=\"dh\">Comments/questions:</div>
 <div>{$volunteer['comments']}</div>

 <div class=\"dh\">Share publicly:</div>
 <div>" . ($volunteer['public'] ? 'yes' : 'no') . "</div>

 <div class=\"dh\">Seeks recommendation:</div>
 <div>" . ($volunteer['recommend'] ? 'yes' : 'no') . "</div>

 <div class=\"dh\">Registered:</div>
 <div>{$volunteer['dt']}</div>

 <div class=\"dh\">Modified:</div>
 <div>{$volunteer['modified']}</div>
 <div class=\"dhh\">Interests</div>
 <div style=\"grid-column: span 2\">\n";
 
  Interests($volunteer);
  
  print " </div>
 <div class=\"dh tall\">Other interests:</div>
 <div>{$volunteer['other']}</div>
 <div class=\"dh tall\">Additional comments:</div>
 <div>{$volunteer['icomments']}</div>

 <div class=\"dhh\">Committment</div>

 <div class=\"dh\">Start date:</div>
 <div>{$volunteer['start']}</div>

 <div class=\"dh\">End date:</div>
 <div>{$volunteer['end']}</div>

 <div class=\"dh\">Weekly hours:</div>
 <div>{$volunteer['hours']}</div>

 <div class=\"dhh\">Demographics</div>

 <div class=\"dh\">Gender:</div>
 <div>{$volunteer['gender']}</div>

 <div class=\"dh\">Age:</div>
 <div>{$volunteer['age']}</div>

 <div class=\"dh\">Nationality:</div>
 <div>{$volunteer['nationality']}</div>

 <div class=\"dh\">Location:</div>
 <div>{$volunteer['location']}</div>

 <div class=\"dh\">Training:</div>
 <div>{$volunteer['training']}</div>

</div>
";

  print "<p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>
</div>
";

} // end Volunteer()


/* ListVolunteers()
 *
 *  Display a table of volunteers.
 */

function ListVolunteers() {
  $volunteers = GetVolunteers();
  if(!count($volunteers)) {
    print "<p>No volunteers found. <a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>\n";
    return false;
  }
  $count = [
    'active' => 0,
    'inactive' => 0
  ];
  print "<div style=\"margin-left: 4vw\">
<h1>Volunteers</h1>

<div id=\"vlist\">
 <div class=\"vh\">ID</div>
 <div class=\"vh\">Fullname</div>
 <div class=\"vh\">Username</div>
 <div class=\"vh\">Active</div>
 <div class=\"vh\">Registered</div>
";

  foreach($volunteers as $volunteer) {
    $active = $volunteer['isactive'] ? 'yes' : 'no';
    $count[$volunteer['isactive'] ? 'active' : 'inactive']++;
    $class = $volunteer['isactive'] ? '' : ' class="vinactive"';
    print "<div$class>
  <a href=\"?id={$volunteer['id']}\">{$volunteer['id']}</a>
 </div>
 <div$class>{$volunteer['fullname']}</div>
 <div$class>{$volunteer['username']}</div>
 <div$class>$active</div>
 <div$class>{$volunteer['dt']}</div>
";
  }
  print "</div>
 <p>There are {$count['active']} activated and {$count['inactive']}
 unactivated volunteers.</p>
 <p><a href=\"{$_SERVER['SCRIPT_NAME']}\">Continue</a>.</p>
</div>
";
  
  return true;
  
} // end ListVolunteers()


/* radio()
 *
 *  Generate a set of radio buttons with selected name, values, classes, and
 *  default.
 */
 
function radio($name, $values, $class, $checked, $required, $labels = null) {
  $rv = '';
  
  foreach($values as $value) {
    if(isset($class))
      $rv .= "<div class=\"$class\">";
    $rv .= "<input type=\"radio\" name=\"$name\" value=\"$value\"";
    if($required) {
      $rv .= ' required';
      $required = false;
    }
    $rv .= ($value == $checked) ? ' checked' : '';
    $rv .= '>';
    if(isset($labels) && isset($labels[$value]))
      $rv .= $labels[$value];
    if(isset($class))
      $rv .= '</div>';
    $rv .= "\n";
  }
  return $rv;
  
} // end radio()


?>
<!doctype html>
<html lang="en">
<head>
  <title>Volunteer Interest Form</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 0;
    }
    .farnsworth {
      width: 200px;
      height: 12px;
      border: 1px solid black;
    }
    /* div { border: 1px dotted grey; } */
    #deetz {
      display: grid;
      grid-template-columns: repeat(2, max-content);
      width: max-content;
      column-gap: .5vw;
   }
    .dh {
      font-weight: bold;
      text-align: right;
    }
    .tall {
      margin-top: .5em;
    }
    .dhh {
      font-size: 15pt;
      font-weight: bold;
      text-align: center;
      grid-column: span 2;
      background-color: #eee;
      margin-top: 1vh;
      margin-bottom: .5vh;
    }
    #vlist {
      display: grid;
      grid-template-columns: repeat(5, max-content);
      width: max-content;
      border: 2px solid #660;
      margin-bottom: 1em;
    }
    .vinactive {
      background-color: #eee;
    }
    #vlist div {
      padding: .4em;
      text-align: center;
    }
    .vh {
      background-color: #ffc;
      border: 1px solid #660;
      font-weight: bold;
    }
    #congrats {
      font-size: 16pt; font-weight: bold;
      margin-top: 10vh;
      margin-bottom: 5vh;
    }
    p {
      color: #444;
    }
    #heading {
      display: grid;
      grid-template-columns: auto repeat(5, max-content);
      column-gap: 1vw;
      background-color: #eee;
      padding: .2em;
    }
    #partook {
      margin-right: 10vw;
      margin-left: 10vw;
    }
    #perchance {
      margin-right: 10vw;
      margin-left: 10vw;
      text-align: center;
      height: 10vh;
    }
    #doug {
      text-align: center;
      color: #444;
      margin-top: 50vh;
    }
    .fs {
      font-weight: 700;
      font-size: 14pt;
      margin-top: 3em;
      margin-bottom: 1em;
    }
    .rstar {
      color: #a00;
    }
    .fh {
      color: #444;
      margin-top: .6vh;
      font-size: 11pt;
    }
    .int {
      margin-left: 2vw;
      display: grid;
      grid-template-columns: max-content repeat(5, 6em);
    }
    .ir {
      text-align: center;
      color: #444;
    }
    .i {
      font-size: 11pt;
      color: #444;
    }
    #opt {
      display: grid;
      grid-template-columns: repeat(2, max-content);
      color: #444;
      column-gap: .5vw;
      row-gap: .5vh;
      margin-left: 2vw;
    }
    .o {
      text-align: right;
    }
    #submit {
      color: white;
      background-color: black;
      font-weight: bold;
      font-size: 16pt;
      border-radius: 1em;
      padding: .4em;
      margin: 1.5em;
      float: right;      
    }
  </style>
</head>
<body>

<div id="heading">
 <div>Public Sphere Project</div>
 <div>About Us</div>
 <div>Our Team</div>
 <div><a href="./">Volunteer Resources</a></div>
 <div><a href="profile.php">Sign Up</a></div>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST') {

  // Absorbing a form submission.

  if($isLogged) {
  
    // existing user
    
    $uid = $user['id'];

  } else {
  
    // New user
    
    $username = strtolower(trim($_POST['username']));
    $email = strtolower(trim($_POST['email']));

    if($auth->isEmailTaken($email)) {

      // An existing email.

      print "<p>We already have a user <code>$email</tt>.</p>\n";
      exit();

    } else if(IsUsernameTaken($username)) {

      // An existing username.

      print "<p>We already have a user with the username <code>$username</tt>. Pick another.</p>\n";
      exit();

    } else if(! IsUsernameValid($username)) {

      // An invalid username.
    
      print "<p>We require a username to consist of between four and twenty characters consisting of lower-case letters, digits, underscores, and dashes. Choose another.</p>\n";
      exit();

    } else {

      // Validate fullname.
    
      $fullname = trim($_POST['fullname']);
      $password = trim($_POST['password']);

      if(!strlen($fullname)) {
        print "<p>We require that you include your name.</p>\n";
        exit();
      }
    }

    // Register.

    $activate = isset($_POST['status']) ? 1 : 0;
  
    $rval = $auth->register($email,
                           $password,
                           $password,
                           [
                             'fullname' => $fullname,
                             'username' => $username,
			   ],
			   '',
			   $activate);
    if($rval['error']) {
      print "<p>Registration failed: " . $rval['message'] . "</p>\n";
      exit();
    }
    $uid = $rval['uid'];
    
  } // end case of new user
  
  // required fields
  
  $meta = [
    'id' => $uid,
    'heard' => trim($_POST['heard']),
    'start' => trim($_POST['start']),
    'end' => trim($_POST['end']),
    'hours' => trim($_POST['hours']),
    'ui' => $_POST['ui'],
    'design' => $_POST['design'],
    'ux' => $_POST['ux'],
    'code' => $_POST['code'],
    'market' => $_POST['market'],
    'pm' => $_POST['pm'],
    'vs' => $_POST['vs'],
    'content' => $_POST['content'],
  ];

  // boolean default-false fields
  
  foreach(['recommend', 'public'] as $f)
    if($_POST[$f])
      $meta[$f] = 1;

  // optional fields

  foreach(['gender', 'age', 'location', 'nationality', 'training',
           'other', 'comments', 'icomments'] as $f)
    if(isset($_POST[$f]) && strlen($_POST[$f]))
      $meta[$f] = trim($_POST[$f]);

  if($volunteer) {

    // updating a volunteer record
    
    $result = UpdateVolunteer($meta);
    print "<div id=\"perchance\">
$result
</div>
";

  } else {

    // create a volunteer record
    
    $rval = InsertVolunteer($meta);
    if(!$rval) {
      print "<p>Creating volunteer record failed</p>\n";
      exit();
    }
    print "<div id=\"perchance\">

<div id=\"congrats\">CONGRATULATIONS</div>

<div>Thanks for volunteering for the PS project! Your account has been created.
We will contact you when your account is activated. At that point, you will
have access to volunteer resources and to the Volunteer Project within the
PS.</div>

<div id=\"doug\">douglas@publicsphereproject.org</div>

</div>
";
  }

} elseif($super && $_GET['id']) {

  // Details for this volunteer.

  Volunteer($_GET['id']);
  
} elseif($super && $_GET['list'] == 'all') {

  // display a list of volunteers
  
  ListVolunteers();
  
} else {

  // Presenting a form.

  $status = (isset($_GET['status']) && $_GET['status'] == 'approved')
    ? '<input type="hidden" name="status" value="approved">'
    : '';

  // this array is used to populate the form if the volunteer record exists

  $values = [
    'heard' => '',
    'start' => '',
    'end' => '',
    'hours' => '',
    'other' => '',
    'icomments' => '',
    'comments' => '',
    'age' => '',
    'location' => '',
    'nationality' => '',
    'training' => '',
  ];

  if($isLogged) {

    // this is an existing, authenticated PS user
    
    if($super)
      $listem = "<a href=\"?list=all\">Display a list of volunteers</a>";
    $log = "Welcome back <em>{$userdata['fullname']}</em>";
    $fullname = $userdata['fullname'];
    $email = $userdata['email'];
    $cemail = '';
    $usernamef = "<div class=\"fh\">Your username:</div>
 <div>{$userdata['username']}</div>\n";
    $passwordf = '';
    if($isLogged)
      print '<script>
 isLogged = true
</script>
';

    if($volunteer) {

      // this is an existing volunteer updating their record

      $values['heard'] = 'value="' . $volunteer['heard'] . '"';
      $values['start'] = 'value="' . $volunteer['start'] . '"';
      $values['end'] = 'value="' . $volunteer['end'] . '"';
      $values['hours'] = 'value="' . $volunteer['hours'] . '"';
      $values['other'] = 'value="' . $volunteer['other'] . '"';
      $values['age'] = 'value="' . $volunteer['age'] . '"';
      $values['location'] = 'value="' . $volunteer['location'] . '"';
      $values['nationality'] = 'value="' . $volunteer['nationality'] . '"';
      $values['training'] = 'value="' . $volunteer['training'] . '"';

      $values['icomments'] = $volunteer['icomments'];
      $values['comments'] = $volunteer['comments'];
    }

  } else {

    // this is a new PS user

    $listem = '';
    $log = '<a href="../log.php">Login</a> if you already have a PS account';
    $fullname = '<input type="text" name="fullname" id="fullname" required>';
    $email = '<input type="text" name="email" id="email" required>';
    $cemail = '<div class="fh" id="celabel">Please confirm Email address <span class="rstar">*</span></div>
<div><input type="text" name="cemail" id="cemail" required></div>';
    $usernamef = '<div class="fh" id="unlabel">Preferred user name for Pattern Sphere Project login (2 characters minimum) <span class="rstar">*</span></div>
<div><input type="text" name="username" id="username" required></div>';
    $passwordf = '<div class="fh" id="pwlabel">Preferred password for Pattern Sphere Project login (8 characters minimum) <span class="rstar">*</span></div>
<div><input type="password" name="password" id="password" required></div>';
  }
?>

<div id="partook">

<h1>Volunteer Interest Form</h1>

<p>For patterns and pattern languages to have lasting and tangible effects, many capabilities need to be developed, including federated pattern language repositories, support for collaboration, team workspace, search capabilities, pattern sharing, and many others. We need input and skills, dedication and creativity, in many areas to make this work. Thank you!</p>

<?= $listem ?>

<p>Required fields are marked with <span class="rstar">*</span>.</p>

<form method="POST" action="profile.php" id="form">
<?=$status?>

<div class="fs">A. Personal Information</div>

<div class="fh"><?= $log ?></div>
<div class="fh" id="fnlabel">Your Full Name <span class="rstar">*</span></div>
<div><?= $fullname ?></div>
<div class="fh" id="emlabel">Email address <span class="rstar">*</span></div>
<div><?= $email ?></div>
<?= $cemail ?>
<div class="fh">How did you hear about the project? <span class="rstar">*</span></div>
<div><input type="text" name="heard" <?= $values['heard'] ?> id="heard" size="128" required></div>
<?= $usernamef ?>
<?= $passwordf ?>
<div class="fs">B. Volunteer Contribution</div>

<?php

  // These two sets of radio buttons have no/yes values and default to no.

  $radios = [
    [
      'name' => 'public',
      'head' => 'Should your name be included on the public list of volunteers?',
    ],
    [
      'name' => 'recommend',
      'head' => 'Will you probably be requesting a letter of recommendation for
 your volunteer work? Note that a bit more discussion and formality will be
 required if you answer Yes.',
    ],
  ];

  foreach($radios as $radio) {
    $value = (isset($volunteer) && isset($volunteer[$radio['name']]))
      ? $volunteer[$radio['name']] : 0;
    print "<div class=\"fh\">{$radio['head']}</div>
<div style=\"margin-left: 1em\">\n" .
      radio($radio['name'], range(0,1), null, $value, true, [0 => 'no', 1 => 'yes']) .
"</div>\n";      
  }
?>
<div class="fh">When will you be available for volunteering?</div>
<div>
 <span class="fh">Approx Start Date</span> <span class="rstar">*</span> <input type="text" name="start" id="start" <?= $values['start'] ?> required>
 <span class="fh">Approx End Date</span> <span class="rstar">*</span> <input type="text" name="end" id="end" <?= $values['end'] ?> required>
</div>
<div class="fh">How many hours per week can you commit to the program? <span class="rstar">*</span></div>
<div><input type="text" name="hours" id="hours" size=3 style="margin-left: 2vw" <?= $values['hours'] ?> required></div>
<div class="fh">Please rate your interests in the following areas: <span class="rstar">*</span></div>
<?php
  Interests($volunteer);
?>
  <div class="i tall">Other (please specify)</div>
  <div style="grid-column: span 5" class="tall">
   <input type="text" name="other" <?= $values['other'] ?> size="60" style="margin-left: 2vw">
  </div>

<p>Additional comments about your interests</p>
<textarea name="icomments" cols="80" rows="4" style="margin-left: 2vw"><?= $values['icomments'] ?></textarea>

<div class="fs">C. Volunteer Introduction and Agreement</div>

<p>
Welcome! We really appreciate your interest in volunteering for the
Pattern Sphere (PS) project. The PS is an app that is intended to
support groups and individuals working on social and environmental
issues using patterns. This is an all-volunteer effort of the Public
Sphere Project, a US-based non-profit educational organization. We
think big but we're limited in other resources. This has many
implications. For one thing, it means that all of us are part of
the design process: We don't know exactly what the PS will look
like — or all of the services it will provide. All of us have to
keep thinking of innovative ways to move ahead. Also, it may take
a while for you to get acclimated to the work but, with your help,
we will find useful and interesting tasks for you. When you work
on the project, please do your best to familiarize yourself with
the project and to get acquainted with other volunteers. We will
be happy to acknowledge your contribution appropriately. And,
finally, if you do need to stop volunteering at some point, please
let us know.
</p>

<p>More info about patterns and this project can be found <a
href="https://limits.pubpub.org/pub/pattern/release/1">here</a>. Also,
soon, there will be a page specifically for volunteers.</p>

<p style="margin-left: 2vw"><input type="checkbox" id="agree" name="agree" required> I understand and agree <span class="rstar">*</span></p>

<p>Comments or Questions</p>
<textarea rows="3" cols="80" name="comments" style="margin-left: 2vw"><?= $values['comments'] ?></textarea>

<div class="fs">D. Optional Section</div>

<div id="opt">

<?php
  $value = (isset($volunteer) && isset($volunteer['gender']))
    ? $volunteer['gender'] : null;
  print "  <div class=\"o\">Gender:</div>\n  <div>\n" .
    radio('gender', ['male', 'female', 'nb', 'other'], null, $value, false,
          ['male' => 'male', 'female' => 'female', 'nb' => 'nb', 'other' => 'other']) .
    "</div>\n";
?>

  <div class="o">Age:</div>
  <div><input type="text" name="age" size="2" <?= $values['age'] ?>></div>

  <div class="o">Current Residence:</div>
  <div><input type="text" name="location" size="30" <?= $values['location'] ?>></div>

  <div class="o">Nationality:</div>
  <div><input type="text" name="nationality" size="30" <?= $values['nationality'] ?>></div>
  
  <div class="o">Current Degree/Course:</div>
  <div><input type="text" name="training" size="30" <?= $values['training'] ?>></div>

</div>

<input id="submit" type="submit" name="submit" value="Submit">

</form>

</div>

<script>

  const form = document.querySelector('#form')

  // Elements for which input is required.
  
  const agree = document.querySelector('#agree')       // checkbox

  // Elements not used for existing accounts.

  const email = document.querySelector('#email')       // text
  const cemail = document.querySelector('#cemail')     // text
  const username = document.querySelector('#username') // text
  const password = document.querySelector('#password') // password
  const emlabel = document.querySelector('#emlabel')
  const celabel = document.querySelector('#celabel')
  const fullname = document.querySelector('#fullname')
  const fnlabel = document.querySelector('#fnlabel')
  const pwlabel = document.querySelector('#pwlabel')
  const unlabel = document.querySelector('#unlabel')

  // Values.
  
  const labelcolor = fnlabel.style.color

  // Validate input on form submission.

  form.addEventListener('submit', (event) => {
    let error = ''
    const re = /^.+@.+\..+$/

    if(! isLogged) {

      // new user - validate email, username, password
      
      if(email.value.match(re)) {
        if(cemail.value != email.value)
          error += "Emails do not match.\n"
      } else
        error += 'Enter a valid mail address'
      usernamev = username.value.trim()
      if(usernamev.length < 4)
        error += "Enter a username of at least four characters.\n"
      if(password.value.length < 8)
        error += "Enter a password of at least eight characters.\n"
    }
    if(! agree.checked)
      error += "Please agree to the terms.\n"

    if(error.length) {
      alert(error)
      event.preventDefault()
    }
  })

</script>

<?php } ?>

</body>
</html>
