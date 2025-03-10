/*
JavaScript file for FrontendForms module
contains no JQuery - pure JavaScript
*/

// run all functions inside its own scope to prevent conflicts with other JavaScript variables and functions
let frontendformsmain = function () {

    /*
    JavaScript counter in seconds
    Outputs a timer in seconds depending on values set in data attributes
    */
    function submitCounter() {
        let el = document.getElementById("timecounter");
        if (el) {
            let timeleft = parseInt(document.getElementById("minTime").getAttribute("data-time"));
            let timetext = document.getElementById("minTime").getAttribute("data-unit");
            timetext = timetext.split(";");

            let downloadTimer = setInterval(function () {
                if (timeleft <= 0) {
                    clearInterval(downloadTimer);
                    el.remove();
                }
                let text = timetext[0];
                if (timeleft <= 1) {
                    text = timetext[1];
                }
                el.innerText = timeleft + " " + text;
                timeleft -= 1;
            }, 1000);
        }
    }

    window.addEventListener("load", function () {

        submitCounter();
        ajaxSubmit();
        jumpToAnchor();
        maxCharsCounterReverse();


        // initialize all forms for the conditional form dependencies
        let frontendforms = document.getElementsByTagName('form');

        if (frontendforms.length > 0) {
            for (let i = 0; i < frontendforms.length; i++) {

                let formID = frontendforms[i].id;
                if(formID){
                    if (typeof mfConditionalFields !== 'undefined'){
                        mfConditionalFields("#" + formID, {rules: "inline", dynamic: true, debug: true});
                    }
                }
            }
        }

    });


    /*
    Show or hide the password in the password field by checking/unchecking the show/hide checkbox below the input field
    */
    document.addEventListener('click', function (element) {

        if (element.target.classList.contains('pwtoggle')) {
            let passwordInput = element.target.parentNode.previousElementSibling;
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }

    });


    /**
     * Remove a specific query string parameter from an url
     * @param url
     * @param parameter
     * @returns {string|*}
     */
    function removeURLParameter(url, parameter) {
        //prefer to use l.search if you have a location/link object
        let urlparts = url.split("?");
        if (urlparts.length >= 2) {

            let prefix = encodeURIComponent(parameter) + "=";
            let pars = urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (let i = pars.length; i-- > 0;) {
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
        let captcha = document.getElementById(id);
        if (captcha) {
            let src = captcha.src;
            src = removeURLParameter(src, "time");
            captcha.src = src + "&time=" + Date.now();
        }
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
            clear_link.style.display = "none";
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
                clear_link.style.display = "block";
            } else {
                clear_link.style.display = "none";
            }
        }
    }


    /**
     * Change HTML 5 validation attributes depending on values of another field
     */

// get all input HTML elements
    let numInputs = document.querySelectorAll('input');

// check if something has been changed inside an input field
    for (let i = 0; i < numInputs.length; i++) {
        numInputs[i].addEventListener("change", changeHTML5AttributeValue, false);
        numInputs[i].addEventListener("change", calculateTimeRange, false);
    }

// Change the HTML5 attribute on change
    function changeHTML5AttributeValue() {
        // find all instances where data-attribute is present
        let field_data_ID = this.id.replace(this.form.id + "-", "");
        let fields = document.querySelectorAll("[data-ff_field=" + field_data_ID + "]");

        if (fields.length > 0) {
            for (let i = 0; i < fields.length; i++) {
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
                            if (validator === "dateBeforeField") {
                                value = calculateBeforeAfterValue(-1, value);
                                value = value.toISOString().split('T')[0];
                            }
                            // check if validator is dateAfterField
                            if (validator === "dateAfterField") {
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
        this.id.replace(this.form.id + "-", "");
        let fields = document.querySelectorAll("[data-ff_validator]");

        if (fields.length > 0) {
            for (let i = 0; i < fields.length; i++) {

                // get the field object
                let field = document.getElementById(fields[i].id);
                if (field && ((field.dataset.ff_validator === "dateWithinDaysRange") || (field.dataset.ff_validator === "dateOutsideOfDaysRange"))) {
                    // get which attribute should be changed
                    let attribute = field.dataset.ff_attribute;
                    let value = this.value;
                    let days = field.dataset.ff_days;

                    if (attribute === 'min') {
                        if (attribute && value) {
                            // calculate new date value
                            let value2 = calculateNewDate(value, days, '+');
                            // convert to YYYY-mm-dd
                            value2 = new Date(value2).toISOString().slice(0, 10);
                            // validator dateOutsideOfDaysRange (min or max attribute)
                            if (field.dataset.ff_validator === 'dateOutsideOfDaysRange') {
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
        if (operator === '+') {
            result.setDate(result.getDate() + parseInt(days));
        } else {
            result.setDate(result.getDate() - parseInt(days));
        }
        return result;
    }

    /**
     * Jump to an internal anchor
     * @param anchor_id
     */
    function jumpTo(anchor_id) {
        let url = location.href;               //Saving URL without a hash.
        location.href = "#" + anchor_id;                 //Navigate to the target element.
        history.replaceState(null, null, url);   //method modifies the current history entry.
    }

    /**
     * Submit forms and send their form data via Ajax
     * Returns the validated form without reloading the page
     */
    function ajaxSubmit(formid = null) {


        if (formid) {
            let form = document.getElementById(formid);
            subAjax(form);
        } else {
            let pageforms = document.querySelectorAll('[data-submitajax]');

            // get all forms that contain the data-submitajax attribute
            if (pageforms.length) {
                let i = 0;
                for (i; i < pageforms.length; i++) {

                    subAjax(pageforms[i]);
                }
            }
        }

    }

    /**
     * Submit a form via Ajax
     * @param form
     */
    function subAjax(form) {

        if (typeof (form) == 'string') {
            form = document.getElementById(form);
        }

        if (form) {

            // add eventlistener to all forms which include the data-submit attribute
            form.addEventListener('submit', function (e) {

                e.preventDefault();

                let formid = form.dataset.submitajax;
                let action = form.getAttribute('action');
                let progress = document.getElementById(formid + '-form-submission');
                // show the info (text, progressbar, ...) by removing display:none from the outer container
                if (progress) {
                    progress.style.display = null;
                }

                // check if anchor is present in the action attribute
                let anchor = action.split(/#(.*)/)[1];
                if (anchor !== 'undefined') {
                    // no anchor, add the anchor of the ajax-wrapper div
                    anchor = formid + '-ajax-wrapper';
                }

                // make the Ajax request
                let xhr = new XMLHttpRequest();

                xhr.onload = function () {

                    let result = this.responseText;

                    const parser = new DOMParser();
                    let doc = parser.parseFromString(result, "text/html");

                    let wrapper = doc.getElementById(formid + '-ajax-wrapper');
                    let content = wrapper.innerHTML;

                    if (xhr.readyState === 4 && xhr.status === 200) {
                        let redirectFieldName = formid + '-ajax_redirect';
                        let redirectUrl = formData.get(redirectFieldName);

                        // check if the form is valid because redirect should only happen after a valid submission
                        if (wrapper.dataset.validated === '1') {
                            let anchorQueryString = '';

                            // check if a special redirect data attribute is present
                            if (redirectUrl) {
                                let urlParts = redirectUrl.split('#');
                                // check if an internal anchor is set
                                if (urlParts.length > 1) {

                                    // an internal anchor is set
                                    redirectUrl = urlParts[0];
                                    let anchor = urlParts[1];
                                    // instead of the anchor, use a query string which does not make problems
                                    anchorQueryString = '?fc-anchor=' + anchor;
                                }
                                window.location = redirectUrl + anchorQueryString;
                            } else {
                                // load the validated form back into the target div
                                document.getElementById(formid + '-ajax-wrapper').innerHTML = content;
                                // jump to the start of the form
                                jumpTo(anchor);
                            }
                        } else {
                            // form is not valid
                            // load the validated form back into the target div
                            document.getElementById(formid + '-ajax-wrapper').innerHTML = content;
                            // jump to the start of the form
                            jumpTo(anchor);
                            // load a new CAPTCHA if CAPTCHA is used
                            reloadCaptcha(formid + '-captcha-image', e);
                            // start as the first page load
                            ajaxSubmit();
                            // start counter
                            submitCounter();
                            // load star rating again if it exists
                            if (typeof stars !== 'undefined' || stars !== null) {
                                // variable is not undefined or not null
                                stars.rebuild();
                            }
                            // load a new Slider CAPTCHA if this CAPTCHA type has been selected
                            if (typeof listenToSliderCaptchaCheckboxes === "function") {
                                listenToSliderCaptchaCheckboxes();
                            }
                        }

                    }
                }
                xhr.open("POST", action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                let formData = new FormData(form);
                xhr.send(formData);
            });

        }
    }

    /**
     * Jump to an anchor if a query string with the name "fc-anchor" is present
     * The name of the anchor is the value of that querystring
     * This is necessary if a form has been submitted via Ajax and a redirect containing an anchor has been set
     * An usual anchor like #newtarget inside the redirect url will lead to stop loading the validated form correctly after a valid submission
     * So this is a JavaScript based work-around to be able to use internal anchors without problems inside redirects
     *
     */
    function jumpToAnchor() {
        const urlParams = new URLSearchParams(window.location.search);
        const anchor = urlParams.get('fc-anchor');
        if (anchor) {

            urlParams.delete("fc-anchor");
            location.hash = "#" + anchor;
        }
    }


    /**
     * Count the letters inside a textarea and output the characters left
     */
    function maxCharsCounterReverse() {
        const textarea = document.querySelector("textarea");

        if (!textarea) {
            return;
        }

        textarea.addEventListener("input", event => {
            const target = event.currentTarget;
            const maxLength = target.getAttribute("maxlength");

            if (maxLength) {

                const currentLength = target.value.length;
                const counterSpan = document.getElementById(target.id + '-char_count');

                if (counterSpan) {
                    // if max number of characters is reached, output a special info message
                    if (currentLength === maxLength) {
                        counterSpan.innerHTML = counterSpan.dataset.maxreached;
                    } else {
                        // change the current length inside the span element
                        counterSpan.children[0].innerHTML = maxLength - currentLength;
                    }
                }
            }
        });
    }

}();
