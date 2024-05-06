<?php
#
# NAME
#
#  project/results.php
#
# CONCEPT
#
#  Pattern assessment results page.
#
# FUNCTIONS
#
#  vmeter()  vote meter
#
# NOTES
#
#  When called as 'aresults.php', show anonymized results to any user,
#  including a visitor. A symlink makes aresults.php exist.
#
# $Id: results.php,v 1.28 2023/03/22 20:43:02 rose Exp $

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require "lib/ps.php";


/* vmeter()
 *
 *  Vote meter function returns two SVG lines - a green one anchored on
 *  the left with a length proportional to the 1 votes and a red one
 *  anchored on the right proportional to the 0 votes. The total is
 *  the number of active voters. NB: there is no support for assessment
 *  spaces with more than two values.
 */
 
function vmeter($v1, $v0, $total) {
  if(!$total)
    return null;
  $prop1 = $v1/$total * 100;
  $prop0 = $v0/$total * 100;
  $line1 = '<line x1="0%" y1="50%" y2="50%" stroke-width="100%" stroke="green" x2="' . $prop1 . '%"/>';
  $line0 = '<line x2="100%" y1="50%" y2="50%" stroke-width="100%" stroke="red" x1="' . (100 - $prop0) . '%"/>';
  return("$line1\n$line0\n");
  
} /* end vmeter() */


/* Main program. */

DataStoreConnect();
Initialize();
$labels = array_merge([$project['nulllabel']],
                      explode(':', $project['labels']));
$labelString = implode(' / ', explode(':', $project['labels']));

/* Redirect if unauthenticated or unauthorized. */

$user = $auth->getCurrentUser(true);

if(preg_match('/aresults.php$/', $_SERVER['SCRIPT_NAME'])) {
  $Anonymize = true;
  $tclass = 'omicron';
  $function = 'Anonymized Results';
} else {
  $Anonymize = false;
  $tclass = 'polio';
  if(!$user || ($user['role'] != 'super' && $user['role'] != 'manager')) {
    header('Location: ./');
    exit;
  }
  $function = 'Results';
}
?>
<!doctype html>
<html lang="en">

<head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 <title><?=$project['title']?> : Results</title>
 <link rel="stylesheet" href="lib/ps.css">
 <script src="lib/ps.js"></script>
 <style>
 </style>
</head>

<body>

<header>
<h1><?=$project['title']?>: <?=$function?></h1>
</header>

<div id="poutine">
<img src="../images/pattern-sphere-band.png" id="gravy">

<?php

$results = Stats();
$projid = $project['id'];
$assessments = $results['byuid'];
$pusers = ProjectMembers($projid);
$counts = [
  'total' => 0,
  'active' => 0,
  'inactive' => 0,
  'voter' => 0,
  'abstainer' => 0,
];

$acount = count(explode(':', $project['labels']));

/* Compute $counts here; we need that data for both anonymized and
 * attributed screens. */
 
foreach($pusers as $puser) {
  if($puser['isactive']) {
    $counts['active']++;
    if(isset($assessments[$puser['userid']])) {
      $stat = $assessments[$puser['userid']];
      $assessed = false;
      for($i = 0; $i < $acount; $i++) {
        if($stat[$i]['count'])
          $assessed = true;
      }
    }
    if(isset($stat) && $assessed)
      $counts['voter']++;
    else
      $counts['abstainer']++;
  } else
     $counts['inactive']++;
  $counts['total']++;
}

if(!$Anonymize) {
?>
<ul>
 <li><a href="#users">Users</a></li>
 <li><a href="#patterns">Patterns</a></li>
 <li><a href="#pcomments">Pattern Comments</a></li>
 <li><a href="#scomments">System Comments</a></li>
 <li style="margin-top: .5em"><a href="./">Return to project</a></li> 
</ul>

<h2 id="users">Users</h2>

<div class="measles">
<div class="smallpox">Key</div>
<div class="f voter">voter</div><div class="voter">has voted</div>
<div class="f abstainer">abstainer</div><div class="abstainer">has not voted</div>
<div class="f inactive">inactive</div><div class="inactive">did not complete registration</div>
</div>

<div class="flu">
<div class="covid">Email</div>
<div class="covid">Fullname</div>
<div class="covid">Username</div>
<div class="covid">Active</div>
<div class="covid">Teams</div>
<div class="covid">Votes (<?=$labelString?>)</div>
<div class="covid">Contact</div>
<div class="covid">Comment</div>
<?php

foreach($pusers as $puser) {

  $votes = '';
  if($puser['isactive']) {

    if(isset($assessments[$puser['userid']])) {
      $stat = $assessments[$puser['userid']];
      $assessed = false;
      for($i = 0; $i < $acount; $i++)
        if($stat[$i]['count'])
          $assessed = true;
    }

    if(isset($stat) && $assessed) {
      $class = 'voter';
      $votes = '';
      for($i = 0; $i < $acount; $i++) {
        if(strlen($votes))
          $votes .= ' / ';
        $votes .= $stat[$i]['count'];
      }
      $ass = GetAssessment([
       'userid' => $puser['userid'],
       'projid' => $projid
      ]);
    } else {
      $class = 'abstainer';
      $ass = null;
      $votes = '';
    }
  } else {
     $class = 'inactive';
     $ass = null;
  }

  $contact = (isset($ass) && $ass['contact'] == 'y')
   ? "<a href=\"mailto:{$puser['email']}\" title=\"compose mail to {$puser['fullname']}\">yes</a>" : 'no';
   
  if(isset($ass['acomment']) && strlen($ass['acomment'])) {
    $comment = 'Show';
    $cclass = ' class="commenter"';
    $ctrid = " id=\"controller{$puser['userid']}\"";
    $cdiv = "<div class=\"mumps\" id=\"user{$puser['userid']}\">
{$ass['acomment']}
</div>\n";
  } else {
    $cclass = '';
    $comment = '(none)';
    $ctrid = '';
    $cdiv = '';
  }
  print "<div class=\"$class\">{$puser['email']}</div>
 <div class=\"$class\">{$puser['fullname']}</div>
 <div class=\"$class\">{$puser['username']}</div>
 <div class=\"$class\" style=\"text-align: center\">" . ($puser['isactive'] ? 'yes' : 'no') . "</div>
 <div class=\"$class\">{$puser['team']}</div>
 <div class=\"$class\" style=\"text-align: center\">$votes</div>
 <div class=\"$class\" style=\"text-align: center\">$contact</div>
 <div$cclass class=\"$class\"$ctrid>$comment</div>
$cdiv
";
  }
    
} /* end loop on pusers */

// If we have unattributed voters, subtract from 'abstainer' and add to 'voter'.

$counts['abstainer'] -= $project['unattr_voter'];
$counts['voter'] += $project['unattr_voter'];

?>
</div>

<div class="measles">
 <div class="f">All members</div><div><?=$counts['total']?></div>
 <div class="f">Active</div><div><?=$counts['active']?></div>
 <div class="f">Voters</div><div><?=$counts['voter']?></div>
 <div class="f">Abstainers</div><div><?=$counts['abstainer']?></div>
 <div class="f">Inactive</div><div><?=$counts['inactive']?></div>
</div>

<h2 id="patterns">Patterns</h2>

<?php

/* Below is results content shown in both anonymous and attributed modes.
 * Anonymized content lacks the Commentary column. */

?>
<div class="<?=$tclass?>">
 <div class="covid" style="background-color: #ffc">Pattern Title</div>
 <div class="covid" style="background-color: #ffc">Origin</div>
 <div class="covid" style="background-color: #ffc">Votes (<?=$labelString?>)</div>
 <div class="covid" style="background-color: #ffc">Meter</div>
<?php
if(!$Anonymize) {
  print "<div class=\"covid\" style=\"background-color: #ffc\">Commentary</div>\n";
}

# An array with an element for each pattern.

$patterns = $results['bypid'];

foreach($patterns as &$pattern) {
  $vs = '';
  for($i = 0; $i < $acount; $i++) {
    if(strlen($vs))
      $vs .= ' / ';
    $vs .= $pattern['assess'][$i];
  }

  if(isset($ovs) && $vs != $ovs)
    $setoff = ' class="setoff"';
  else
    $setoff = '';

  $Commentary = '';
  if($ccount = count($pattern['commentary'])) {
    $commentary = "$ccount comment" .
      (($ccount == 1) ? '' : 's');
    $cclass='ebola';
    foreach($pattern['commentary'] as $c) {
      $class = isset($c['assessment'])
        ? $c['assessment'] : 'neutral';
      if($Anonymize) {
        $Commentary .= "<span class=\"$class\">{$c['commentary']}</span>\n";
      } else {
        $puser = $pusers[$c['userid']];
        $Commentary .= "<span class=\"$class\" title=\"{$puser['email']} {$puser['username']}\"><i>{$puser['fullname']}</i> {$c['commentary']}</span>\n";
      }
    }
  } else {
    $commentary = '(none)';
    $cclass = 'ringworm';
  }
  if(strlen($setoff))
    $cclass .= ' setoff';
  $pattern['vs'] = $vs;
  $lines = vmeter($pattern['assess'][1], $pattern['assess'][0], $counts['voter']);
  print "<div$setoff>{$pattern['ptitle']}</div>
<div$setoff>{$pattern['pltitle']}</div>
<div style=\"text-align: center\"$setoff>$vs</div>
<div$setoff><svg class=\"epstein\">$lines</div>
";
  if($tclass == 'polio') {
    print "<div class=\"$cclass\" id=\"c{$pattern['pid']}\"$setoff>$commentary</div>
<div class=\"influenza\" id=\"i{$pattern['pid']}\">$Commentary</div>
";
  }
  $ovs = $vs;
  
} /* end loop on assessed patterns */

print "</div>\n\n";

if(!$Anonymize) {

  print "<h2 id=\"pcomments\">Pattern Comments</h2>\n\n";

  foreach($patterns as $pattern) {
    print "<h3>{$pattern['ptitle']} ({$pattern['pltitle']}) {$pattern['vs']}</h3>
";
    if(count($pattern['commentary'])) {
      foreach($pattern['commentary'] as $c) {
        $puser = $pusers[$c['userid']];
        $vote = isset($c['assessment']) ? $c['assessment'] : 'unranked';
        print "<p><span style=\"text-decoration: underline\">{$puser['fullname']} ($vote)</span>: {$c['commentary']}</p>\n";
      }
    } else {
      print "<p>No comments.</p>\n";
    }
  }
  print "\n<h2 id=\"scomments\">System Comments</h2>\n\n";

  $sccount = 0;
  foreach($pusers as $puser) {
    if($puser['isactive']) {
      $ass = GetAssessment([
       'userid' => $puser['userid'],
       'projid' => $projid
      ]);
      if(isset($ass) && isset($ass['acomment']) && strlen($ass['acomment'])) {
        print "<p><span style=\"text-decoration: underline\">{$puser['fullname']}</span>: {$ass['acomment']}</p>\n";
        $sccount++;
      }
    }
  } /* end loop on members */
  
  if(!$sccount)
    print '<p class="alert">No system comments yet.</p>
';
} /* end not anonymized */
?>
<p><a href="./">Return to the project.</a></p>

<script>

  // match pattern id in HTML element id
  
  const re = /^\D+(\d+)$/

  // Toggle display of the targetted (per-user) comment.
  
  function tog(event) {
      controller = event.target
      match = re.exec(controller.id)
      /* extract the pattern id to find the content element */
      commenterId = '#user' + match[1]
      commenter = document.querySelector(commenterId)
      if(commenter.style.display == 'grid') {
        commenter.style.display = 'none'
        controller.innerHTML = 'Show'
      } else {
        controller.innerHTML = 'Hide'
        commenter.style.display = 'grid'
      }
      
  } // end tog()
  
  // Toggle display of the per-pattern comments.

  function tog2(event) {
      controller = event.target
      match = re.exec(controller.id)
      commentaryId = '#i' + match[1]
      commentary = document.querySelector(commentaryId)
      if(commentary.style.display == 'grid') {
        commentary.style.display = 'none'
        //controller.innerHTML = controller.innerHTML.replace('Hide', 'Show')
      } else {
        commentary.style.display = 'grid'
        //controller.innerHTML = controller.innerHTML.replace('Show', 'Hide')
      }
      
  } // end tog2()

  // add event listeners
  
  commenters = document.querySelectorAll('.commenter');
  commenters.forEach(commenter => {
    commenter.addEventListener('click', tog)
  })
  commentaries = document.querySelectorAll('.ebola')
  commentaries.forEach(commentary => {
    commentary.addEventListener('click', tog2)
  })
</script>
</div>
<?=FOOT?>
</body>
</html>
