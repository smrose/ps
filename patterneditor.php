<?php
#
# NAME
#
#  Pattern editor.
#
# CONCEPT
#
#  Create/edit pattern languages and patterns.
#
# FUNCTIONS
#
#  image    seek existing matching graphic image
#  pform    present a form for editing or creating a pattern
#  plform   present a form for editing or creating a language
#  pledit   edit or delete a pattern language
#  pedit    edit or delete a pattern
#  pcreate  create a pattern
#  plcreate create a pattern language
#
# $Id: patterneditor.php,v 1.20 2023/03/22 20:43:25 rose Exp $

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(isset($_POST) && isset($_POST['cancel'])) {
  header('Location: ./');
}

set_include_path(get_include_path() . PATH_SEPARATOR . 'project');
require "lib/ps.php";

DataStoreConnect();
Initialize();

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(!$user || $user['role'] != 'super') {
  header('Location: ./');
  exit;
}

$fields = [
  [
    'name' => 'title',
    'label' => 'Pattern title',
    'type' => 'text',
    'size' => 40
  ],
  [
    'name' => 'synopsis',
    'label' => 'Brief description',
    'type' => 'textarea',
  ],
  [
    'name' => 'discussion',
    'label' => 'Discussion',
    'type' => 'textarea',
  ],
  [
    'name' => 'context',
    'label' => 'Context',
    'type' => 'textarea',
  ], 
  [
    'name' => 'solution',
    'label' => 'Seed',
    'type' => 'textarea',
  ],
  [
    'name' => 'intro',
    'label' => 'Introductory image',
    'type' => 'image',
    'prefix' => 'i_'
  ],
  [
    'name' => 'close',
    'label' => 'Closing image',
    'type' => 'image',
    'prefix' => 'c_'
  ]
];


/* image()
 *
 *  Return a two-element array containing 'name' and 'size' (in bytes) of
 *  an image file if found for this field for this pattern, else null.
 */

function image($pid, $field) {
  $dh = opendir(IMAGEDIR);
  $pat = '/^' . $field['prefix'] . $pid . '\..*$/';
  while($direntry = readdir($dh)) {
    if(preg_match($pat, $direntry)) {
      $stat = stat(IMAGEDIR . '/' . $direntry);
      $file = [
        'name' => $direntry,
	'size' => $stat[7]
      ];
      break;
    }
  }
  closedir($dh);
  return($file);
  
} /* end image() */


/* plform()
 *
 *  Present a form for creating ($pl is null) or editing a language.
 */

function plform($pl = null) {
  if(isset($pl)) {

    # edit existing
    
    $action = 'pledit';
    $slabel = 'Absorb edits';
    $title = "Editing Pattern Language <span style=\"font-style: oblique\">{$pl['title']}</span>";
    $plid = "<input type=\"hidden\" name=\"plid\" value=\"{$pl['id']}\">
<input type=\"hidden\" name=\"plaction\" value=\"edit\">
";
    $options = '';
    foreach(['inwork', 'published'] as $status) {
      $selected = ($pl['status'] == $status) ? ' selected="selected"' : '';
      $options .= "<option value=\"$status\"$selected> $status\n";
    }
  } else {

    # create new
    
    $title = 'Creating Pattern Language';
    $action = 'plcreate';
    $slabel = 'Create language';
    $pl = [
      'title' => '',
      'baseurl' => ''
    ];
    $plid = '';
    $options = ' <option value="inwork" selected="selected"> in work
 <option value="published"> published
';
  }
  print "<h2>$title</h2>

<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\">
$plid
<input type=\"hidden\" name=\"action\" value=\"$action\">
<div class=\"fieldlabel\">Language title:</div>
<div><input type=\"textfield\" size=\"40\" name=\"title\" value=\"{$pl['title']}\"></div>
<div class=\"fieldlabel\">Base URL:</div>
<div><input type=\"textfield\" size=\"40\" name=\"baseurl\" value=\"{$pl['baseurl']}\"></div>
<div class=\"fieldlabel\">Status:</div>
<div><select name=\"status\">
$options
</select>
</div>
<div class=\"gs\">
  <input type=\"submit\" name=\"submit\" value=\"$slabel\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

} /* end plform() */


/* pform()
 *
 *  Present a form for creating or editing a pattern.
 *
 *  If we are editing a pattern, the argument array contains the
 *  record returned by GetPattern() - everything in a row from the
 *  'pattern' table plus the 'planguage' title as 'ptitle'.
 *
 *  If we are creating a pattern, the argument array contains just the
 *  'id' field (as 'plid') from the 'planguage' record to which it
 *  will belong.
 */

function pform($p = null) {
  global $fields;
  
  $plid = $p['plid'];
  if(isset($p['id'])) {

    # edit existing
    
    $action = 'pedit4';
    $slabel = 'Absorb edits';
    $title = "Editing Pattern <span style=\"font-style: oblique\">{$p['title']}</span>";
    $fpid = "<input type=\"hidden\" name=\"pid\" value=\"{$p['id']}\">
<input type=\"hidden\" name=\"paction\" value=\"edit\">
";
    $instructions = '';
    $pltitle = $p['pltitle'];
    
  } else {

    # create new
    
    $title = 'Creating Pattern';
    $action = 'pcreate3';
    $slabel = 'Create pattern';
    $pl = GetPLanguage(['id' => $plid]);
    $pltitle = $pl['title'];
    $instructions = '
<p class="alert">Each pattern has a (required) parent language, a (required) title - 
which must be unique across patterns in the language - and (optional)
brief description, discussion, context, and seed.</p>
';
  }
  
  print "<h2>$title</h2>
$instructions
<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\" enctype=\"multipart/form-data\">
$fpid
<input type=\"hidden\" name=\"plid\" value=\"$plid\">
<input type=\"hidden\" name=\"action\" value=\"$action\">
<div class=\"fieldlabel\">Pattern language:</div>
<div style=\"font-weight: bold\">$pltitle</div>
";

  foreach($fields as $field) {

    $stitle = '';
    if(isset($p) && $field['type'] == 'image') {
      $existing = image($p['id'], $field);
      if(isset($existing))
        $stitle = " title=\"{$existing['name']}, {$existing['size']} bytes\" style=\"text-decoration: underline\"";
    }

    print "<div class=\"fieldlabel\"$stitle>{$field['label']}:</div>\n";
    
    if($field['type'] == 'text') {
      $value = (isset($p)) ? $p[$field['name']] : '';
      print "<div><input type=\"textfield\" size=\"{$field['size']}\" name=\"{$field['name']}\" value=\"$value\"></div>\n";
    } elseif($field['type'] == 'textarea') {
      $value = (isset($p)) ? $p[$field['name']] : '';
      print "<div><textarea name=\"{$field['name']}\" rows=\"4\" cols=\"80\">$value</textarea></div>\n";
    } elseif($field['type'] == 'image') {
      print "<div><input type=\"file\" name=\"{$field['name']}\"></div>\n";
    }
  }
  print "<div class=\"gs\">
  <input type=\"submit\" name=\"submit\" value=\"$slabel\">
  <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

} /* end pform() */


/* ValidPL()
 *
 *  Returns the 'planguage' record with this 'id' or null if not found.
 */
 
function ValidPL($plid) {
  $planguages = GetPLanguage();
  foreach($planguages as $planguage) {
    if($planguage['id'] == $plid)
      return($planguage);
  }
  return(false);
} /* end ValidPL() */


/* pledit()
 *
 *  Edit or delete a pattern language.
 */

function pledit() {
  if($_SERVER['REQUEST_METHOD'] == 'POST') {

    # process a form submission - absorbing language selection or edits

    # first, ensure the selected language exists

    $plid = $_POST['plid'];
    if(!($planguage = ValidPL($plid))) {
      Error('No language was selected.');
      exit();
    }

    if(isset($_POST['plaction']) && $_POST['plaction'] == 'delete') {

      # delete this language altogether
      
      DeletePLanguage($plid);
      print "<p class=\"alert\">Deleted pattern language <tt>{$planguage['title']}</tt></p>\n";
      return(true);
    } else if(isset($_POST['plaction']) && $_POST['plaction'] == 'edit') {

      # edit the selected language
      
      if(isset($_POST['title'])) {

        # absorb the edits

	$update = [
	  'id' => $plid,
	  'title' => $_POST['title'],
	  'baseurl' => $_POST['baseurl'],
	  'status' => $_POST['status']
	];
        UpdatePLanguage($update);
	return(true);
      } else {

        # present the edit form
	
        $planguages = GetPLanguage(['id' => $plid]);
        plform($planguage);
	return(false);
      }
    } else {
      Error('Select either <tt>edit</tt> or <tt>delete</tt>');
    }
  } else {

    # select pattern language and edit or delete action
    
    $planguages = GetPLanguage();
    $options = "<option value=\"\"selected>Choose</option>\n";
    foreach($planguages as $planguage) {
      $options .= "<option value=\"{$planguage['id']}\">{$planguage['title']}</option>\n";
    }
    print "<h2>Edit or Delete a Pattern Language</h2>

<p class=\"alert\">Deleting a pattern language also deletes all the patterns
it contains.</p>

<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\">
<input type=\"hidden\" name=\"action\" value=\"pledit\">
<div class=\"fieldlabel\">Language:</div>
<div>
 <select name=\"plid\" id=\"selplid\">
  $options
 </select>
</div>
<div class=\"fieldlabel\">Action:</div>
<div>
 <input type=\"radio\" name=\"plaction\" value=\"edit\" id=\"pledit\"> edit
 <input type=\"radio\" name=\"plaction\" value=\"delete\" id=\"pldelete\"> delete
</div>
<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"Continue\" id=\"pledsubmit\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";
  }
  return(false);

} /* end pledit() */


/* pedit()
 *
 *  Edit or delete a pattern.
 */

function pedit() {
  global $fields;
  
  if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
  
    if($action == 'pedit2') {

      # select a pattern and action
      
      $plid = $_POST['plid'];
      $planguage = GetPLanguage(['id' => $plid]);
      $patterns = GetPattern(['plid' => $plid]);
      if(! $patterns) {
        Error("No patterns found in this language.");
      }
      $options = "<option value=\"\"selected>Choose</option>\n";
      foreach($patterns as $pattern) {
        $options .= "<option value=\"{$pattern['id']}\">{$pattern['title']}</option>\n";
      }
      print "<h2>Edit or Delete a <span style=\"font-style: oblique\">{$planguage['title']}</span> Pattern</h2>

<p class=\"alert\">Select the pattern and action you wish to take.</p>

<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\">
<input type=\"hidden\" name=\"action\" value=\"pedit3\">
<input type=\"hidden\" name=\"plid\" value=\"$plid\">
<div class=\"fieldlabel\">Pattern:</div>
<div>
 <select name=\"pid\" id=\"selpid\">
  $options
 </select>
</div>
<div class=\"fieldlabel\">Action:</div>
<div>
 <input type=\"radio\" name=\"paction\" value=\"edit\" id=\"pedit\"> edit
 <input type=\"radio\" name=\"paction\" value=\"delete\" id=\"pdelete\"> delete
</div>
<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"Continue\" id=\"pedsubmit\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

    } else if($action == 'pedit3') {
    
      /* we have selected a pattern - delete, or solicit edits, then
         absorb them */

      if(!isset($_POST['paction'])) {
        Error("You didn't select an action for this pattern.");
      }
      $paction = $_POST['paction'];
      $pid = $_POST['pid'];
      $plid = $_POST['plid'];
      $patterns = GetPattern(['p.id' => $pid]);
      $pattern = $patterns[0];

      if($_POST['paction'] == 'delete') {
        DeletePattern($pid);
	print "<p class=\"alert\">Deleted pattern <tt>{$pattern['title']}</tt></p>\n";
	return(true);
      }
      # solicit edits

      pform($pattern);
      return(false);
      
    } else if($_POST['action'] == 'pedit4') {

      # absorb edits

      $acted = false;
      $oktypes = [
        'image/jpeg' => 'jpg',
	'image/png' => 'png',
	'image/gif' => 'gif'
      ];
	  
      $pid = $_POST['pid'];
      $update['id'] = $pid;
      
      foreach($fields as $field) {
      
        $name = $field['name'];
	
        if($field['type'] == 'text' || $field['type'] == 'textarea') {
	  $update[$name] = $_POST[$name];
	} elseif($field['type'] == 'image') {
	  if(isset($_FILES[$name]) && $_FILES[$name]['size']) {

            // file upload attempt
	    
	    $file = $_FILES[$name];
	    if(DEBUG) error_log("$name: upload type: {$file['type']}, size {$file['size']}");
	    
	    // check if we support this file type as an image
	    
	    if(! array_key_exists($file['type'], $oktypes))
	      Error("{$field['label']}: file type <tt>{$file['type']} not supported");
            else
	      $destination = $field['prefix'] . $pid . '.' .
	       $oktypes[$file['type']];

	    // check if the file is below the maximum file size
	    
	    if($file['size'] > MAXSIZE)
	      Error("{$field['label']}: file size of {$file['size']} exceeds the maximum of " . MAXSIZE);

	    // collect name and size of existing matching file in $existing.

	    $existing = image($pid, $field);

	    if(move_uploaded_file($file['tmp_name'],
	                          IMAGEDIR . '/' . $destination)) {
	      print "<p class=\"alert\">{$field['label']}: absorbed.</p>\n";
	      $acted = true;
	    } else
	      Error("{$field['label']}: unable to save uploaded file\n");

            // unlink any conflicting file

            if(isset($existing) && $existing['name'] != $destination)
	        unlink(IMAGEDIR . '/' . $existing['name']);
	    
	  } // end case of graphic file upload
	}
      } // end loop on fields
      
      if(UpdatePattern($update))
        print "<p class=\"alert\">Pattern updated.</p>\n";
      elseif(!$acted)
        print "<p class=\"alert\">No changes to pattern.</p>\n";
	
      return(true);
    }
  } else {

    # first, select a pattern language

    print "<h2>Edit or Delete a Pattern</h2>

<p class=\"alert\">First, select the language this pattern belongs to.</p>

";
    plselect('pedit2');
  }
} /* end pedit() */


/* plselect()
 *
 *  Select a planguage from a popup menu.
 */
 
function plselect($nextAction, $plid = null) {
  $planguages = GetPLanguage();
  $options = "<option value=\"0\">Choose</option>\n";
  foreach($planguages as $planguage) {
    $selected = (isset($plid) && $planguage['id'] == $plid)
      ? ' selected' : '';
    $options .= "<option value=\"{$planguage['id']}\"$selected>{$planguage['title']}</option>\n";
  }
  print "<form method=\"post\" action=\"{$_SERVER['SCRIPT_NAME']}\" class=\"gf\">
<input type=\"hidden\" name=\"action\" value=\"$nextAction\">
<div class=\"fieldlabel\">Language:</div>
<div>
 <select name=\"plid\" id=\"selplid\">
  $options
 </select>
</div>
<div class=\"gs\">
 <input type=\"submit\" name=\"submit\" value=\"Continue\" id=\"pledsubmit\">
 <input type=\"submit\" name=\"submit\" value=\"Cancel\">
</div>
</form>
";

} /* plselect() */


/* pcreate()
 *
 *  Create a pattern.
 *
 *  We use this function to both present the form that solicits input
 *  and to process that input. A GET is "solicit" and a POST is absorb.
 */
 
function pcreate($action) {
  if($action == 'pcreate') {
  
    # solicit the planguage

    print "<h2>Create a Pattern</h2>

<p class=\"alert\">First, select the language to which this pattern will belong.</p>
";
    if(isset($_GET) && isset($_GET['plid']))
      $plid = $_GET['plid'];
    plselect('pcreate2', $plid);
    
  } else if($action == 'pcreate2') {

    # check that the langauge was selected, then get the rest of the data
    
    if (!ValidPL($plid = $_POST['plid']))
    
    if(! isset($plid))
      Error('No language was selected.');
    pform(['plid' => $plid]);
    
  } else if($action == 'pcreate3') {
  
    # process a form submission

    $plid = $_POST['plid'];
    $patterns = GetPattern(['plid' => $plid]);
    $synopsis = trim($_POST['synopsis']);
    $discussion = trim($_POST['discussion']);
    $context = trim($_POST['context']);
    $solution = trim($_POST['solution']);
    $title = trim($_POST['title']);
    if(! strlen($title))
      Error("Pattern title cannot be empty.");
    $maxprank = 0;
    if(! is_null($patterns)) {

      # check other patterns in this language for title uniqueness
      
      foreach($patterns as $pattern) {
        if($pattern['title'] == $title) {
          Error("Pattern titles must be unique within the language, but '{$pattern['title']}' already exists in this language.");
        }
        if($pattern['prank'] > $maxprank) {
          $maxprank = $pattern['prank'];
        }
      }
    }
    $value = [
      'title' => $title,
      'plid' => $plid,
      'prank' => $maxprank+1,
      'synopsis' => $synopsis,
      'discussion' => $discussion,
      'context' => $context,
      'solution' => $solution
    ];
    $id = InsertPattern($value);
    print "<p class=\"alert\">Created a new <tt>$title</tt> pattern in <tt>{$pattern['pltitle']}</tt> with ID <tt>$id</tt>.</p>";
    return(true);
    
  }
  return(false);
   
} /* end pcreate() */


/* plcreate()
 *
 *  Create a pattern language.
 *
 *  We use this function to both present the form that solicits input
 *  and to process that input. A GET is "solicit" and a POST is absorb.
 */

function plcreate() {
  if($_SERVER['REQUEST_METHOD'] == 'POST') {
  
    # process a form submission

    $planguages = GetPLanguage();
    $title = trim($_POST['title']);
    if(! strlen($title)) {
      Error("Pattern language title cannot be empty.");
    }
    $baseurl = trim($_POST['baseurl']);
    foreach($planguages as $planguage) {
      if($planguage['title'] == $title) {
        Error("Pattern language title must be unique.");
      }
    }
    $value = ['title' => $title];
    if(strlen($baseurl)) {
      $value['baseurl'] = $baseurl;
    }
    $id = InsertPLanguage($value);
    print "<p class=\"alert\">Created a new <tt>$title</tt> pattern language with ID <tt>$id</tt>.</p>";
    return(true);
  } else {

    # solicit data to create a new planguage
    
    plform();
    return(false);
  }
  return(false);
  
} /* end plcreate() */

if(isset($_POST['submit']) && $_POST['submit'] == 'Cancel') {
  header("Location: {$_SERVER['SCRIPT_NAME']}\n");
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title>Pattern Management</title>
 <link rel="stylesheet" href="project/lib/ps.css">
 <script src="project/lib/ps.js"></script>
</head>

<body>

<header>
<h1>Pattern Management</h1>
</header>

<div id="poutine">
<img src="images/pattern-sphere-band.png" id="gravy">

<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && DEBUG)
  error_log("POST: " . var_export($_POST, true));
if($_SERVER['REQUEST_METHOD'] == 'GET' && DEBUG)
  error_log("GET: " . var_export($_GET, true));

$rv = true;

if(($_SERVER['REQUEST_METHOD'] == 'POST'
     && isset($_POST['action'])
     && ($action = $_POST['action'])) ||
   ($_SERVER['REQUEST_METHOD'] == 'GET'
     && isset($_GET['action'])
     && ($action = $_GET['action']))) {

  if($action == 'plcreate') {
    $rv = plcreate();
  } else if(preg_match('/^pcreate\d?$/', $action)) {
    $rv = pcreate($action);
  } else if($action == 'pledit') {
    $rv = pledit();
  } else if(preg_match('/^pedit\d?$/', $action)) {
    $rv = pedit();
  } elseif($action == 'pmanage') {
    $rv = pmanage();
  } elseif($action == 'passign') {
    $rv = passign();
  } else {
     Error("Unknown action <tt>$action</tt>");
  }
}
if($rv) {
  $pcreate = "?action=pcreate";
  if(isset($_POST) && isset($_POST['plid']))
    $pcreate .= "&plid={$_POST['plid']}";
?>
<p class="alert">Each pattern exists in the context of a single
pattern language. Patterns can be assessed in the context of any
number of projects. Project managers perform the task of selecting
which patterns are assessed in that project.  in a different
screen.</p>

<p class="alert">On this screen, an administrator can take actions
with respect to patterns and pattern languages. Create, edit, or
delete either by choosing the actions in the menu below.</p>

<h3>Actions:</h3>
<ul>
 <li><a href="<?=$pcreate?>">Create a pattern</a></li>
 <li><a href="?action=pedit">Edit or delete a pattern</a></li>
 <li class="spacer"></li>
 <li><a href="?action=plcreate">Create a language</a></li>
 <li><a href="?action=pledit">Edit or delete a language</a></li>
 <li class="spacer"></li>
 <li><a href="./">Return to the project directory</a></li>
</ul>
<?php
}
?>
<script>
init();
</script>
</div>
<?=FOOT?>
</body>
</html>
