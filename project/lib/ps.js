// Submit button on the planguage edit/delete and select screens
var pledsubmit
// Edit and delete radio buttons on planguage edit/delete screen
var pledit
var pldelete
// Popup menu on planguage edit/delete and select screens
var selplid
// Pattern form elements
var pedsubmit
var pedit
var pdelete
var selpid


/* Show()
 *
 *  Toggle the 'display' CSS property of the <id>-ctl element between
 *  'block' and 'none' and change the label of the <id> element to
 *  match the current state.
 */

function show(id) {
  control = document.querySelector('#' + id + '-ctl');
  instructions = document.querySelector('#' + id);
  if(instructions.style.display == 'block') {
    control.innerHTML = '[Show instructions]';
    instructions.style.display = 'none';
  } else {
    control.innerHTML = '[Hide instructions]';
    instructions.style.display = 'block';
  }
} /* end show() */


/* Mask()
 *
 *  Given the id of an element, toggle the 'type' of the input element
 *  with a '-mask' suffix between 'password' and 'text' and change the
 *  label of the element to match - either 'Mask' or 'Unmask'.
 */

function Mask(id) {
    $password = document.querySelector('#' + id);
    $mask = document.querySelector('#' + id + '-mask');
    
    if($password.type == 'password') {
	$password.type = 'text';
	$mask.value = 'Mask';
    } else {
	$password.type = 'password';
	$mask.value = 'Unmask';
    }
    return true;
    
} /* end Mask() */


/* selplidf()
 *
 *  Called when any form element is manipulated, determine whether to
 *  enable or disable the submit button on the planguage edit/delete
 *  and select screens.
 */

function selplidf() {
    if(pledit) {
	if((pledit.checked || pldelete.checked) && selplid.selectedIndex)
	    pledsubmit.disabled = false
	else
	    pledsubmit.disabled = true
    } else if(selplid.selectedIndex)
	pledsubmit.disabled = false
    else
	pledsubmit.disabled = true
	
} /* end selplidf() */

/* selpidf()
 *
 *  Called when any form element is manipulated, determine whether to
 *  enable or disable the submit button on the planguage edit/delete
 *  screen.
 */

function selpidf() {
    if((pedit.checked || pdelete.checked) && selpid.selectedIndex)
	pedsubmit.disabled = false
    else
	pedsubmit.disabled = true
	
} /* end selpidf() */

/* init()
 *
 *  Called when the page is loaded, find the elements on this page,
 *  disable submit buttons, and add event listeners.
 */
function init() {
    // Submit button on the planguage edit/delete screen
    if(pledsubmit = document.querySelector('#pledsubmit')) {
	// Edit and delete radio buttons on planguage edit/delete screen
	pledit = document.querySelector('#pledit')
	pldelete = document.querySelector('#pldelete')
	// Popup menu on planguage edit/delete and select screens
	selplid = document.querySelector('#selplid')
    
	/* disable the submit button on the planguage edit/delete form
	 * and set event listeners on all the form elements that might
	 * cause it to be duly enabled */
    
	pledsubmit.disabled = true;
	selplid.addEventListener('change', selplidf)
	if(pledit) {
	    pledit.addEventListener('change', selplidf)
	    pldelete.addEventListener('change', selplidf)
	}
    }
    // Submit button on the pattern edit/delete screen
    if(pedsubmit = document.querySelector('#pedsubmit')) {
	// Edit and delete radio buttons on pattern edit/delete screen
	pedit = document.querySelector('#pedit')
	pdelete = document.querySelector('#pdelete')
	// Popup menu on pattern edit/delete screen
	selpid = document.querySelector('#selpid')
    
	/* disable the submit button on the planguage edit/delete form
	 * and set event listeners on all the form elements that might
	 * cause it to be duly enabled */
    
	pedsubmit.disabled = true;
	selpid.addEventListener('change', selpidf)
	pedit.addEventListener('change', selpidf)
	pdelete.addEventListener('change', selpidf)
    }
    
} /* init() */
