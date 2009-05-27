/*
 * Grooveshark javascript player
 * interfaces with the JSPlayer.swf file to provide playback and controls of music
 *
 * @author Chanel Munezero <chanel.munezero@grooveshark.com>
 * @copyright Grooveshark 2009
 *
 * @Modified for the Grooveshark Wordpress Plugin by Roberto Sanchez <roberto.sanchez@escapemg.com>
 */

function jsPlayer(session) {
    session = session || '';
    var options;
    var thisWpUrl;
    if(!session) {
        // a grooveshark session key required to use jsPlayer
        return false;
    }
    var self = this;
    var swf;
    var eiAttempts = 0;
    var gsLastStatusButton = 0;

    this.options = {
        // flash player callbacks (must be strings)
        errorCallback: 'player.errorCallback',
        statusCallback: 'player.playerStatus',
        songCompleteCallback: 'player.playerSongComplete',
        // swfobject related options (not required)
        swfobjectReplaceId: 'jsPlayerReplace',
        swfobjectId: 'jsPlayerEmbed',
        swfobjectName: 'jsPlayerEmbed'
    };
    if(options) {
        for(var key in this.options) {
            if(options[key]) {
                this.options[key] = options[key];
            }
        }
    }
    
    this.init = function()
    {
        var hostname, playerUrl;
        var filename = 'JSPlayer.swf';
        hostname = "cowbell.grooveshark.com";
        playerUrl = "http://listen.grooveshark.com/" + filename;

        var vars = {hostname: hostname, session: session};
        var params = {allowScriptAccess: "always"};
        var attributes = {id:this.options.swfobjectId, name:this.options.swfobjectName}; // give an id to the flash object
        
        if(window.swfobject && swfobject.embedSWF) {
            // swfobject: used to embed flash objects
            // http://code.google.com/p/swfobject
            swfobject.embedSWF(playerUrl, this.options.swfobjectReplaceId, "1", "1", "9.0.0", null, vars, params, attributes);
        }
        setTimeout(self.getFlash, 500);
        
    }

    this.setErrorCallback = function(functionName)
    {
        if (swf && swf.setErrorCallback) {
            return swf.setErrorCallback(functionName);
        }
    }
    
    this.setStatusCallback = function(functionName)
    {
        if (swf && swf.setStatusCallback) {
            return swf.setStatusCallback(functionName);
        }
    }
    
    this.setSongCompleteCallback = function(functionName)
    {
        if (swf && swf.setSongCompleteCallback) {
            return swf.setSongCompleteCallback(functionName);
        }
    }

    // these must be string references or they will not be set, required by flash
    this.setCallbacks = function()
    {
        if(self.options.errorCallback) {
            self.setErrorCallback(self.options.errorCallback);
        }
        if(self.options.statusCallback) {
            self.setStatusCallback(self.options.statusCallback);
        }
        if(self.options.songCompleteCallback) {
            self.setSongCompleteCallback(self.options.songCompleteCallback);
        }
    }

    this.getPlayerVersion = function()
    {
        if (swf && swf.getPlayerVersion) {
            return swf.getPlayerVersion();
        }
    }

    this.pauseStream = function()
    {
        if (swf && swf.pauseStream) {
            return swf.pauseStream();
        }
    }
   
    this.resumeStream = function()
    {
        if (swf && swf.resumeStream) {
            return swf.resumeStream();
        }
    }
    
    this.stopStream = function()
    {
        if (swf && swf.stopStream) {
            return swf.stopStream();
        }
    }

    this.playSong = function(songID)
    {
        if (swf && swf.playSong) {
            return swf.playSong(songID);
        }
    }

    this.playerStatus = function(msg)
    {
        switch(msg) {
            case 'loading':
                gsLastStatusButton.className = "gsLoading";
                break;

            case 'playing':
                gsLastStatusButton.className = "gsPause";
                break;

            case 'paused':
                gsLastStatusButton.className = "gsPlay";
                break;

            case 'failed':
                gsLastStatusButton.className = "gsPlay";
                gsLastStatusButton = 0;

            default:
                break;
        }
    }

    this.errorCallback = function(msg)
    {
        console.log(msg);
    }

    this.playerSongComplete = function()
    {
        gsLastStatusButton.className = "gsPlay";
        gsLastStatusButton = 0;
    }

//NOTE: the toggleStatus function was added afterwards specifically for the plugin, but shows how other javascripts can interface with player.js
//This function mainly handles user input. Other functions handle interfacing with the swf
    this.toggleStatus = function(gsStatusButton, songID, wpurl)
    {
        //If this is the first time the play button was pressed in this instance or if the previous song is complete
        if (gsLastStatusButton == 0 && gsLastStatusButton.className == undefined) {
            //loads the current "button" into last 
            gsLastStatusButton = gsStatusButton;
            //loads and plays the stream
            this.playSong(songID);
            //now playing, loads a pause icon into the button
            return;
        } else {
            //If a new stream is being played (name property stores songID)
            if (gsLastStatusButton.name != gsStatusButton.name) {
                //Stop the current stream
                this.stopStream();
                //Makes the last stream's button have a play icon
                gsLastStatusButton.className = "gsPlay";
                //loads the current button into last
                gsLastStatusButton = gsStatusButton;
                //loads a loading icon into the button
                //loads and plays the stream
                this.playSong(songID);
                return;
            }
        }
        //This condition checks for instances when a song that is currently playing is added to the playlist
        //and the user chooses to play the same song. The song in the search list is loaded with a play icon
        //and the song in the playlist is loaded with a pause icon.
        if (gsLastStatusButton.parentNode.parentNode.parentNode.parentNode.id != gsStatusButton.parentNode.parentNode.parentNode.parentNode.id) {
            gsLastStatusButton.className = "gsPlay";
            gsStatusButton.className = "gsPause";
            gsLastStatusButton = gsStatusButton;
            return;
        }
        //If the button pressed was a play button after pausing the same song
        if (gsStatusButton.className.indexOf("gsPlay") != -1) {
            //resume the paused stream
            this.resumeStream();
        } else {
            //If the button pressed was a pause button for a currently playing song
            if (gsStatusButton.className.indexOf("gsPause") != -1) {
                //pause the stream
                this.pauseStream();
            }
        }
    }
    
    this.getFlash = function()
    {
        if (eiAttempts < 10) {
            try {
                flash = null;
                if (window.jsPlayerEmbed) {
                    flash = window.jsPlayerEmbed;
                } else {
                    if (document.jsPlayerEmbed) {
                        flash = document.jsPlayerEmbed;
                    }
                }
                flash = flash || document.getElementBy('jsPlayerEmbed');
            } catch(e) {
                eiAttempts++;
                setTimeout(self.getFlash, 500);
            }
            if (flash != null) {
                swf = flash;
                if(self.setCallbacks) {
                    setTimeout(self.setCallbacks, 1000);
                }
            } else {
                eiAttempts++;
                setTimeout(self.getFlash, 500);
            }
        } else {
        }
    }
    
    this.init();
    return this;
}
