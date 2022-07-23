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
    if (document.readyState == "complete") {
        var el = document.getElementById('timecounter');
        if (el) {
            var timeleft = parseInt(document.getElementById('minTime').getAttribute('data-time'));
            var timetext = document.getElementById('minTime').getAttribute('data-unit');
            var timetext = timetext.split(';');
            console.log(timetext);
            var singular = timetext[0];
            var plural = timetext[1];
            var downloadTimer = setInterval(function() {
                if (timeleft <= 0) {
                    clearInterval(downloadTimer);
                    el.remove();
                }
                var text = timetext[0];
                if(timeleft <= 1){
                  text = timetext[1];
                }
                el.innerText = timeleft + ' ' + text + '.';
                timeleft -= 1;
            }, 1000);
        }
    }
}

/*
Show or hide the password in the password field by checking/unchecking the show/hide checkbox below the input field
*/

let togglePasswords = document.getElementsByClassName('pwtoggle');
if(togglePasswords.length > 0){
  for (let i = 0; i < togglePasswords.length; i++) {
    console.log(togglePasswords[i].parentNode.previousElementSibling);
    if(togglePasswords[i].parentNode.previousElementSibling.type === 'password'){
      togglePasswords[i].addEventListener('click', function (event) {
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
