/*
JavaScript file for FrontendForms module
contains no JQuery - pure JavaScript
*/

/*
Javascript counter in seconds
Informs the user about how long he has to wait until he can submit the form once more
Runs only if minTime was set and the form was submitted to fast
*/

document.onreadystatechange = onReady;

function onReady() {
    if (document.readyState === "complete") {
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
                el.innerText = timeleft + " " + text + ".";
                timeleft -= 1;
            }, 1000);
        }
    }


}

/*
Show or hide the password in the password field by checking/unchecking the show/hide checkbox below the input field
*/

let togglePasswords = document.getElementsByClassName("pwtoggle");
if(togglePasswords.length > 0){

    for (i = 0; i < togglePasswords.length; i++) {
        console.log(togglePasswords[i].parentNode.previousElementSibling);
        if(togglePasswords[i].parentNode.previousElementSibling.type === "password"){
            togglePasswords[i].addEventListener("click", function () {
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
    var urlparts= url.split("?");
    if (urlparts.length>=2) {

        var prefix= encodeURIComponent(parameter)+"=";
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (i= pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }

        url= urlparts[0]+"?"+pars.join("&");
        return url;
    } else {
        return url;
    }
}

// Reload the captcha image
function reloadCaptcha(id, event) {
    event.preventDefault();
    var src = document.getElementById(id).src;
    console.log(src);
    src = removeURLParameter(src, "time");
    document.getElementById(id).src = src +  "&time=" + Date.now();
}

/**
 * Clear a file upload field by clicking on the link below the input field
 * @param event
 */
function clearInputfield(event){
    let id = event.id;
    let uploadfield_id = id.replace("-clear", "");
    let uploadfield = document.getElementById(uploadfield_id);
    if(uploadfield.value){
        uploadfield.value = null;
    }
    // set display:none to clear link wrapper
    let clear_link_id = id.replace("-clear", "-clearlink-wrapper");
    let clear_link = document.getElementById(clear_link_id);
    if(clear_link){
        clear_link.style = "display:none";
    }
}

/**
 * Show the clear link only if a file was added to the input field
 * @param event
 */
function showClearLink(event){
    let id = event.target.id+"-clearlink-wrapper";
    let clear_link = document.getElementById(id);
    if(clear_link){
        if(event.target.files.length > 0) {
            clear_link.style = "display:block";
        } else {
            clear_link.style = "display:none";
        }
    }
}
