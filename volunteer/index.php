<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '../project');
require '../project/lib/ps.php';

DataStoreConnect();
Initialize();

if($isLogged = $auth->isLogged()) {
  $action = [
    ' <li><a href="../log.php">Log out</a></li>',
    ' <li><a href="../profile.php">Edit profile</a></li>',
  ];
} else {
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
<h2>Information for Volunteers</h2>
</header>

<div id="poutine">
<img src="../images/pattern-sphere-band.png" id="gravy">

<h3>This page is intended to provide resources for developers. It's a draft!</h3>

<p>If you have any insights or suggestions as to what should be added or 
clarified, on this page, let us (Roma & Doug) know.</p>

<p>The purpose of the Pattern Sphere (PS) is to support the use of
patterns and languages to help address difficult social and
environmental problems collaboratively. In fact, the user covenant
that registered users agree to stipulates that they use it that
way.</p>

<p>Each "pattern" is a structured description of successful ideas and
actions. Patterns are general, not recipes with precise
instructions. They're intended to be adapted to meet the needs of the
people using them. This approach is not a cure-all and it's not
intended to replace other approaches. And the online approach that we
need hasn't been developed.</p>

<p>The PS is intended to support people throughout the entire
life-cycle of social change activities from creating new patterns and
commenting on existing patterns to using patterns to develop new
projects. As the PS provides more services, people, working
individually or on teams, will be able to find patterns, select
patterns to use, annotate patterns (for their own use or to help
others), create new patterns, brainstorm with patterns, join teams,
etc. etc.  We also plan to offer alternative ways for people to
perform each of these tasks — knowing that people have different
styles and take different approaches.</p>

<h3>Priorities (rough draft)</h3>

<p>I have listed the people whom I believe are working on these
priorities.  If you would like to be listed on one of them (or have
your name taken down), let me know. If you have comments on any of
these, and there is no name listed, please get in touch with me
(Doug).</p>


<ul>
<li>Manage and coordinate volunteer development activities 
<li>Overhaul Front page — https://labs.publicsphereproject.org
<ul>
<li>What is it supposed to accomplish? 
For one thing encourage people to go to the PS page (and AM too!) 
<li>something soon, even if it's just for the short term
</ul>
<li>Overhaul PS splash page — https://labs.publicsphereproject.org/ps 
<ul>
<li>What is it supposed to accomplish? 
Encourage people to go to the PS page (and AM too!) 
<li>something soon, even if it's just for the short term
</ul>
<li>Master process outline / site map (Tharani (and Sanjana?))
<li>Finish business rules (processes, roles, rules, patterns, pattern 
languages, organizations, users, annotations, workspace, etc.) (Tharani (and Sanjana?))
<li>Fix (or, even, improve) registration / login process
<li>Overhaul volunteer page(s) (Roma)
<li>User Research (Georgia Tech)
<li>Import Liberating Voices and one or more other pattern languages
<li>Template development, pattern enhancement and rework (Scott)
<li>Expert interviews
<li>Add info about one or Pattern Languages to list
<li>Work with a community (real or represented) (Lala, Georgia Tech)
<ul>
<li>publish results, help tailor processes to the various communities
</ul>
<li>develop / curate specialized — and small — pattern languages
</ul>


