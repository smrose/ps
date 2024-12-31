<?php
/* NAME
 *
 *  volunteer-reg.php
 *
 * CONCEPT
 *
 *  Volunteer interest form.
 *
 */

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require 'project/lib/ps.php';

DataStoreConnect();
Initialize();

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
    #congrats {
      font-size: 16pt; font-weight: bold;
      margin-top: 10vh;
      margin-bottom: 5vh;
    }
    p {
      color: #555;
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
      color: #555;
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
      color: #555;
      margin-top: .4vh;
      font-size: 11pt;
    }
    #int {
      margin-left: 2vw;
      display: grid;
      grid-template-columns: max-content repeat(5, 6em);
    }
    .ir {
      text-align: center;
      color: #555;
    }
    .i {
      font-size: 11pt;
      color: #555;
    }
    #opt {
      display: grid;
      grid-template-columns: repeat(2, max-content);
      color: #555;
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
 <div>Volunteer Resources</div>
 <div>Sign Up</div>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST') {

  // Absorbing a form submission.

  $email = trim($_POST['email']);
  $username = strtolower(trim($_POST['username']));

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

    // A new user. Validate fullname.
    
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
  
  // required fields
  
  $meta = [
    'id' => $rval['uid'],
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
} else {

  // Presenting a form.

  $status = (isset($_GET['status']) && $_GET['status'] == 'approved')
    ? '<input type="hidden" name="status" value="approved">'
    : '';
?>

<div id="partook">

<h1>Volunteer Interest Form</h1>

<p>For patterns and pattern languages to have lasting and tangible effects, many capabilities need to be developed, including federated pattern language repositories, support for collaboration, team workspace, search capabilities, pattern sharing, and many others. We need input and skills, dedication and creativity, in many areas to make this work. Thank you!</p>

<p>Required fields are marked with <span class="rstar">*</span>.</p>

<form method="POST" action="volunteer-reg.php" id="form">
<?=$status?>

<div class="fs">A. Personal Information</div>

<div class="fh">Your Full Name <span class="rstar">*</span></div>
<div><input type="text" name="fullname" id="fullname" required></div>
<div class="fh">Email address <span class="rstar">*</span></div>
<div><input type="text" name="email" id="email" required></div>
<div class="fh">Please confirm Email address <span class="rstar">*</span></div>
<div><input type="text" name="cemail" id="cemail" required></div>
<div class="fh">How did you hear about the project? <span class="rstar">*</span></div>
<div><input type="text" name="heard" id="heard" required></div>
<div class="fh">Preferred user name for Pattern Sphere Project login (2 characters minimum) <span class="rstar">*</span></div>
<div><input type="text" name="username" id="username" required></div>
<div class="fh">Preferred password for Pattern Sphere Project login (8 characters minimum) <span class="rstar">*</span></div>
<div><input type="password" name="password" id="password" required></div>

<div class="fs">B. Volunteer Contribution</div>

<div class="fh">Should your name be included on the public list of volunteers?</div>
<div style="margin-left: 1em">
 <input type="radio" name="public" value="0" checked> no
 <input type="radio" name="public" value="1"> yes
</div>
<div class="fh">Will you probably be requesting a letter of recommendation for
 your volunteer work? Note that a bit more discussion and formality will be
 required if you answer Yes.</div>
<div style="margin-left: 1em">
 <input type="radio" name="recommend" value="0" checked> no
 <input type="radio" name="recommend" value="1"> yes
</div>
<div class="fh">When will you be available for volunteering?</div>
<div>
 <span class="fh">Approx Start Date</span> <span class="rstar">*</span> <input type="text" name="start" id="start" required>
 <span class="fh">Approx End Date</span> <span class="rstar">*</span> <input type="text" name="end" id="end" required>
</div>
<div class="fh">How many hours per week can you commit to the program? <span class="rstar">*</span></div>
<div><input type="text" name="hours" id="hours" size=3 style="margin-left: 2vw" required></div>
<div class="fh">Please rate your interests in the following areas: <span class="rstar">*</span></div>
<div id="int">
  <div></div>
  <div class="ir">Very High</div>
  <div style="grid-column: span 3"></div>
  <div class="ir">Very Low</div>

  <div class="i">1. Page and Site UI:</div>
  <div class="ir"><input type="radio" name="ui" value="1" required></div>
  <div class="ir"><input type="radio" name="ui" value="2"></div>
  <div class="ir"><input type="radio" name="ui" value="3"></div>
  <div class="ir"><input type="radio" name="ui" value="4"></div>
  <div class="ir"><input type="radio" name="ui" value="5"></div>

  <div class="i">2. Process and Site Design</div>
  <div class="ir"><input type="radio" name="design" value="1" required></div>
  <div class="ir"><input type="radio" name="design" value="2"></div>
  <div class="ir"><input type="radio" name="design" value="3"></div>
  <div class="ir"><input type="radio" name="design" value="4"></div>
  <div class="ir"><input type="radio" name="design" value="5"></div>

  <div class="i">3. UX Design and Research</div>
  <div class="ir"><input type="radio" name="ux" value="1" required></div>
  <div class="ir"><input type="radio" name="ux" value="2"></div>
  <div class="ir"><input type="radio" name="ux" value="3"></div>
  <div class="ir"><input type="radio" name="ux" value="4"></div>
  <div class="ir"><input type="radio" name="ux" value="5"></div>

  <div class="i">4. Database and Coding</div>
  <div class="ir"><input type="radio" name="code" value="1" required></div>
  <div class="ir"><input type="radio" name="code" value="2"></div>
  <div class="ir"><input type="radio" name="code" value="3"></div>
  <div class="ir"><input type="radio" name="code" value="4"></div>
  <div class="ir"><input type="radio" name="code" value="5"></div>

  <div class="i">5. Outreach and Marketing</div>
  <div class="ir"><input type="radio" name="market" value="1" required></div>
  <div class="ir"><input type="radio" name="market" value="2"></div>
  <div class="ir"><input type="radio" name="market" value="3"></div>
  <div class="ir"><input type="radio" name="market" value="4"></div>
  <div class="ir"><input type="radio" name="market" value="5"></div>

  <div class="i">6. Project Management</div>
  <div class="ir"><input type="radio" name="pm" value="1" required></div>
  <div class="ir"><input type="radio" name="pm" value="2"></div>
  <div class="ir"><input type="radio" name="pm" value="3"></div>
  <div class="ir"><input type="radio" name="pm" value="4"></div>
  <div class="ir"><input type="radio" name="pm" value="5"></div>

  <div class="i">7. Volunteer Support</div>
  <div class="ir"><input type="radio" name="vs" value="1" required></div>
  <div class="ir"><input type="radio" name="vs" value="2"></div>
  <div class="ir"><input type="radio" name="vs" value="3"></div>
  <div class="ir"><input type="radio" name="vs" value="4"></div>
  <div class="ir"><input type="radio" name="vs" value="5"></div>

  <div class="i">8. Content Development</div>
  <div class="ir"><input type="radio" name="content" value="1" required></div>
  <div class="ir"><input type="radio" name="content" value="2"></div>
  <div class="ir"><input type="radio" name="content" value="3"></div>
  <div class="ir"><input type="radio" name="content" value="4"></div>
  <div class="ir"><input type="radio" name="content" value="5"></div>

  <div class="i">9. Other (please specify)</div>
  <div style="grid-column: span 5"><input type="text" name="other" size="60"></div>
</div>

<p>Additional comments about your interests</p>
<textarea name="icomments" cols="80" rows="4" style="margin-left: 2vw"></textarea>

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
<textarea rows="3" cols="80" name="comments" style="margin-left: 2vw"></textarea>

<div class="fs">D. Optional Section</div>

<div id="opt">

  <div class="o">Gender:</div>
  <div>
    <input type="radio" name="gender" value="male"> Male
    <input type="radio" name="gender" value="female"> Female
    <input type="radio" name="gender" value="nb"> Non-Binary
    <input type="radio" name="gender" value="other">Other
  </div>

  <div class="o">Age:</div>
  <div><input type="text" name="age" size="2"></div>

  <div class="o">Current Location:</div>
  <div><input type="text" name="location" size="30"></div>

  <div class="o">Nationality:</div>
  <div><input type="text" name="nationality" size="30"></div>
  
  <div class="o">Current Degree/Course:</div>
  <div><input type="text" name="training" size="30"></div>

</div>

<input id="submit" type="submit" name="submit" value="Submit">

</form>

</div>

<script>

  const form = document.querySelector('#form')

  // Elements for which input is required.
  
  const email = document.querySelector('#email')       // text
  const cemail = document.querySelector('#cemail')     // text
  const username = document.querySelector('#username') // text
  const password = document.querySelector('#password') // password
  const agree = document.querySelector('#agree')       // checkbox

  // Validate input on form submission.

  form.addEventListener('submit', (event) => {
    let error = ''
    const re = /^.+@.+\..+$/

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
