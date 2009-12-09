/*
 * Main Document Ready Function
 */

jQuery(function() {
    // Resize the search button for small box used in comments
    if (jQuery('#gsSearchButton').hasClass('gsSmallButton')) {
        jQuery('#gsSearchButton').width(60);
    }
    var gsDataStore = jQuery('#gsDataStore');
    gsDataStore.data('gsDataStore', {player: '', savedHeight: false, isVersion26: false, lastPlayed: false});
    // Add an onfocus event to the song search input to remove grayed-out text
    jQuery('#gs-query').focus(function() {
        if (jQuery(this).hasClass('empty')) {
            jQuery(this).removeClass('empty').val('');
        }
    });
    // The rest of this function does not need to be setup for the small box
    if (jQuery('#isSmallBox', context).val() == 1) {
        return;
    }
    // Add an onclick event to the visual tab in content edit area to ensure that the music placement button is added
    jQuery('#edButtonPreview').click(function() {
        if (jQuery('#content_grooveshark').length == 0) {
            jQuery(jQuery('#content_toolbar1')[0].rows[0]).append("<td><a href='javascript:;' title='Place your music' class='mceButton mceButtonEnabled' id='content_grooveshark' onclick='insertGroovesharkTag(2)'><span class='gsNote'></span></a></td>");
        }
    });
    // Adds the music placement button to the visual buttons row if that row exists in the dom (if it doesn't yet exist the onclick event above accomplishes the same task when the user needs it)
    if (typeof(jQuery('#content_toolbar1')[0]) != 'undefined') {
        jQuery(jQuery('#content_toolbar1')[0].rows[0]).append("<td><a href='javascript:;' title='Place your music' class='mceButton mceButtonEnabled' id='content_grooveshark' onclick='insertGroovesharkTag(2)'><span class='gsNote'></span></a></td>");
    }
    // Adds the music placement text button to the text buttons
    jQuery('#ed_toolbar').append("<input type='button' id='ed_grooveshark' class='ed_button' value='music' title='Place your music' onclick='insertGroovesharkTag(1);'/>");
    // Set up the jQuery context to minimize the search area for the jQuery() function
    var context = jQuery('#groovesharkdiv');
    // Change the colors preview based on the selected colors determined server-side
    changeColor(jQuery('#colors-select', context)[0]);
    // Set up data store for the plugin
    var value = jQuery('#wpVersion', context).val();
    if (value != undefined) {
        if ((value.indexOf('2.6') != -1) || (value.indexOf('2.5') != -1)) {
            gsDataStore.data('gsDataStore').isVersion26 = true;
        }
    }
    if (typeof(jQuery('#gsSessionID').val()) != 'undefined') {
        // Normally used to set up the player
        // gsDataStore.data('gsDataStore').player = new jsPlayer({errorCallback: 'gsErrorCallback', statusCallback: 'gsStatusCallback', songCompleteCallback: 'gsSongComplete'});
    }
    var playlistID = 0;
    var heightRegEx = /height\=\"(\d+)\"/;
    var playlistRegEx = /(\<input.+id\=['"]gsPlaylistID['"].+\/>)/;
    // determines which content box is used and set that as the reference for search of heigh and playlistID
    var content = (typeof(jQuery('#content').val()) != 'undefined') ? jQuery('#content') : ((typeof(jQuery('#content_ifr').val()) != 'undefined') ? jQuery('#content_ifr') : '');
    if (content != '') {
        var matchedPlaylist = 0;
        if (content.val().match(playlistRegEx)) {
            // Found a playlist, look for the playlistID and save it
            matchedPlaylist = 1;
            var playlistMatch = playlistRegEx.exec();
            playlistID = playlistMatch[1];
            var playlistIDRegEx = /value\=['"](\d+)["']/;
            playlistID.match(playlistIDRegEx);
            playlistMatch =  playlistIDRegEx.exec();
            playlistID = playlistMatch[1];
        }
        if ((matchedPlaylist == 1) && (content.val().match(heightRegEx))) {
            // Found tha playlist and found the previous widget height
            var heightMatch = heightRegEx.exec();
            var playlistHeight = heightMatch[1];
            jQuery('#widgetHeight', content).val(playlistHeight);
            gsDataStore.data('gsDataStore').savedHeight = true;
        }
    }
    // With the given playlistID, update the selected songs list with all songs in the widget currently used on the post
    // Will need an alternate way of doing this. The intent is to do away with gsPlaylistSongs calls and have the
    // playlist information ready as soon as the user clicks on a playlist button.
    /*
    playlistID = parseInt(playlistID);
    if (jQuery('#wpVersion', context).length != 0) {
        addToSelectedPlaylist(playlistID);
    }
    */
});

if (jQuery('a.widget-action').length) {
    jQuery('a.widget-action').live('click', function() {
            // bind event to widget-action arrow that opens/closes sidebar widget options
            var widget = jQuery(this).closest('div.widget');
            if ((widget.find('#groovesharkSidebarOptionsBox').length) || (widget.find('#groovesharkSidebarRssBox').length)) {
                // Only modify box when the box is for the Grooveshark Sidebar
                var widgetInside = widget.children('.widget-inside');
                if (widgetInside.is(":hidden")) {
                    // If the box is opening, modify it's width
                    widget.css({width: '400px', marginLeft: '-135px'});
                }
            }
    });
}

//Inserts the tag which will be replaced by embed code.
function insertGroovesharkTag(identifier) {
    if (typeof(switchEditors) != 'undefined') {
        if (switchEditors.go() != null && identifier == 2) {
            switchEditors.go('content','html');
        }
    }
    if (document.getElementById('gsTagStatus') != null && document.getElementById('gsTagStatus').value == 0) {
        if (document.getElementById('content') != null) {
            var edCanvas = document.getElementById('content');
        }
        //IE support
        if (document.selection) {
            edCanvas.focus();
            sel = document.selection.createRange();
            if (sel.text.length > 0) {
                sel.text = sel.text + '[[grooveshark]]';
            } else {
                sel.text = '[[grooveshark]]';
            }
            edCanvas.focus();
        } else if (edCanvas.selectionStart || edCanvas.selectionStart == '0') {
            //MOZILLA/NETSCAPE support
            var startPos = edCanvas.selectionStart;
            var endPos = edCanvas.selectionEnd;
            var cursorPos = endPos;
            var scrollTop = edCanvas.scrollTop;
            if (startPos != endPos) {
                edCanvas.value = edCanvas.value.substring(0, endPos) + '[[grooveshark]]' + edCanvas.value.substring(endPos, edCanvas.value.length);
                cursorPos += 15;
            } else {
                edCanvas.value = edCanvas.value.substring(0, startPos) + '[[grooveshark]]' + edCanvas.value.substring(endPos, edCanvas.value.length);
                cursorPos = startPos + 15;
            }
            edCanvas.focus();
            edCanvas.selectionStart = cursorPos;
            edCanvas.selectionEnd = cursorPos;
            edCanvas.scrollTop = scrollTop;
        } else {
            edCanvas.value += '[[grooveshark]]';
            edCanvas.focus();
        }
        document.getElementById('gsTagStatus').value = 1;
        document.getElementById('ed_grooveshark').title = 'One tag at a time';
        document.getElementById('ed_grooveshark').onclick = function() {};
        document.getElementById('content_grooveshark').title = 'One tag at a time';
        document.getElementById('content_grooveshark').onclick = function() {};
    }
    if (typeof(switchEditors) != 'undefined') {
        if (switchEditors.go() != null && identifier == 2) {
            switchEditors.go('content','tinymce');
        }
    }
}

/*
 * Sidebar setup functions
 */

// Used as the onclick function for playlist radio input elements in the sidebar widget options panel
function groovesharkUpdateChoice(obj) {
   var height = 176; 
   height += (22 * obj.className); 
   if (height > 1000) { 
       height = 1000;
   } 
   // The context is needed for jQuery to actually change the value of sidebarWidgetHeight; also, document.getElementById.value won't change it either
   jQuery('#gsSidebarWidgetHeight', jQuery(obj).parent().parent().parent().parent()).val(height);
}

// Used as the onchange functions for the width/height text input elements in the sidebar widget options panel
function changeSidebarHeight(obj) {
    document.getElementById('hiddenSidebarWidgetHeight').value = obj.value;
}

function changeSidebarWidth(obj) {
    document.getElementById('hiddenSidebarWidgetWidth').value = obj.value;
}

/*
 * Main Grooveshark Add Music Box functions
 */

//Handles the user's searches.
function gsSearch(obj) {
    obj.value = '...';
    obj.disabled = true;
    var aquery = document.getElementById("gs-query").value;
    var sessionID = document.getElementById('gsSessionID').value;
    var limit = document.getElementById('gsLimit').value;
    var wpurl = document.getElementById('gsBlogUrl').value;
    var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
    var isSmallBox = document.getElementById('isSmallBox').value;
    var random = Math.floor(Math.random()*100);
    if (aquery != '') {
        // load the table containing the search results
        jQuery('#search-results-wrapper').load(wpurl + "/wp-content/plugins/grooveshark/gsSearch.php?" + random, {query: aquery, sessionID: sessionID, limit: limit, isVersion26: isVersion26, isSmallBox: isSmallBox}, function(){
            if (jQuery('#search-results-wrapper').children().length > 0) {
                // Header for the search result table
                jQuery('#queryResult').html('Search results for "' + aquery + '":');
                // Revert buttons to inactive state
                obj.value = 'Search';
                obj.disabled = false;
                // Show results
                jQuery('#search-results-container').add('#search-results-wrapper').show();
            } else {
                jQuery('#queryResult').html('There was an error with your search. If this error persists, please contact the author.').show();
            }
        });
    }
}

//Handles selecting a song for addition to the post.
function addToSelected(songInfo) {
    var temp = [];
    temp = songInfo.split("::");
    songNameComplete = temp[0];
    songID = temp[1];
    if (songNameComplete && songID) {
        // Prepare the table with all selected songs
        selectedTable = jQuery('#selected-songs-table');
        // Alternating table styles
        var className = isVersion26 ? 'gsTr26' : 'gsTr27';
        var tableLength = selectedTable[0].rows.length;
        var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
        if (tableLength % 2) {
            className = 'gsTr1';
        }
        // Different style for wordpress version 2.6 and under
        var preClass = isVersion26 ? 'gsTr26' : 'gsTr27';
        // Prepare the row with the selected song
        var rowContent = "<tr class='"+className+"'><td class='gsTableButton'>" + ((document.getElementById('isSmallBox').value == 1) ? ("<a class='gsRemove' title='Remove This Song' style='cursor:pointer;' onclick='removeFromSelected(this);'></a></td>") : ("<a title='Play This Song' class='gsPlay' name='"+songID+"' style='cursor: pointer;' onclick='toggleStatus(this);'></a></td>")) + "<td><pre title='Drag and drop this row to change the order of your songs' class='"+preClass+"'>"+songNameComplete+"</pre><input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='"+songID+"'/></td>" + ((document.getElementById('isSmallBox').value == 1) ? '' : "<td class='gsTableButton'><a title='Remove This Song' class='gsRemove' style='cursor: pointer; float: right;' onclick='removeFromSelected(this);'></a></td>") + "</tr>";
        selectedTable.append(rowContent);
        // Make the row draggable
        TableDnD(selectedTable[0]);
        // Auto-adjust the widget height for the new number of songs, unless height is predetermined by user
        widgetHeight = jQuery('#widgetHeight');
        if ((widgetHeight.val() < 1000) && (jQuery('#gsDataStore').data('gsDataStore').savedHeight == false)) {
            widgetHeight.val(parseInt(widgetHeight.val()) + 22);
        }
    }
    updateCount();
}

//Handles selecting a playlist for addition to the post.
function addToSelectedPlaylist(obj) {
    // prepare playlist info
    var playlistSongs = obj.innerHTML;
    var playlistID = obj.name;
    var playlistSongs = jQuery.parseJSON(playlistSongs);
    var selectedTable = jQuery('#selected-songs-table');
    if ((typeof playlistSongs == 'undefined') || (playlistSongs.length == 0)) {
        // No songs available, display error message
        selectedTable.append('<tr class="temporaryError"><td></td><td><pre>An unexpected error occurred while loading your playlist. Contact the author for support</pre></td><td></td></tr>');
        setTimeout(function(){jQuery('.temporaryError').fadeOut('slow', function(){jQuery(this).remove();});}, 3000);
        return;
    }
    // prepare needed variables
    var count = selectedTable[0].rows.length - 1;
    var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
    // Different string lengths allowed for wordpress versions 2.6 and under
    var stringLimit = isVersion26 ? 71 : 78;
    // Different alt row styling for wordpress versions 2.6 and under
    var altClass = isVersion26 ? 'gsTr26' : 'gsTr27';
    // Different pre styling for wordpress versions 2.6 and under
    var preClass = isVersion26 ? 'gsPreSingle26' : 'gsPreSingle27';
    // Prepare widgetHeight for auto-adjust
    var widgetHeight = parseInt(jQuery('#widgetHeight').val());
    // Prepare the new song content
    var newSongContent = '';
    var savedHeight = jQuery('#gsDataStore').data('gsDataStore').savedHeight;
    for (var i = 0; i < playlistSongs.length; i++) {
        // Get all relevant song information
        var songName = playlistSongs[i].songName;
        var artistName = playlistSongs[i].artistName;
        var songNameComplete = songName + " by " + artistName;
        if (songNameComplete.length > stringLimit) {
            songNameComplete = songName.substring(0,stringLimit - 3 - artistName.length) + "&hellip;" + " by " + artistName;
        }
        var songID = playlistSongs[i].songID;
        if (songNameComplete && songID) {
            // If song information is returned
            count++;
            var newSongRow = "<tr class='" + (count % 2 ? altClass : 'gsTr1') + " newRow'><td class='gsTableButton'><a title='Play this song' class='gsPlay' name='"+songID+"' style='cursor: pointer;' onclick='toggleStatus(this);'></a></td><td><pre title='Drag and drop this row to change the order of your songs' class='"+preClass+"'>"+songNameComplete+"</pre><input type='hidden' name='songsInfoArray[]' class='songsInfoArrayClass' value='"+songID+"'/></td><td class='gsTableButton'><a title='Remove This Song' class='gsRemove' style='cursor: pointer; float: right;' onclick='removeFromSelected(this);'></a></td></tr>";
            // Make the new row draggable
            newSongContent += newSongRow;
            if ((widgetHeight < 1000) && (savedHeight == false)) {
                widgetHeight += 22;
            }
        }
    }
    selectedTable.append(newSongContent);
    // Make the new rows draggable
        TableDnD(jQuery('#selected-songs-table')[0]);
        updateCount();
    jQuery('#widgetHeight').val(widgetHeight);
}

// Handles showing all playlist songs before adding to post
function showPlaylistSongs(obj) {
    var playlistSongs = obj.innerHTML;
    var playlistID = obj.name;
    var playlistSongs = jQuery.parseJSON(playlistSongs);
    if ((typeof playlistSongs == 'undefined') || (playlistSongs.length == 0)) {
        jQuery(obj).parent().parent().after('<tr class="revealed-' + playlistID + ' playlistRevealedSong"><td></td><td></td><td><pre>An unexpected error occurred while loading your playlist. Contact the author for support.</pre></td></tr>');
        jQuery(obj).attr('class', 'gsHide');
        jQuery(obj).attr('title', 'Hide All Songs In This Playlist');
        jQuery(obj).removeAttr('onclick');
        jQuery(obj).unbind('click');
        jQuery(obj).click(function() {hidePlaylistSongs(obj.name, obj);});
        setTimeout(function() {jQuery(obj).click(); jQuery(obj).click(function() {showPlaylistSongs(obj);});}, 5000);
        return;
    }
    // Set up needed variables
    var appendRow = jQuery(obj).parent().parent();
    var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
    // Different string lengths allowed for wordpress versions 2.6 and under
    var stringLimit = isVersion26 ? 71 : 78;
    // Different alt row styling for wordpress versions 2.6 and under
    var altClass = isVersion26 ? 'gsTr26' : 'gsTr27';
    // Different pre styling for wordpress versions 2.6 and under
    var preClass = isVersion26 ? 'gsPreSingle26' : 'gsPreSingle27';
    // Prepare the new song content
    var newSongContent = '';
    var count = 0;
    for (var i = 0; i < playlistSongs.length; i++) {
        // Get all relevant song information
        var songName = playlistSongs[i].songName;
        var artistName = playlistSongs[i].artistName;
        var songNameComplete = songName + " by " + artistName;
        if (songNameComplete.length > stringLimit) {
            songNameComplete = songName.substring(0,stringLimit - 3 - artistName.length) + "&hellip;" + " by " + artistName;
        }
        var songID = playlistSongs[i].songID;
        if (songNameComplete && songID) {
            // If song information is returned
            var newSongRow = "<tr class='" + (count % 2 ? altClass : 'gsTr1') + " revealed-" + playlistID + " playlistRevealedSong'><td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd' name='" + songNameComplete + "::" + songID + "' onclick='addToSelected(this.name);' style='cursor:pointer'></a></td><td class='gsTableButton'><a title='Play This Song' class='gsPlay' name='" + songID + "' onclick='toggleStatus(this);' style='cursor:pointer'></a></td><td><pre class='" + preClass + "'>" + songNameComplete + "</pre></td></tr>";
            // Make the new row draggable
            newSongContent += newSongRow;
            count++;
        }
    }
    if (newSongContent.length > 0) {
        appendRow.after(newSongContent);
        jQuery(obj).attr('class', 'gsHide');
        jQuery(obj).attr('title', 'Hide All Songs In This Playlist');
        jQuery(obj).removeAttr('onclick');
        jQuery(obj).unbind('click');
        jQuery(obj).click(function() {hidePlaylistSongs(obj.name, obj);});
    }
}

function hidePlaylistSongs(playlistID, obj) {
    jQuery(obj).parent().parent().parent().find('.revealed-'+playlistID).fadeOut('slow', function() {jQuery(this).remove();});
    jQuery(obj).attr('class', 'gsShow');
    jQuery(obj).attr('title', 'Show All Songs In This Playlist');
    jQuery(obj).removeAttr('onclick');
    jQuery(obj).unbind('click');
    jQuery(obj).click(function() {showPlaylistSongs(obj.name, obj);});
}

//Handles unselecting a song for addition.
function removeFromSelected(element) {
    // Just remove the song's row, adjust widget height as necessary, and update selected song count
    jQuery(element).parent().parent().remove();
    if (document.getElementById('widgetHeight') != null) {
        if (document.getElementById('widgetHeight').value > 176 && jQuery('#gsDataStore').data('gsDataStore').savedHeight == false) {
            document.getElementById('widgetHeight').value = parseInt(document.getElementById('widgetHeight').value) - 22;
        }
    }
    jQuery('#selected-songs-table tr:odd').attr('class', 'gsTr1');
    jQuery('#selected-songs-table tr:even').attr('class', jQuery('#gsDataStore').data('gsDataStore').isVersion26 ? 'gsTr26' : 'gsTr27');
    updateCount();
}

//Clears all songs that are selected for addition.
function clearSelected() {
    jQuery('#selected-songs-table').empty();
    document.getElementById("selectedCount").innerHTML = "Selected Songs (0):"
    if (((jQuery('#gsDataStore').data('gsDataStore').savedHeight == false) && (document.getElementById('widgetHeight') != null))) {
        document.getElementById('widgetHeight').value = 176;
    }
}

//Only needed because the addToSelectedPlaylist function for some reason does not update the selected count on its own.
function updateCount() {
    var selectedCount = document.getElementById("selectedCount");
    selectedCountValue = jQuery('#selected-songs-table tr').length;
    selectedCount.innerHTML = "Selected Songs (" + selectedCountValue + "):";
    if (selectedCountValue > 0) {
        document.getElementById("selected-songs-table").className = 'gsSelectedPopulated';
    } else {
        document.getElementById("selected-songs-table").className = 'gsSelectedEmpty';
    }
}

//Change the example display phrase to reflect what the user typed in.
function changeExample(obj) {
    document.getElementById('displayPhraseExample').innerHTML = 'Example: "' + obj.value + ': song by artist"';
}

//Change the example playlist name to reflect what the user typed in.
function changeExamplePlaylist(obj) {
    document.getElementById('displayPhrasePlaylistExample').innerHTML = 'Example: "Grooveshark: ' + obj.value + '"';
}


//Toggles whether appearance is shown or hidden (presumably once a user sets the widget/link appearance, they would use that appearance for a while)
function gsToggleAppearance(){
    if(document.getElementById('gsAppearance').className == 'gsAppearanceHidden'){
      document.getElementById('gsAppearance').className = 'gsAppearanceShown';
      document.getElementById('jsLink').innerHTML = "&rarr; Appearance";
    }else{
      document.getElementById('gsAppearance').className = 'gsAppearanceHidden';
      document.getElementById('jsLink').innerHTML = "&darr; Appearance";
    }
}


// Handles appending a widget/link to a user's comment
function gsAppendToComment(obj) {
    var songsArray = jQuery("input.songsInfoArrayClass");
    if (songsArray.length > 0) {
        obj.value = 'Saving...';
        obj.disabled = true;
        var songString = songsArray[0].value;
        for (var i = 1; i < songsArray.length; i++) {
            songString += ":" + songsArray[i].value;
        }
        var widgetHeight = document.getElementById('widgetHeight').value;
        widgetHeight = (widgetHeight < 1000) ? widgetHeight : 1000;
        jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsComment.php', {songString: songString, widgetHeight: widgetHeight, sessionID: document.getElementById('gsSessionID').value}, function(returnedData) {
            if (document.getElementById('comment') != null) {
                document.getElementById('comment').value += returnedData;
            }
            obj.value = 'Save Music';
            obj.disabled = false;
        });
    }
}


//Handles appending a widget/link to the post content.
function gsAppendToContent(obj) {
    //songsArray = document.getElementsByName('songsInfoArray[]');
    var songsArray = jQuery("input.songsInfoArrayClass");
    if (songsArray.length > 0) {
        obj.value = 'Saving...';
        obj.disabled = true;
        var songString = songsArray[0].value;
        for (var i = 1; i < songsArray.length; i++) {
            songString += ":" + songsArray[i].value;
        }
        var displayOptions = document.getElementsByName("displayChoice");
        var displayOption = displayOptions[1].checked ? 1 : 0;
        var sidebarOptions = document.getElementsByName("sidebarChoice");
        var sidebarOption = sidebarOptions[0].checked ? 1 : 0;
        var dashboardOptions = document.getElementsByName("dashboardChoice");
        var dashboardOption = dashboardOptions[0].checked ? 1 : 0;
        var widgetWidth = document.getElementById('widgetWidth').value;
        var widgetHeight = document.getElementById('widgetHeight').value;
        if (widgetWidth < 150) {
            widgetWidth = 150;
        }
        if (widgetHeight < 150) {
            widgetHeight = 150;
        }
        if (widgetWidth > 1000) {
            widgetWidth = 1000;
        }
        if (widgetHeight > 1000) {
            widgetHeight = 1000;
        }
        document.getElementById('widgetWidth').value = widgetWidth;
        document.getElementById('widgetHeight').value = widgetHeight;
        var positionBeginning = document.getElementById('gsPosition').checked ? true : false;
        var colorScheme = document.getElementById('colors-select').value;
        var displayPhrase = document.getElementById('displayPhrase').value;
        var playlistName = document.getElementById('playlistsName').value;
        jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsPost.php', {songString: songString, displayOption: displayOption, sidebarOption: sidebarOption, dashboardOption: dashboardOption, widgetWidth: widgetWidth, widgetHeight: widgetHeight, colorsSelect: colorScheme, displayPhrase: displayPhrase, playlistsName: playlistName, sessionID: document.getElementById('gsSessionID').value}, function(returnedData) {
            if (!jQuery('#gsDataStore').data('gsDataStore').isVersion26) {
                if (typeof(switchEditors) != 'undefined') {
                    if (switchEditors.go() != null) {
                        switchEditors.go('content','html');
                    }
                }
            }
            if (document.getElementById('content') != null) {
                if (document.getElementById('gsTagStatus').value == 1) {
                    document.getElementById('content').value = gsReplaceTag(document.getElementById('content').value, returnedData);
                } else {
                    if (positionBeginning) {
                        document.getElementById('content').value = returnedData + document.getElementById('content').value;
                    } else {
                        document.getElementById('content').value += returnedData;
                    }
                }
                document.getElementById('autosaveMusic').value = 0;
            }
            if (document.getElementById('comment') != null) {
                document.getElementById('comment').value += returnedData;
            }
            if (document.getElementById('content_ifr') != null) {
                if (document.getElementById('content_ifr').contentDocument != null) {
                    contentIframe = document.getElementById('content_ifr').contentDocument;
                    if (contentIframe.getElementsByTagName('p')[0] != null) {
                        if (document.getElementById('gsTagStatus').value == 1) {
                            contentIframe.getElementsByTagName('p')[0].innerHTML = gsReplaceTag(contentIframe.getElementsByTagName('p')[0].innerHTML, returnedData);
                        } else {
                            if (positionBeginning) {
                                contentIframe.getElementsByTagName('p')[0].innerHTML = returnedData + contentIframe.getElementsByTagName('p')[0].innerHTML;
                            } else {
                                contentIframe.getElementsByTagName('p')[0].innerHTML += returnedData;
                            }
                        }
                        document.getElementById('autosaveMusic').value = 0;
                    }
                }
            }
            //mce_editor only from wordpress versions below 2.6
            if (document.getElementById('mce_editor_0') != null) {
                if (document.getElementById('gsTagStatus').value == 1) {
                    document.getElementById('mce_editor_0').contentDocument.body.innerHTML = gsReplaceTag(document.getElementById('mce_editor_0').contentDocument.body.innerHTML, returnedData);
                } else {
                    if (positionBeginning) {
                        document.getElementById('mce_editor_0').contentDocument.body.innerHTML = returnedData + document.getElementById('mce_editor_0').contentDocument.body.innerHTML;
                    } else {
                        document.getElementById('mce_editor_0').contentDocument.body.innerHTML += returnedData;
                    }
                }
                document.getElementById('autosaveMusic').value = 0;
            }
            document.getElementById('gsTagStatus').value = 0;
            if (document.getElementById('ed_grooveshark') != null) {
                document.getElementById('ed_grooveshark').disabled = false;
                document.getElementById('ed_grooveshark').title = 'Place your music';
            }
            if (document.getElementById('content_grooveshark') != null) {
                document.getElementById('content_grooveshark').onclick = function() {insertGroovesharkTag();};
                document.getElementById('content_grooveshark').title = 'Place your music';
            }
            obj.value = 'Save Music';
            obj.disabled = false;
            if (!jQuery('#gsDataStore').data('gsDataStore').isVersion26) {
                if (typeof(switchEditors) != 'undefined') {
                    if (switchEditors.go() != null) {
                        switchEditors.go('content','tinymce');
                    }
                }
            }
        });
    }
}

function gsReplaceTag(postContent, embedCode) {
    //takes post content, looks for a [[grooveshark]] tag, and replaces with embed code 
    if (postContent.indexOf('[[grooveshark]]') != -1) {
        postContentArray = postContent.split(/\[\[grooveshark\]\]/);
        postContent = postContentArray[0] + embedCode + postContentArray[1];
    }
    return postContent;
}

function changeAppearanceOption(appearanceOption) {
    switch (appearanceOption) {
        case 'link':
            jQuery('#gsDisplayLink').show();
            jQuery('#gsDisplayWidget').hide();
            break;
        case 'widget':
            jQuery('#gsDisplayWidget').show();
            jQuery('#gsDisplayLink').hide();
            break;
    }
}
//Toggles whether the user is shown the search, their favorites, or their playlists.
function gsToggleSongSelect(myRow){
    var isVersion26 = jQuery('#gsDataStore').data('gsDataStore').isVersion26;
    var tabClass = isVersion26 ? 'gsTabActive26' : 'gsTabActive27';
    var tabClass2 = isVersion26 ? 'gsTabInactive26' : 'gsTabInactive27';
    var isQueryEmpty = (jQuery('#queryResult').html() == '');
    switch (myRow) {
        case 'Search':
            jQuery('#playlists-option').attr('class', tabClass2);
            jQuery('#songs-playlists').hide();
            jQuery('#favorites-option').attr('class', tabClass2);
            jQuery('#songs-favorites').hide();
            jQuery('#search-option').attr('class', tabClass);
            jQuery('#songs-search').show();
            if (!isQueryEmpty) {
                jQuery('#search-results-container').show();
            }
            break;

        case 'Favorites':
            jQuery('#playlists-option').attr('class', tabClass2);
            jQuery('#songs-playlists').hide();
            jQuery('#search-option').attr('class', tabClass2);
            jQuery('#songs-search').hide();
            jQuery('#search-results-container').hide();
            jQuery('#favorites-option').attr('class', tabClass);
            jQuery('#songs-favorites').show();
            break;

        case 'Playlists':
            jQuery('#favorites-option').attr('class', tabClass2);
            jQuery('#songs-favorites').hide();
            jQuery('#search-option').attr('class', tabClass2);
            jQuery('#songs-search').hide();
            jQuery('#search-results-container').hide();
            jQuery('#playlists-option').attr('class', tabClass);
            jQuery('#songs-playlists').show();
            break;

        default:
            break;
    }
}

//Change the base, primary, and secondary colors to show the user what colors a given color scheme uses.
function changeColor(obj) {
    if(!obj) {
        return false;
    }
    curValue = obj.options[obj.selectedIndex].value;
    baseColor = document.getElementById('base-color');
    primaryColor = document.getElementById('primary-color');
    secondaryColor = document.getElementById('secondary-color');
    curValue = parseInt(curValue);
    var colorArray = getBackgroundRGB(curValue);
    baseColor.style.backgroundColor = colorArray[0];
    primaryColor.style.backgroundColor = colorArray[1];
    secondaryColor.style.backgroundColor = colorArray[2];
}

function changeSidebarColor(obj) {
    if (!obj) {
        return false;
    }
    var curValue = parseInt(obj.options[obj.selectedIndex].value);
    var context = jQuery(obj).parent().parent().parent();
    var baseColor = jQuery('#widget-base-color', context);
    var primaryColor = jQuery('#widget-primary-color', context);
    var secondaryColor = jQuery('#widget-secondary-color', context);
    var colorArray = getBackgroundRGB(curValue);
    baseColor[0].style.backgroundColor = colorArray[0];
    primaryColor[0].style.backgroundColor = colorArray[1];
    secondaryColor[0].style.backgroundColor = colorArray[2];
}

function getBackgroundRGB(colorSchemeID) {
    var colorArray = new Array();
    switch (colorSchemeID) {
        case 0:
            colorArray[0] = 'rgb(0,0,0)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(102,102,102)';
        break;

        case 1:
            colorArray[0] = 'rgb(204,162,12)';
            colorArray[1] = 'rgb(77,34,28)';
            colorArray[2] = 'rgb(204,124,12)';
        break;

        case 2:
            colorArray[0] = 'rgb(135,255,0)';
            colorArray[1] = 'rgb(0,136,255)';
            colorArray[2] = 'rgb(255,0,84)';
        break;

        case 3:
            colorArray[0] = 'rgb(255,237,144)';
            colorArray[1] = 'rgb(53,150,104)';
            colorArray[2] = 'rgb(168,212,111)';
        break;

        case 4:
            colorArray[0] = 'rgb(224,228,204)';
            colorArray[1] = 'rgb(243,134,48)';
            colorArray[2] = 'rgb(167,219,216)';
        break;

        case 5:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(55,125,159)';
            colorArray[2] = 'rgb(246,214,31)';
        break;

        case 6:
            colorArray[0] = 'rgb(69,5,18)';
            colorArray[1] = 'rgb(217,24,62)';
            colorArray[2] = 'rgb(138,7,33)';
        break;

        case 7:
            colorArray[0] = 'rgb(180,213,218)';
            colorArray[1] = 'rgb(129,59,69)';
            colorArray[2] = 'rgb(177,186,191)';
        break;

        case 8:
            colorArray[0] = 'rgb(232,218,94)';
            colorArray[1] = 'rgb(255,71,70)';
            colorArray[2] = 'rgb(255,255,255)';
        break;

        case 9:
            colorArray[0] = 'rgb(153,57,55)';
            colorArray[1] = 'rgb(90,163,160)';
            colorArray[2] = 'rgb(184,18,7)';
        break;

        case 10:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(0,150,9)';
            colorArray[2] = 'rgb(233,255,36)';
        break;

        case 11:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(122,122,122)';
            colorArray[2] = 'rgb(214,214,214)';
        break;

        case 12:
            colorArray[0] = 'rgb(255,255,255)';
            colorArray[1] = 'rgb(215,8,96)';
            colorArray[2] = 'rgb(154,154,154)';
        break;

        case 13:
            colorArray[0] = 'rgb(0,0,0)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(98,11,179)';
        break;

        case 14:
            colorArray[0] = 'rgb(75,49,32)';
            colorArray[1] = 'rgb(166,152,77)';
            colorArray[2] = 'rgb(113,102,39)';
        break;

        case 15:
            colorArray[0] = 'rgb(241,206,9)';
            colorArray[1] = 'rgb(0,0,0)';
            colorArray[2] = 'rgb(255,255,255)';
        break;

        case 16:
            colorArray[0] = 'rgb(255,189,189)';
            colorArray[1] = 'rgb(221,17,34)';
            colorArray[2] = 'rgb(255,163,163)';
        break;

        case 17:
            colorArray[0] = 'rgb(224,218,74)';
            colorArray[1] = 'rgb(255,255,255)';
            colorArray[2] = 'rgb(249,255,52)';
        break;

        case 18:
            colorArray[0] = 'rgb(87,157,214)';
            colorArray[1] = 'rgb(205,35,31)';
            colorArray[2] = 'rgb(116,191,67)';
        break;

        case 19:
            colorArray[0] = 'rgb(178,194,230)';
            colorArray[1] = 'rgb(1,44,95)';
            colorArray[2] = 'rgb(251,245,211)';
        break;

        case 20:
            colorArray[0] = 'rgb(96,54,42)';
            colorArray[1] = 'rgb(232,194,142)';
            colorArray[2] = 'rgb(72,46,36)';
        break;
    }
    return colorArray;
}
