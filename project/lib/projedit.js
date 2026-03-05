/* Number of labels we have. */

var count = 2

/* init()
 *
 * Add an event listener for 'click' on #newl to invoke addl().
 */

function init() {
    if(newl = document.querySelector('#newl'))
      newl.addEventListener('click', addl)
}

/* addl()
 *
 *  Event handler for the 'click' event on #newl adds a text label and
 *  three input elements (for label, value, color).
 */

function addl() {
    // alert('add')
    count++
    const d = this.parentElement // DIV containing 'add' button
    const contents = d.parentElement // FORM

    // create and insert a DIV for the field label

    var d1 = document.createElement('div')
    d1.setAttribute('class', 'fieldlabel')
    d1.innerHTML = 'Label/value ' + count + ':'
    contents.insertBefore(d1, d)

    // create and insert a DIV for the INPUTs

    var d2 = document.createElement('div')
    var i1 = document.createElement('input')
    var i2 = document.createElement('input')
    var i3 = document.createElement('input')
    i1.setAttribute('style', 'margin: 2px')
    i2.setAttribute('style', 'margin: 2px')
    i3.setAttribute('style', 'margin: 2px')
    i1.setAttribute('type', 'text')
    i2.setAttribute('type', 'text')
    i3.setAttribute('type', 'text')
    i1.setAttribute('name', 'l' + count)
    i2.setAttribute('name', 'v' + count)
    i3.setAttribute('name', 'c' + count)
    i1.setAttribute('size', 20)
    i2.setAttribute('size', 4)
    i2.setAttribute('size', 10)
    d2.appendChild(i1)
    d2.appendChild(i2)
    d2.appendChild(i3)
    contents.insertBefore(d2, d)
}
