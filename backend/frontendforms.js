/*
Javascript file for the backend

Created by Jürgen K.
https://github.com/juergenweb 
File name: frontendforms.js
Created: 17.07.2022 
*/


/*
 * Add or remove the given IP from the blacklist
 * @param event
 * @param type
 */
function addIP(event, type) {
    var ip = event.value;
    var target = document.getElementById('Inputfield_input_preventIPs')
    var currentIPs = target.value;
    if(type == 0){
        //remove the ip
        target.value = currentIPs.replace("\n" + ip, '');
    } else {
        //add the ip
        target.value = currentIPs + "\n" + ip;
    }
}