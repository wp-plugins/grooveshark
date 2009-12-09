// Player callback and helper functions
// gsDataStore initiated in grooveshark.js

/*
function gsErrorCallback(msg) {
    // Display error message here
}

function gsStatusCallback(msg) {
    // Status of the player
    var lastPlayed = jQuery('#gsDataStore').data('gsDataStore').lastPlayed;
    if (lastPlayed != false) {
        switch (msg)
        {
            case 'loading':
                lastPlayed.className = 'gsLoading';
                break;

            case 'playing':
                lastPlayed.className = 'gsPause';
                break;

            case 'paused':
                lastPlayed.className = 'gsPlay';
                break;

            case 'failed':
            default:
                lastPlayed.className = 'gsPlay';
                break;
        }
    }
}

function gsSongComplete(msg) {
    // Song is complete
    jQuery('#gsDataStore').data('gsDataStore').lastPlayed.className = 'gsPlay';
    jQuery('#gsDataStore').data('gsDataStore').lastPlayed = false;
}
*/

function toggleSong(currentPlayed, songID) {
    // Toggle the status for a song (play, pause, new song)
    var gsDataStore = jQuery('#gsDataStore').data('gsDataStore');
    var sessionID = document.getElementById('gsSessionID').value;
    var wpurl = document.getElementById('gsBlogUrl').value;
    var lastPlayed = gsDataStore.lastPlayed;
    if (typeof lastPlayed == 'boolean') {
        // initial song play
        gsDataStore.lastPlayed = currentPlayed;
        lastPlayed = currentPlayed;
    } else {
        if (lastPlayed.name != currentPlayed.name) {
            // new song play
            jQuery('#apContainer').empty();
            lastPlayed.className = 'gsPlay';
            currentPlayed.className = 'gsPause';
            gsDataStore.lastPlayed = currentPlayed;
            gsPlaySong(sessionID, songID, wpurl);
            return;
        }
    }
    if (lastPlayed.parentNode.parentNode.parentNode.parentNode.id != currentPlayed.parentNode.parentNode.parentNode.parentNode.id) {
        // same song play, different play button
        lastPlayed.className = 'gsPlay';
        currentPlayed.className = 'gsPause';
        gsDataStore.lastPlayed = currentPlayed;
        return;
    }
    if (currentPlayed.className.indexOf('gsPlay') != -1) {
        // Play the song
        currentPlayed.className = 'gsPause';
        gsPlaySong(sessionID, songID, wpurl);
    } else {
        if (currentPlayed.className.indexOf('gsPause') != -1) {
            // stop the song
            console.log(5);
            currentPlayed.className = 'gsPlay';
            jQuery('#apContainer').empty();
        }
    }
    return;
    /*
    var lastPlayed = jQuery('#gsDataStore').data('gsDataStore').lastPlayed;
    var player = jQuery('#gsDataStore').data('gsDataStore').player;
    if (lastPlayed == false) {
        // If this the first song played, or song played after previous song completed
        jQuery('#gsDataStore').data('gsDataStore').lastPlayed = currentPlayed; // Update lastPlayed button
        lastPlayed = currentPlayed;
        player.playSong(songID); // Send songID to player to play song
        return;
    } else {
        // A song is already playing
        if (lastPlayed.name != currentPlayed.name) {
            // A new song was selected
            player.stopStream(); // Stop the current stream
            lastPlayed.className = 'gsPlay'; // Make the button for the last song play button
            jQuery('#gsDataStore').data('gsDataStore').lastPlayed = currentPlayed; // Update lastPlayed button
            lastPlayed = currentPlayed;
            player.playSong('songID'); // Send songID to player to play song
        }
    }
    // Checks if the same song was chosen but at a different button
    if (lastPlayed.parentNode.parentNode.parentNode.parentNode.id != currentPlayed.parentNode.parentNode.parentNode.parentNode.id) {
        lastPlayed.className = 'gsPlay'; // Make the button for the last song play button
        currentPlayed.className = 'gsPause'; // Make the button for the current song pause button
        player.data('gsDataStore').lastPlayed = currentPlayed; // Update lastPlayed button
        lastPlayed = currentPlayed;
        return;
    }
    if (currentPlayed.className.indexOf('gsPlay') != -1) {
        // If the button was a play button for a currently paused song
        player.resumeStream(); // Resume paused stream
    } else {
        // If the button was a pause button for a currently playing song
        if (currentPlayed.className.indexOf('gsPause') != -1) {
            player.pauseStream();
        }
    }
    */
}

function gsPlaySong(sessionID, songID, wpurl) {
    jQuery.post(wpurl + "/wp-content/plugins/grooveshark/gsEmbed.php", {sessionID: sessionID, songID: songID}, function(returnedCode) {
        jQuery('#apContainer').html(returnedCode.embedCode);
    }, "json");
}
//Intermediate between the user and player.js. Gets the song ID for the song, and the session ID for the stream key.
//Other function really should be called, but this kept to minimize changes
function toggleStatus(obj) {
    if (obj.name) {
        toggleSong(obj, obj.name);
    }
    /*
    var gsObj = obj;
    sessionID = document.getElementById('gsSessionID').value;
    wpurl = document.getElementById('gsBlogUrl').value;
    //streamKey = 0;
    songID = obj.name;
    */
    /*jQuery(document).ready(function($) {
        jQuery.post(wpurl + "/wp-content/plugins/grooveshark/gsSearch.php", {sessionID: sessionID, songID: songID, call: 'getStreamKey'}, function(returnedStreamKey) {
            if (returnedStreamKey.indexOf('Code') != -1) {
                alert('Error ' + returnedStreamKey + '. Contact author for support.');
                return;
            }
            streamKey = returnedStreamKey;
            if (streamKey) {
                player.toggleStatus(obj, streamKey, wpurl);
            }
        });
    });*/
    /*
    if (songID) {
        jQuery('#gsDataStore').data('gsDataStore').player.toggleStatus(gsObj, songID, wpurl);
    }
    */
}

