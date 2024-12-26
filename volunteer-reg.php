<?php
/* NAME
 *
 *  volunteer-reg.php
 *
 * CONCEPT
 *
 * Volunteer interest form.
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
    }
    p {
      color: #555;
    }
    #heading {
      display: grid;
      grid-template-columns: repeat(5, auto);
    }
   #partook {
      margin-right: 10vw;
      margin-left: 10vw;
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

<div id="partook">

<h1>Volunteer Interest Form</h1>

<p>For patterns and pattern languages to have lasting and tangible effects, many capabilities need to be developed, including federated pattern language repositories, support for collaboration, team workspace, search capabilities, pattern sharing, and many others. We need input and skills, dedication and creativity, in many areas to make this work. Thank you!</p>

<form method="POST" action="">

<div class="fs">A. Personal Information</div>

<div class="fh">Your Full Name<span class="rstar">*</span></div>
<div><input type="text" name="fullname"></div>
<div class="fh">Email address<span class="rstar">*</span></div>
<div><input type="text" name="email"></div>
<div class="fh">Please confirm Email address<span class="rstar">*</span></div>
<div><input type="text" name="cemail"></div>
<div class="fh">How did you hear about the project?<span class="rstar">*</span></div>
<div><input type="text" name="heard"></div>
<div class="fh">Preferred user name for Pattern Sphere Project login<span class="rstar">*</span></div>
<div><input type="text" name="heard"></div>
<div class="fh">Preferred password for Pattern Sphere Project login<span class="rstar">*</span></div>
<div><input type="password" name="password"></div>

<div class="fs">B. Volunteer Contribution</div>

<div class="fh">When will you be available for volunteering?<span class="rstar">*</span></div>
<div><span class="fh">Approx Start Date</span> <input type="text" name="start"> <span class="fh">Approx End Date</span> <input type="text" name="end"></div>
<div class="fh">How many hours per week can you commit to the program?<span class="rstar">*</span></div>
<div><input type="text" name="hours" size=3 style="margin-left: 2vw"></div>
<div class="fh">Please rate your interests in the following areas: <span class="rstar">*</span></div>
<div id="int">
  <div></div>
  <div class="ir">Very High</div>
  <div style="grid-column: span 3"></div>
  <div class="ir">Very Low</div>

  <div class="i">1. Page and Site UI:</div>
  <div class="ir"><input type="radio" name="ui" value="1"></div>
  <div class="ir"><input type="radio" name="ui" value="2"></div>
  <div class="ir"><input type="radio" name="ui" value="3"></div>
  <div class="ir"><input type="radio" name="ui" value="4"></div>
  <div class="ir"><input type="radio" name="ui" value="5"></div>

  <div class="i">2. Process and Site Design</div>
  <div class="ir"><input type="radio" name="design" value="1"></div>
  <div class="ir"><input type="radio" name="design" value="2"></div>
  <div class="ir"><input type="radio" name="design" value="3"></div>
  <div class="ir"><input type="radio" name="design" value="4"></div>
  <div class="ir"><input type="radio" name="design" value="5"></div>

  <div class="i">3. UX Design and Research</div>
  <div class="ir"><input type="radio" name="ux" value="1"></div>
  <div class="ir"><input type="radio" name="ux" value="2"></div>
  <div class="ir"><input type="radio" name="ux" value="3"></div>
  <div class="ir"><input type="radio" name="ux" value="4"></div>
  <div class="ir"><input type="radio" name="ux" value="5"></div>

  <div class="i">4. Database and Coding</div>
  <div class="ir"><input type="radio" name="code" value="1"></div>
  <div class="ir"><input type="radio" name="code" value="2"></div>
  <div class="ir"><input type="radio" name="code" value="3"></div>
  <div class="ir"><input type="radio" name="code" value="4"></div>
  <div class="ir"><input type="radio" name="code" value="5"></div>

  <div class="i">5. Outreach and Marketing</div>
  <div class="ir"><input type="radio" name="market" value="1"></div>
  <div class="ir"><input type="radio" name="market" value="2"></div>
  <div class="ir"><input type="radio" name="market" value="3"></div>
  <div class="ir"><input type="radio" name="market" value="4"></div>
  <div class="ir"><input type="radio" name="market" value="5"></div>

  <div class="i">6. Project Management</div>
  <div class="ir"><input type="radio" name="pm" value="1"></div>
  <div class="ir"><input type="radio" name="pm" value="2"></div>
  <div class="ir"><input type="radio" name="pm" value="3"></div>
  <div class="ir"><input type="radio" name="pm" value="4"></div>
  <div class="ir"><input type="radio" name="pm" value="5"></div>

  <div class="i">7. Volunteer Support</div>
  <div class="ir"><input type="radio" name="vs" value="1"></div>
  <div class="ir"><input type="radio" name="vs" value="2"></div>
  <div class="ir"><input type="radio" name="vs" value="3"></div>
  <div class="ir"><input type="radio" name="vs" value="4"></div>
  <div class="ir"><input type="radio" name="vs" value="5"></div>

  <div class="i">8. Content Development</div>
  <div class="ir"><input type="radio" name="content" value="1"></div>
  <div class="ir"><input type="radio" name="content" value="2"></div>
  <div class="ir"><input type="radio" name="content" value="3"></div>
  <div class="ir"><input type="radio" name="content" value="4"></div>
  <div class="ir"><input type="radio" name="content" value="5"></div>

  <div class="i">9. Other (please specify)</div>
  <div style="grid-column: span 5"><input type="text" name="other" size="60"></div>
</div>

<div class="fh">Additional comments about your interests <span class="rstar">*</span></div>
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

<p style="margin-left: 2vw"><input type="checkbox" name=""> I understand and agree <span class="rstar">*</span></p>

<p>Comments or Questions <span class="rstar">*</span></p>
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
  <div><input type="text" name="nationality" size="30"></div>

</div>

<input id="submit" type="submit" name="submit" value="Submit" disabled>

</form>

</div>

</body>
</html>
