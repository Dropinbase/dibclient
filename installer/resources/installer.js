
function initPage() {
    var scriptFields = ['owner', 'group', 'webUser', 'composerFolder'];
    var showFieldCard = false;

    for (var i = 0; i < scriptFields.length; i++) {
        var v = document.getElementById(scriptFields[i]).value;
        var el = document.getElementById(scriptFields[i] + 'Div');
        el.style.display = (!!!v) ? 'none' : 'block';
        if(!!v) showFieldCard = true;
    }

    if(!!!showFieldCard) {
        var el = document.getElementById('scriptFields');
        el.style.display = 'none';
    }
}

function doAction(action) {
    // fetch html
    var url = '/' + action;

    if(action == 'saveTestDb') {
        var params = JSON.stringify(
            {
                'host' : document.getElementById('host').value,
                'port' : document.getElementById('port').value,
                'database' : document.getElementById('database').value,
                'username' : document.getElementById('un').value,
                'password' : document.getElementById('pw').value
            }
        );
    } else if(action == 'configureDib') {
        var params = JSON.stringify(
            {
                'composerFolder' : document.getElementById('composerFolder').value,
                'owner' : document.getElementById('owner').value,
                'group' : document.getElementById('group').value,
                'webUser' : document.getElementById('webUser').value,
            }
        );
    } else if(action == 'signIn') {
        var params = JSON.stringify(
            {
                'email' : document.getElementById('email').value,
                'password' : document.getElementById('password').value,
            }
        );
    } else
        var params = {};

    var el = document.getElementById(action + 'Msg');

    if(action === 'saveTestDb')
        el.innerHTML = '<h3>Please wait while the Dropinbase database is being created... </h3>';
    else 
        el.innerHTML = '';

    ajaxPost(url, params, function(data) {

        if(!!data && !!data.success) {
           
            var msg = '<h3>All good!</h3>';

            if(action == 'updateIndex') {
                var stateObj = {};
               
                window.history.replaceState(stateObj, "Dropinbase", "/login");

                msg = '<h2 style="color: #05a6ed">Ready to go!</h2> <b style="color: rgb(2, 78, 144)">Refresh your browser, and provide the credentials above.</b>';

            } else
                msg += 'Please proceed to the next step';
            
            el.innerHTML = msg;

        } else {

            if(action == 'testPhp') {

                if (!!data.messages) {
                    var msg = {};
                    for (var index in data.messages) {
                        var name = data.messages[index]['name'];
                        var required = (data.messages[index]['ready'] == true) ? 'Yes' : 'No';
                        var str = '<tr><td>' + name + '</td><td>' + data.messages[index]['notes'] + '</td><td style="text-align: center; vertical-align: middle;">' + required  + '</td></tr>';

                        if(!!!msg[name])
                            msg[name] = str;
                        else
                            msg[name] += str;
                    }

                    str = '<br><table class="dibTable">';
                    str += '<tr><th >Extension Type</th><th>Missing Extensions / Notes</th><th>Showstopper?</th></tr>';
                    
                    for(index in msg) {
                        str += msg[index];
                    }

                    str += '</table>';

                    el.innerHTML = str;

                } else
                    el.innerHTML = '<h3>Could not fetch info. Check the PHP error log and your server connection.</h3>';
            } else {
                if (!!data.messages) {
                    if(!!data.messages[0])
                        var str = data.messages[0].notes;
                    else
                        var str = data.messages.notes
                    
                    el.innerHTML = str;
                }
            }

        }
    });

}

var maxI = 0;
var currentPage = 1;
var maxPage = 7;

function openTab(pos) {

    var iagree = document.getElementById('iagree');
    if (!!!iagree.checked) {
        alert('First please agree to the terms and conditions stated before continuing');
        return;
    }
        

    if(pos === 'next')
        currentPage = currentPage + 1;
    else
        currentPage = pos;

    if (currentPage > maxPage) currentPage = maxPage;

    var divNext = document.getElementById('divNext');

    divNext.style.display = (currentPage === maxPage) ? 'none' : 'flex';

    window.event.stopPropagation();
    if(maxI < currentPage) maxI = currentPage;

    var i, tabcontent, tablinks;

    tabcontent = document.getElementsByClassName("rightInnerBlock");
    tablinks = document.getElementsByClassName("title");

    for (i = 0; i < tabcontent.length; i++) {
        if(i + 1 == currentPage) {
            tabcontent[i].style.display = "flex";
            tablinks[i].className = 'title active'
            tablinks[i].childNodes[1].src = "/files/dropins/dibAdmin/images/icons/busyTick.png"
        } else {
            tabcontent[i].style.display = "none";
            tablinks[i].className = 'title'
            if(i < maxI)
                tablinks[i].childNodes[1].src = "/files/dropins/dibAdmin/images/icons/doneTick.png"
            else
                tablinks[i].childNodes[1].src = "/files/dropins/dibAdmin/images/icons/pendingTick.png"
        }
    }

    var el = document.getElementById("pg" + i);
    if(!!el && el.name == 'installDib') {
        // check if framework is installed already
        doAction('checkDibInstall');
    }
}

function ajaxPost(url, params, callback) {
   
    if (!!params==false) params="{}";

    let response = fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json;charset=utf-8',
            'X-Requested-With': 'XMLHttpRequest'
        },
        Referrer: 'no-referrer',
        body: params

    }).then((response) => {
        if (response.status == 401) {
            showMessage("Unauthorized - reloading the browser...");
            parent.location.reload(); // Handle fact that user has been logged out.
        }
        return response.text()

    }).then((result) => {
        try {
            if(!!result) {
                var parsedJson = JSON.parse(result);
                if(parsedJson && typeof parsedJson === 'object') {
                    callback(parsedJson);
                    return null;
                } else {
                    console.error('Invalid JSON response: ' + result);
                    return false;
                }
            } else
                return null;
        }
        catch (e) {
            return false;
        }

    });
    
}

let intervalId;

function startDownload() {
    const downloadStatus = document.getElementById("downloadStatus");
    downloadStatus.innerHTML = "Starting download...";

    // Start polling for progress every 1 seconds
    intervalId = setInterval(checkProgress, 1000);

    // Start the download via AJAX
    fetch('downloadDib')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!!data.messages.ready) {
                downloadStatus.innerHTML =  data.messages.notes;
            } else {
                downloadStatus.innerHTML = "Download failed to start.<br><br>" + data.messages.notes;
            }
        })
        .catch(error => {
            downloadStatus.innerHTML = "Error starting download: " + error;
        });
}

function checkProgress() {
    const downloadStatus = document.getElementById("downloadStatus");

    fetch('downloadDibProgress')
        .then(response => response.json())
        .then(data => {
            
            if (!!data.messages.ready || (!!data.messages.notes && data.messages.notes == 'stop queue')) {
                clearInterval(intervalId); // Stop checking progress when complete
                
            } else if (!!data.messages[0] && data.messages[0].name == 'Fatal PHP Error') {
                clearInterval(intervalId);
                data.messages.notes =  data.messages[0].notes;
            }
            
            if(!!data.messages.notes && data.messages.notes != 'stop queue')
                downloadStatus.innerHTML = data.messages.notes;
            
        })
        .catch(error => {
         //   clearInterval(intervalId);
            downloadStatus.innerHTML = "Error checking progress: " + error;
        });
}