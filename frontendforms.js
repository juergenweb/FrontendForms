/*
JavaScript file for the FrontendForms module
contains no JQuery - pure JavaScript
*/

/*
JavaScript counter in seconds
Outputs a timer in seconds depending on values set in data attributes
*/
function submitCounter() {
    let timeAlert = document.getElementById("ff-time-alert");
    let el = document.getElementById("timecounter");
    if (el) {
        let timeleft = parseInt(document.getElementById("minTime").getAttribute("data-time"));
        let timetext = document.getElementById("minTime").getAttribute("data-unit");
        timetext = timetext.split(";");

        let downloadTimer = setInterval(function () {
            if (timeleft <= 0) {
                clearInterval(downloadTimer);
                el.remove();

                let fadeEffect = setInterval(function () {
                    if (!timeAlert.style.opacity) {
                        timeAlert.style.opacity = 1;
                    }
                    if (timeAlert.style.opacity > 0) {
                        timeAlert.style.opacity -= 0.1;
                    } else {
                        clearInterval(fadeEffect);
                        timeAlert.remove();
                    }
                }, 200);
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

// Handler for File Uploads
function handleFileUploads() {
    // file list
    const dt = new DataTransfer();

    let fileuploadFields = document.querySelectorAll(".fileupload");

    if (fileuploadFields.length > 0) {

        for (let i = 0; i < fileuploadFields.length; i++) {

            fileuploadFields[i].addEventListener("change", function () {

                let fieluploadField = fileuploadFields[i];
                let multiple = fieluploadField.hasAttribute("multiple");
                let framework = fieluploadField.dataset.framework;
                let fileuploadFieldID = fieluploadField.id;
                let fileList = document.getElementById(fileuploadFieldID + "-files");
                let totalFileSize = parseInt(fileuploadFields[i].dataset.filesize);
                let allowedFileSize = 0;
                let allowedTotalFileSize = 0;

                // check if maxfilesize is present
                if (fieluploadField.dataset.maxfilesize) {
                    allowedFileSize = fieluploadField.dataset.maxfilesize;
                }

                // check if maxtotalfilesize is present
                if (fieluploadField.dataset.maxtotalfilesize) {
                    allowedTotalFileSize = fieluploadField.dataset.maxtotalfilesize;
                }

                let validFileSize = true;
                let invalidFileSizeClass = "";
                let invalidfilezSizeSpanClass = "";
                let invalidNotesClass = "";
                let notesAllowedFileSizeElement = document.getElementById(fileuploadFieldID + "-allowedFileSize");
                let notesAllowedTotalFileSizeElement = document.getElementById(fileuploadFieldID + "-allowedTotalFileSize");

                // Loop through selected files and handle each one
                for (let i = 0; i < this.files.length; i++) {
                    let file = this.files[i];

                    let fileSize = formatBytes(file.size, 2);

                    totalFileSize += file.size;

                    //remove previous file block if file upload does not allow multiple files
                    if (!multiple) {
                        if(fileList){
                            fileList.innerHTML = "";
                        }
                        totalFileSize = file.size;
                    }

                    // Create file block
                    let fileBlock = document.createElement("div");
                    fileBlock.className = "file-block";

                    // compare allowed filesize and current file size
                    if (allowedFileSize !== 0 && file.size > allowedFileSize) {
                        validFileSize = false;

                    }

                    switch (framework) {
                        case "uikit3":
                            if (!validFileSize) {
                                invalidFileSizeClass = " uk-badge-danger";
                                invalidfilezSizeSpanClass = " ff-invalid-fs";
                                invalidNotesClass = "uk-text-danger";
                            }
                            invalidTotalFileSizeNotesClass = " uk-text-danger";

                            // create the badge markup
                            let badgeContentUK = "<span class='uk-light uk-badge uk-padding-small uk-margin-xsmall-top" + invalidFileSizeClass + "'>";
                            badgeContentUK += "<span class='file-delete uk-margin-xsmall-right'><span data-uk-icon='icon: close'></span></span>";
                            badgeContentUK += "<span class='file-name'>" + file.name + "</span>";
                            badgeContentUK += "<span class='ff-file-size " + invalidfilezSizeSpanClass + "'>(" + fileSize + ")</span></span>";
                            fileBlock.innerHTML = badgeContentUK;

                            if (notesAllowedFileSizeElement && !validFileSize && !notesAllowedFileSizeElement.hasAttribute("class")) {
                                notesAllowedFileSizeElement.className += invalidNotesClass;
                            } else {
                                if(!multiple) {
                                    if(notesAllowedFileSizeElement){
                                        notesAllowedFileSizeElement.removeAttribute("class");
                                    }
                                }
                            }

                            validFileSize = true;
                            invalidFileSizeClass = "";
                            invalidfilezSizeSpanClass = "";
                            invalidNotesClass = "";

                            break;
                        case "bootstrap5":
                            if (!validFileSize) {
                                invalidFileSizeClass = " bg-danger";
                                invalidfilezSizeSpanClass = " ff-invalid-fs";
                                invalidNotesClass = "text-danger";
                            } else {
                                invalidFileSizeClass = " bg-primary";
                            }

                            invalidTotalFileSizeNotesClass = " text-danger";

                            // create the badge markup
                            let badgeContentBS = "<span class='badge mt-2 p-2" + invalidFileSizeClass + "'>";
                            badgeContentBS += "<span class='file-delete me-1'><span class='ff-close'></span></span>";
                            badgeContentBS += "<span class='file-name'>" + file.name + "</span>";
                            badgeContentBS += "<span class='ff-file-size " + invalidfilezSizeSpanClass + "'>(" + fileSize + ")</span></span>";
                            fileBlock.innerHTML = badgeContentBS;

                            if (notesAllowedFileSizeElement && !validFileSize && !notesAllowedFileSizeElement.hasAttribute("class")) {
                                notesAllowedFileSizeElement.className += invalidNotesClass;
                            } else {
                                if(!multiple) {
                                    notesAllowedFileSizeElement.removeAttribute("class");
                                }
                            }
                            validFileSize = true;
                            invalidFileSizeClass = "";
                            invalidfilezSizeSpanClass = "";

                            break;
                        default:
                            if (!validFileSize) {
                                invalidFileSizeClass = " text-danger";
                                invalidfilezSizeSpanClass = " ff-invalid-fs";
                                invalidNotesClass = "text-danger";
                            }

                            invalidTotalFileSizeNotesClass = " text-danger";

                            // create the badge markup
                            let badgeContentDef = "<span class='ff-file-item" + invalidFileSizeClass + "'>";
                            badgeContentDef += "<span class='file-delete'><span class='ff-close'></span></span>";
                            badgeContentDef += "<span class='file-name'>" + file.name + "</span>";
                            badgeContentDef += "<span class='ff-file-size " + invalidfilezSizeSpanClass + "'>(" + fileSize + ")</span></span>";
                            fileBlock.innerHTML = badgeContentDef;

                            if (notesAllowedFileSizeElement && !validFileSize && !notesAllowedFileSizeElement.hasAttribute("class")) {
                                notesAllowedFileSizeElement.className += invalidNotesClass;
                            } else {
                                if(!multiple) {
                                    notesAllowedFileSizeElement.removeAttribute("class");
                                }
                            }
                            invalidFileSizeClass = "";
                            invalidfilezSizeSpanClass = "";
                            validFileSize = true;

                    }

                    // Add block to list
                    if(fileList){
                        fileList.appendChild(fileBlock);
                    }

                    // Add event listener for delete
                    fileBlock.querySelector(".file-delete").addEventListener("click", (e) => deleteFileBlock(e, fileBlock, dt, fieluploadField));

                    // Add file to DataTransfer object
                    dt.items.add(file);
                }

                let totalSizeDiv = document.getElementById(fileuploadFieldID + "-total");
          
                // compare allowed total filesize and file sizes of all selected files
                if (allowedTotalFileSize !== 0 && totalFileSize > allowedTotalFileSize) {
                    notesAllowedTotalFileSizeElement.className += invalidTotalFileSizeNotesClass;

                    if (totalSizeDiv) {
                        totalSizeDiv.className += invalidTotalFileSizeNotesClass;
                    }

                }

                fileuploadFields[i].dataset.filesize = String(totalFileSize);

                if (totalSizeDiv) {
                    totalSizeDiv.innerHTML = formatBytes(totalFileSize);
                }

                // Update the file input with the new DataTransfer file list
                this.files = dt.files;

            });

        }
    }

}

// Function to delete a file block
function deleteFileBlock(e, fileBlock, dt, inputfield) {

    let totalFileSize = inputfield.dataset.filesize;
    let name = fileBlock.querySelector(".file-name").textContent;
    let notesAllowedFileSizeElement = document.getElementById(inputfield.id + "-allowedFileSize");
    let notesAllowedTotalFileSizeElement = document.getElementById(inputfield.id + "-allowedTotalFileSize");
    let totalSizeDiv = document.getElementById(inputfield.id + "-total");
    let newTotalFileSize = 0;

    fileBlock.remove();

    let fileSizes = [];
    for (let i = 0; i < dt.items.length; i++) {

        if (name === dt.items[i].getAsFile().name) {
            let fileSizeRemoved = dt.items[i].getAsFile().size;
            dt.items.remove(i);
            newTotalFileSize = totalFileSize - fileSizeRemoved;
            inputfield.dataset.filesize = String(newTotalFileSize);
            if (totalSizeDiv) {
                totalSizeDiv.innerHTML = formatBytes(newTotalFileSize);
            }
            break;
        } else {
            fileSizes.push(dt.items[i].getAsFile().size);
        }

    }

    // check if there is at least 1 file which is larger than allowed, otherwise remove the warning text class from the notes
    if (fileSizes.every(value => {
        return value <= inputfield.dataset.maxfilesize
    })) {
        if (totalSizeDiv) {
            totalSizeDiv.removeAttribute("class");
        }
        if (notesAllowedFileSizeElement) {
            notesAllowedFileSizeElement.removeAttribute("class");
        }
    }

    // check if total files size is not larger than allowed
    if (Number.isInteger(newTotalFileSize) && inputfield.dataset.maxtotalfilesize && newTotalFileSize <= inputfield.dataset.maxtotalfilesize) {
        if (notesAllowedTotalFileSizeElement) {
            notesAllowedTotalFileSizeElement.removeAttribute("class");
        }
    }

    document.querySelector(".fileupload").files = dt.files;
}

// check if DOM is loaded completely
window.addEventListener("DOMContentLoaded", function () {

    submitCounter();
    ajaxSubmit();
    jumpToAnchor();
    maxCharsCounterReverse();
    handleFileUploads();


    // initialize all forms for the conditional form dependencies
    let frontendforms = document.getElementsByTagName("form");

    if (frontendforms.length > 0) {
        for (let i = 0; i < frontendforms.length; i++) {

            let formID = frontendforms[i].id;
            if (formID) {
                if (typeof mfConditionalFields !== "undefined") {
                    mfConditionalFields("#" + formID, {rules: "inline", dynamic: true, debug: true});
                }
            }
        }
    }

});

function formatBytes(bytes, decimals = 2) {

    if (!+bytes) return "0 B";

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
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

/*
Show or hide the password in the password field by checking/unchecking the show/hide checkbox below the input field
*/
document.addEventListener("click", function (element) {

    if (element.target.classList.contains("pwtoggle")) {
        let passwordInput = element.target.parentNode.previousElementSibling;
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
        } else {
            passwordInput.type = "password";
        }
    }

});

/**
 * Remove a specific query string parameter from a url
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

/**
 * Change HTML 5 validation attributes depending on values of another field
 */

// get all input HTML elements
let numInputs = document.querySelectorAll("input");

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
                            value = value.toISOString().split("T")[0];
                        }
                        // check if validator is dateAfterField
                        if (validator === "dateAfterField") {
                            value = calculateBeforeAfterValue(1, value);
                            value = value.toISOString().split("T")[0];
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

                if (attribute === "min") {
                    if (attribute && value) {
                        // calculate new date value
                        let value2 = calculateNewDate(value, days, "+");
                        // convert to YYYY-mm-dd
                        value2 = new Date(value2).toISOString().slice(0, 10);
                        // validator dateOutsideOfDaysRange (min or max attribute)
                        if (field.dataset.ff_validator === "dateOutsideOfDaysRange") {
                            if (days > 0) {
                                // positive days value
                                field.setAttribute("min", value2);
                            } else if (days < 0) {
                                // negative days value
                                field.setAttribute("max", value2);
                            } else {
                                // value is zero - remove max attribute
                                field.removeAttribute("max");
                            }
                        } else {
                            // validator dateWithinDaysRange (min and max attribute)
                            if (days > 0) {
                                field.setAttribute("min", value);
                                field.setAttribute("max", value2);
                            } else {
                                field.setAttribute("max", value);
                                field.setAttribute("min", value2);
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
    if (operator === "+") {
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
        let pageforms = document.querySelectorAll("[data-submitajax]");

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

    if (typeof (form) == "string") {
        form = document.getElementById(form);
    }

    if (form) {

        // add eventlistener to all forms which include the data-submit attribute
        form.addEventListener("submit", function (e) {

            e.preventDefault();

            let formid = form.dataset.submitajax;
            let action = form.getAttribute("action");
            let progress = document.getElementById(formid + "-form-submission");
            // show the info (text, progressbar, ...) by removing display:none from the outer container
            if (progress) {
                progress.style.display = null;
            }

            // check if anchor is present in the action attribute
            let anchor = action.split(/#(.*)/)[1];
            if (anchor !== "undefined") {
                // no anchor, add the anchor of the ajax-wrapper div
            }

            // make the Ajax request
            let xhr = new XMLHttpRequest();

            xhr.upload.addEventListener("progress", function (event) {
                if (event.lengthComputable) {
                    let percent = Math.round((event.loaded / event.total) * 100);
                    percent = percent - 1;

                    let progressBar = document.getElementById(formid + "-progressbar");
                    if (progressBar) {
                        progressBar.dataset.percent = String(percent);
                        progressBar.style.width = percent + "%";
                    }
                }
            });

            xhr.onload = function () {

                let result = this.responseText;

                const parser = new DOMParser();
                let doc = parser.parseFromString(result, "text/html");

                let wrapper = doc.getElementById(formid + "-ajax-wrapper");
                let content = wrapper.innerHTML;

                if (xhr.readyState === 4 && xhr.status === 200) {
                    let redirectFieldName = formid + "-ajax_redirect";
                    let redirectUrl = formData.get(redirectFieldName);

                    // check if the form is valid because redirect should only happen after a valid submission
                    if (wrapper.dataset.validated === "1") {
                        let anchorQueryString = "";

                        // check if a special redirect data attribute is present
                        if (redirectUrl) {
                            let urlParts = redirectUrl.split("#");
                            // check if an internal anchor is set
                            if (urlParts.length > 1) {

                                // an internal anchor is set
                                redirectUrl = urlParts[0];
                                let anchor = urlParts[1];
                                // instead of the anchor, use a query string which does not make problems
                                anchorQueryString = "?fc-anchor=" + anchor;
                            }
                            window.location = redirectUrl + anchorQueryString;
                        } else {
                            // load the validated form back into the target div
                            document.getElementById(formid + "-ajax-wrapper").innerHTML = content;
                            // jump to the start of the form
                            jumpTo(anchor);
                        }
                    } else {

                        // form is not valid
                        // load the validated form back into the target div
                        document.getElementById(formid + "-ajax-wrapper").innerHTML = content;
                        // jump to the start of the form
                        jumpTo(anchor);
                        // load a new CAPTCHA if CAPTCHA is used
                        reloadCaptcha(formid + "-captcha-image", e);
                        // start as the first page load
                        ajaxSubmit();
                        // start counter
                        submitCounter();
                        // handle file uploads
                        handleFileUploads();
                        // load star rating again if it exists
                        if (typeof stars !== "undefined" && stars !== null) {
                            // variable is not undefined or not null
                            stars.rebuild();
                        }
                        // load a new Slider CAPTCHA if this CAPTCHA type has been selected
                        if (typeof listenToSliderCaptchaCheckboxes === "function") {
                            listenToSliderCaptchaCheckboxes();
                        }
                    }

                }
            };
            // get the method for sending the form data (get or post)
            let method = form.method;

            //sanitize method to be all uppercase
            method = method.toUpperCase();

            // convert all methods which are not GET or POST to POST
            const allowedMethods = ["POST", "GET"];
            if (!allowedMethods.includes(method)) {
                method = "POST";
            }

            let formData = new FormData(form);

            // convert formData to query string if GET method is chosen
            let queryString;
            if (method === "GET") {
                queryString = new URLSearchParams(formData);
                action = action + "?" + String(queryString);
            }

            xhr.open(method, action);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

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
    const anchor = urlParams.get("fc-anchor");
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
            const counterSpan = document.getElementById(target.id + "-char_count");

            if (counterSpan) {
                // if max number of characters is reached, output a special info message
                if (currentLength === maxLength) {
                    counterSpan.innerHTML = counterSpan.dataset.maxreached;
                } else {
                    // change the current length inside the span element
                    counterSpan.children[0].innerHTML = String(maxLength - currentLength);
                }
            }
        }
    });
}



