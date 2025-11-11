<?php
/* NAME
 *
 *  index.php
 *
 * CONCEPT
 *
 *  Volunteer hub.
 */

set_include_path(get_include_path() . PATH_SEPARATOR . '../project');
require '../project/lib/ps.php';

DataStoreConnect();
Initialize();

if($isLogged = $auth->isLogged()) {

  $userinfo = $auth->getCurrentSessionUserInfo();
  $user = GetUser($userinfo['uid']);
  $isVolunteer = GetVolunteer($userinfo['uid']) ? true : false;
  $isSuper = ($user['role'] == 'super') ? true : false;
  $isAuthorized = ($isVolunteer || $isSuper) ? true : false;

  $action = [
    ' <li><a href="../log.php">Log out</a></li>',
    ' <li><a href="../profile.php">Edit profile</a></li>',
  ];
} else {

  $isAuthorized = false;

  $action = [
    '<li><a href="../log.php">Log in</a></li>',
    '<li><a href="../reset.php">Reset password</a></li>'
  ];
}
$actions = join('', $action);
?>
<!doctype html>
<html lang="en">

<head>
 <title>Pattern Sphere Volunteer Resources</title>
 <link rel="stylesheet" href="../project/lib/ps.css">
 <script src="../project/lib/ps.js"></script>
 <script>
  let exptime = 1692051396
 </script>

</head>

<body>

<div id="actions">
  <div id="authactions">
  <div class="banner">Actions</div>
  <ul>
    <?= $actions ?>
  </ul>
</div>
</div>

<header>
<h1>Pattern Sphere</h1>
<h2>Information for Volunteers (draft)</h2>
</header>

<div id="poutine">
<img src="../images/pattern-sphere-band.png" id="gravy">



<?php
if(!$isLogged) {

  // this user hasn't logged in
  
  print "<p>If you are a registered Pattern Sphere volunteer, please <a href=\"../log.php\">log in</a> for registered volunteer resources.</p>

  <p>If you would like to register as a volunteer, please <a href=\"profile.php\">complete the PS volunteer interest form.</p>";

} elseif(!$isAuthorized) {

  // this user logged in, but is neither a volunteer nor a superuser

  print "<p>Access to this page is restricted to registered volunteers. To
register as a volunteer, please complete this <a href=\"profile.php\">volunteer
registration form</a>.</p>
";
} else {

  // this user is authorized

?>
<p>If you have any insights or suggestions as to what should be added or 
clarified, on this page, let Doug know.</p>

<p>The purpose of the Pattern Sphere (PS) is to support the use of
patterns and languages to help address difficult social and
environmental problems collaboratively. In fact, the user covenant
that registered users agree to stipulates that they use it that
way.</p>

<p>Each "pattern" is a structured description of successful ideas and
actions. Patterns are general, not recipes with precise
instructions. They must be adapted by the people using them to meet 
their specific needs. This approach is not a cure-all and it's not
intended to replace other approaches. </p>

<p>The PS is intended to support people throughout the entire
life-cycle of social change activities from creating new patterns and
commenting on existing patterns to using patterns and developing new
projects. As the PS provides more services, people, working
individually or on teams, will be able to find patterns, select
patterns to use, annotate patterns (for their own use or to help
others), create new patterns, brainstorm with patterns, join teams,
etc. etc.  We also plan to offer alternative ways for people to
perform each of these tasks — knowing that people have different
styles and take different approaches.</p>

<h3>Documents (incomplete list)</h3>
<ul>
<li><a href="https://docs.google.com/document/d/13LI1mpcgUYXMaP4UQy211gYWd-sznTgt094vUMTbZ0c/edit?tab=t.0">PS 
Basic Objects and User Functions</a> Document, comments welcome</li>
<li><a href="https://docs.google.com/document/d/1sy3Y3vVp69YXesU_JSb6F3gc27ffkWt37QX9p7HRA_I/edit?tab=t.0">PS 
objectives and PS splash page and Labs splash page objectives</a>
</li>
</ul>

<h3>Development Platforms</h3>
<ul>
<li><a href="https://www.figma.com/design/KiyeFNhGEKeXt1RebDcYa9/Public-Sphere-Project--App-Mockups?t=DU5vbgYgfkgXZKuQ-0#-1">Figma</li>
<li><a href="....">(not linked) Notion</a></li>
<li><a href="....">(not linked) LinkedIn</a></li>
<li><a href="....">(not linked) Github</a></li>
<li><a href="...."></a>(not linked)</li>
<li><a href="...."></a>(not linked)</li>
<li><a href="...."></a>(not linked)/li>
</ul>

<h3>Priorities (rough draft)</h3>

<p>I have listed the people whom I believe are working on these
priorities.  If you would like to be listed on one of them (or have
your name taken down), let me know. If you have comments on any of
these, and there is no name listed, please get in touch with me
(Doug).</p>

<ul>
<li>Manage and coordinate volunteer development activities </li>
<li>Overhaul Front page — https://labs.publicsphereproject.org</li>
<ul>
<li>What is it supposed to accomplish? </li>
For one thing encourage people to go to the PS page (and AM too!) 
<li>something soon, even if it's just for the short term</li>
</ul>
<li>Overhaul PS splash page — https://labs.publicsphereproject.org/ps</li>
<ul>
<li>What is it supposed to accomplish?</li>
Encourage people to go to the PS page (and AM too!) 
<li>something soon, even if it's just for the short term</li>
</ul>
<li>Master process outline / site map /li>
<li>Finish business rules (processes, roles, rules, patterns, pattern </li>
languages, organizations, users, annotations, workspace, etc.) 
<li>Fix (or, even, improve) registration / login process</li>
<li>Overhaul volunteer page(s)</li>
<li>User Research</li>
<li>Import Liberating Voices and one or more other pattern languages</li>
<li>Template development, pattern enhancement and rework</li>
<li>Expert interviews</li>
<li>Add info about one or Pattern Languages to list</li>
<li>Work with a community (real or represented)</li>
<ul>
<li>publish results, help tailor processes to the various communities</li>
</ul>
<li>develop / curate specialized — and small — pattern languages</li>
</ul>
<?php
}
?>
</body>
</html>
