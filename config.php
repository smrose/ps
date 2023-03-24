<?php
/* NAME
 *
 *  config.php
 *
 * CONCEPT
 *
 *  Configuration management for pattern assessment projects.
 *
 * NOTES
 *
 *  This screen offers serveral forms:
 *
 *   Editing the PHPAuth configuration, which is shared by all projects
 *
 *   Editing the row in the 'project' table for project 0, which is the
 *    "template  project."
 *
 *   Editing the values in the 'appconfig' table, which contains various
 *    strings displayed in various global contexts.
 *
 *  When creating a new project, the template project is copied into
 *  the form that will be used to create the new row in the project
 *  table that defines the project. Many of the columns in the table
 *  are the text of messages that are displayed in the UI, but the 'tag'
 *  and 'title' columns are unique to the project.
 *
 *  For auth system configuration, all we do is load the configuration
 *  using $Config->getAll() and throw the values into an HTML
 *  form. All of those values are varchar(100) so it's easy. Two
 *  values in the array returned by getAll don't correspond to
 *  settings; we just delete those.
 *
 *  CREATE TABLE phpauth_config (
 *   setting varchar(100) NOT NULL PRIMARY KEY,
 *   value varchar(100) DEFAULT NULL
 *  )
 *  CREATE TABLE config_meta (
 *   descript varchar(255),
 *   FOREIGN KEY (setting) REFERENCES phpauth_config(setting)
 *  )
 *
 *  Access to this page requires the 'super' role. Arriving here without
 *  that silently redirects to the top of the project.
 *
 * $Id: config.php,v 1.21 2023/03/22 20:43:25 rose Exp $
 */

if(isset($_POST) && isset($_POST['cancel'])) {
  header('Location: ./');
}

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

const BLACKLIST = array('dictionary', 'recaptcha');

DataStoreConnect();
Initialize();


/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(is_null($user) || $user['role'] != 'super') {
  header('Location: ./');
  exit;
}

/* This user is authorized. */

$settings = GetConfig();

$title = "Configuration Management";

?>
<!doctype html>
<html lang="en">

<head>
 <title><?=$title?></title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <link rel="script" href="project/lib/ps.js">
</head>

<body>

<header>
<h1><?=$title?></h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

<?php
if(isset($_POST['realm'])) {

  if(DEBUG) error_log(var_export($_POST, true));

  if($_POST['realm'] == 'auth') {

    // Possible updates to auth configuration.

    $report = [];

    foreach($settings as $field => $setting) {
      $nvalue = trim($_POST[$field]);
      if($setting['value'] != $nvalue) {
	if($config->__set($field, $nvalue)) {
	  array_push($report,
		     "Updated <tt>'$field'</tt> from <tt>'${setting['value']}'</tt> to <tt>'$nvalue'</tt><br>\n");
	} else {
	  array_push($report,
		     "Update of <tt>'$setting'</tt> from <tt>'$value'</tt> to <tt>'$nvalue'</tt> failed.<br>\n");
	}
      }
    } // end loop on settings

    // Report.

    if(count($report)) {
      print '<p>' . join('<br>', $report) . "</p>\n";

      // Refresh settings to reflect changes.

      $settings = GetConfig();
    } else {
      print "<p>No updates.</p>\n";
    }
  } elseif($_POST['realm'] == 'appconfig') {

    // Possible updates to appconfig.
    
    $report = [];

    foreach($_POST as $k => $v) {
      if($k == 'submit' || $k == 'realm')
        continue;
      if(UpdateAppConfig(['tag' => $k, 'value' => $v]))
        array_push($report,
	           "Updated <tt>$k</tt>\n");
    }
    if(count($report))
      print '<p>' . join('<br>', $report) . "</p>\n";
    else
      print "<p>No updates.</p>\n";
      
  } else {

    // Possible updates to template project.
    
    $update = [];
    foreach($_POST as $k => $v) {
      if($k == 'action')
        continue;
      elseif($k == 'prid')
        $update['id'] = $_POST['prid'];
      else
        $update[$k] = $v;
    }
    if(UpdateProject($update))
      print "<p class=\"alert\">Updated project <tt>${update['title']} (ID ${update['id']})</tt>.</p>\n";
    else
      print "<p class=\"alert\">No updates to project <tt>${update['title']}</tt>.</p>\n";
  }
} # end case of submitted form

?>

<p>This page has separate forms for editing the auth configuration and the
template project.</p>

<ul>
 <li><a href="#auth">auth system configuration</a>
 <li><a href="#app">template project configuration</a>
 <li><a href="#appconfig">application configuration</a></li>
 <li><a href="./">return to the project directory</a></li>
</ul>

<h2 id="auth">Auth Configuration</h2>

<p>Below are all the configuration settings for the auth system. Hover over a
field name to see the field description. Change values as needed and submit
the form.</p>

<form method="POST" class="gf">
<input type="hidden" name="realm" value="auth">
<div class="gb" style="border-bottom: 2px solid #555">Setting</div>
<div class="gb" style="border-bottom: 2px solid #555">Value</div>
<?php
  foreach($settings as $setting) {
    $field = $setting['setting'];
    $descript = isset($setting['descript'])
      ? $setting['descript'] : 'no description';
    print "
 <div class=\"fieldlabel\" title=\"$descript\">$field</div>
 <div><input size=\"100\" type=\"text\" name=\"$field\" value=\"${setting['value']}\"></div>
";
  }
?>
<div class="gs">
 <input type="submit" name="submit" value="Submit">
 <input type="submit" name="cancel" value="Cancel">
</div>
</form>

<h2 id="app">Template Project Configuration</h2>

<p>The template project is simply a place to store the default values
that are used to populate the form that is used to create a new
project.</p>

<dl>
 <dt>Project tag</dt>

 <dd>Project instances share a URL stem that differs in only in the final
  component, which we call the "tag." There should be no need to change the
  value in this form as every new project will need a unique value.</dd>

 <dt>Project title</dt>

 <dd>This plain-text field sets the value of the main header and document
  titles and may appear in other contexts as well. There is no good
  reason to change the value in the template as the project title will
  be set to a unique value when the project is created.</dd>

 <dt>Visitor instructions</dt>

 <dd>This HTML is shown to user visiting the project without having
  authenticated. As such, it should explain the purpose of the project
  and the need to create an account or log into an existing account. Explain,
  too, how to create the account.</dd>

 <dt>User instructions</dt>

 <dd>This HTML is displayed on the main page to logged-in users. Explain how
  to open the form for entering an assessment.</dd>

 <dt>Manager instructions</dt>

 <dd>These HTML instructions are displayed to logged-in users that have
  <tt>manager</tt> privileges below the user instructions on the main page.
  Explain the available actions and how to get to them.</dd>

 <dt>Super instructions</dt>

 <dd>These HTML instructions are displayed to logged-in users that
  have <tt>super</tt> privileges below the <tt>manager</tt>
  instructions on the main page.  Explain the available actions and
  how to get to them.</dd>

 <dt>Assessment instructions</dt>

 <dd>These HTML instructions are displayed at the top of the assessment form.
  Explain the meaning of the fields in the form, how to submit it, and that
  assessments can be edited after they are entered.</dd>

 <dt>Above-comment instructions</dt>

 <dd>These HTML instructions are displayed below the assessment form and
  above the area for user comments and the "may we contact you" checkbox.</dd>

 <dt>Parting text</dt>

 <dd>This block of HTML is displayed on the assessment entry form just above
  the submit button.</dd>

</dl>

<form method="POST" id="app" class="gf">
<input type="hidden" name="realm" value="app">
<div class="fieldlabel">Project tag:</div>
<div><input type="text" name="tag" value="<?=$project['tag']?>"></div>
<div class="fieldlabel">Project title:</div>
<div><input type="text" name="title" value="<?=$project['title']?>"></div>
<?php
  foreach(['visitor', 'user', 'manager', 'super', 'assessment', 'abovecomment', 'parting'] as $role) {
    $field = $role . '_text';
    print "<div class=\"fieldlabel\">" . ucfirst($role) . " instructions:</div>
<div>
 <textarea rows=\"4\" cols=\"80\" name=\"$field\">$project[$field]</textarea>
</div>
";
  }
?>
<div class="gs">
 <input type="submit" name="submit" value="Submit">
 <input type="submit" name="cancel" value="Cancel">
</div>
</form>

<h2 id="appconfig">Application Configuration</h2>

<p>Various strings displayed in the application can be edited here.</p>

<form method="POST" id="appconfig" class="gf">
<input type="hidden" name="realm" value="appconfig">

<?php
  $acs = GetAppConfig();
  foreach($acs as $ac) {
    print "<div class=\"fieldlabel\" title=\"${ac['descr']}\">${ac['tag']}</div>
<div>
  <textarea rows=\"4\" cols=\"80\" name=\"${ac['tag']}\">${ac['value']}</textarea>
</div>
";
  }
?>
<div class="gs">
 <input type="submit" name="submit" value="Submit">
 <input type="submit" name="cancel" value="Cancel">
</div>
</form>

<p><a href="./">Return</a>.</p>

</div>

<?=FOOT?>
</body>
</html>
