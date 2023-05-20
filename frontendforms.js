/*
JavaScript file for FrontendForms module
contains no JQuery - pure JavaScript
*/

/*
Javascript counter in seconds
Informs the user about how long he has to wait until he can submit the form once more
Runs only if minTime was set and the form was submitted to fast
*/

window.onload = function () {

    let el = document.getElementById("timecounter");
    if (el) {
        let timeleft = parseInt(document.getElementById("minTime").getAttribute("data-time"));
        let timetext = document.getElementById("minTime").getAttribute("data-unit");
        timetext = timetext.split(";");

        alert('timer');
        let downloadTimer = setInterval(function () {
            if (timeleft <= 0) {
                clearInterval(downloadTimer);
                el.remove();
            }
            let text = timetext[0];
            if (timeleft <= 1) {
                text = timetext[1];
            }
            el.innerText = timeleft + " " + text + ".";
            timeleft -= 1;
        }, 1000);
    }
}

/*
Show or hide the password in the password field by checking/unchecking the show/hide checkbox below the input field
*/

let togglePasswords = document.getElementsByClassName('pwtoggle');
if (togglePasswords.length > 0) {
    for (let i = 0; i < togglePasswords.length; i++) {
        if (togglePasswords[i].parentNode.previousElementSibling.type === 'password') {
            togglePasswords[i].addEventListener('click', function () {
                var passwordInput = togglePasswords[i].parentNode.previousElementSibling;
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                } else {
                    passwordInput.type = "password";
                }
            });
        }
    }
}

/**
 * Remove a specific query string parameter from an url
 * @param url
 * @param parameter
 * @returns {string|*}
 */
function removeURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts = url.split("?");
    if (urlparts.length >= 2) {

        var prefix = encodeURIComponent(parameter) + "=";
        var pars = urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (i = pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }

        url = urlparts[0] + "?" + pars.join("&");
        return url;
    } else {
        return url;
    }
}

// Reload the captcha image
function reloadCaptcha(id, event) {
    event.preventDefault();
    var src = document.getElementById(id).src;
    src = removeURLParameter(src, "time");
    document.getElementById(id).src = src + "&time=" + Date.now();
}

/**
 * Clear a file upload field by clicking on the link below the input field
 * @param event
 */
function clearInputfield(event) {
    let id = event.id;
    let uploadfield_id = id.replace("-clear", "");
    let uploadfield = document.getElementById(uploadfield_id);
    if (uploadfield.value) {
        uploadfield.value = null;
    }
    // set display:none to clear link wrapper
    let clear_link_id = id.replace("-clear", "-clearlink-wrapper");
    let clear_link = document.getElementById(clear_link_id);
    if (clear_link) {
        clear_link.style = "display:none";
    }
}

/**
 * Show the clear link only if a file was added to the input field
 * @param event
 */
function showClearLink(event) {
    let id = event.target.id + "-clearlink-wrapper";
    let clear_link = document.getElementById(id);
    if (clear_link) {
        if (event.target.files.length > 0) {
            clear_link.style = "display:block";
        } else {
            clear_link.style = "display:none";
        }
    }
}


/**
 * Change HTML 5 validation attributes depending of values of another field
 */

// get all input HTML elements
var numInputs = document.querySelectorAll('input');

// check if something has been changed inside an input field
for (var i = 0; i < numInputs.length; i++) {
    numInputs[i].addEventListener("change", changeHTML5AttributeValue, false);
    numInputs[i].addEventListener("change", calculateTimeRange, false);
}

// Change a HTML5 attribute on chang
function changeHTML5AttributeValue() {
    // find all instances where data-attribute is present
    let field_data_ID = this.id.replace(this.form.id + "-", "");
    var fields = document.querySelectorAll("[data-ff_field =" + field_data_ID + "]");

    if (fields.length > 0) {
        for (var i = 0; i < fields.length; i++) {
            // get the field object
            let field = document.getElementById(fields[i].id);
            if (field) {
                // get which attribute should be changed
                let attribute = field.dataset.ff_attribute;
                let validator = field.dataset.ff_validator;
                let value = this.value;
                if (attribute && value) {
                    if (validator) {
                        // check if validator is dateBeforeField
                        if (validator == "dateBeforeField") {
                            value = calculateBeforeAfterValue(-1, value);
                            value = value.toISOString().split('T')[0];
                        }
                        // check if validator is dateAfterField
                        if (validator == "dateAfterField") {
                            value = calculateBeforeAfterValue(1, value);
                            value = value.toISOString().split('T')[0];
                        }
                    }
                    field.setAttribute(attribute, value);
                }
            }
        }
    }

}

/**
 * Add or subtract 1 day from the date set, depending on if before or after was set
 * Result of the calculation will be set to the attribute
 * @param days
 * @param attribute
 * @param value
 */
function calculateBeforeAfterValue(days, value) {
    let result = new Date(value);
    result.setDate(result.getDate() + days);
    return result;
}

/**
 * Special function for time range validators dateWithinDaysRange amd dateOutsideOfDaysRange
 * Sets the second parameter of the time range (min or max)
 */
function calculateTimeRange() {

    // find all instances where data-attribute is present
    let field_data_ID = this.id.replace(this.form.id + "-", "");
    var fields = document.querySelectorAll("[data-ff_validator]");

    if (fields.length > 0) {
        for (var i = 0; i < fields.length; i++) {

            // get the field object
            let field = document.getElementById(fields[i].id);
            if (field && ((field.dataset.ff_validator == "dateWithinDaysRange") || (field.dataset.ff_validator == "dateOutsideOfDaysRange"))) {
                // get which attribute should be changed
                let attribute = field.dataset.ff_attribute;
                let attributename = '';
                let value = this.value;
                let days = field.dataset.ff_days;

                if (attribute = 'min') {
                    if (attribute && value) {
                        // calculate new date value
                        let value2 = calculateNewDate(value, days, '+');
                        // convert to YYYY-mm-dd
                        value2 = new Date(value2).toISOString().slice(0, 10);
                        // validator dateOutsideOfDaysRange (min or max attribute)
                        if (field.dataset.ff_validator == 'dateOutsideOfDaysRange') {
                            if (days > 0) {
                                // positive days value
                                field.setAttribute('min', value2);
                            } else if (days < 0) {
                                // negative days value
                                field.setAttribute('max', value2);
                            } else {
                                // value is zero - remove max attribute
                                field.removeAttribute('max');
                            }
                        } else {
                            // validator dateWithinDaysRange (min and max attribute)
                            if (days > 0) {
                                field.setAttribute('min', value);
                                field.setAttribute('max', value2);
                            } else {
                                field.setAttribute('max', value);
                                field.setAttribute('min', value2);
                            }
                        }
                    }
                } else {

                    field.removeAttribute("max");
                }
            }
        }
    }
}

/**
 * Add or subtract days to a given date and output the new date
 * @param date
 * @param days
 * @param operator
 * @returns {Date}
 */
function calculateNewDate(date, days, operator) {
    let result = new Date(date);
    if (operator = '+') {
        result.setDate(result.getDate() + parseInt(days));
    } else {
        result.setDate(result.getDate() - parseInt(days));
    }
    return result;
}
