player = '';
aquery = '';
asavedheight = 0;
gsToolbarCount = 0;

//Set up the player and format the UI for wordpress versions 2.6.x and 2.5.x
jQuery(document).ready(function($) {
    if (document.getElementById('widgetHeight') != null) {
        document.getElementById('widgetHeight').value = 176;
    }
    var playlistID = 0;
    var heightRegEx = /height\=\"(\d+)\"/
    var playlistRegEx = /\<input type\=\'hidden\' name\=\'gsPlaylistID\' id\=\'gsPlaylistID\' value\=\'(\d+)\'\/>/;
    var postContent = document.getElementById('content');
    if (postContent != null && postContent.value != null) {
        var matchedPlaylist = 0;
        if (postContent.value.match(playlistRegEx)) {
            matchedPlaylist = 1;
            playlistMatch = playlistRegEx.exec();
            playlistID = playlistMatch[1];
            setTimeout('updateCount()',500);
        }
        if (postContent.value.match(heightRegEx) && matchedPlaylist == 1) {
            heightMatch = heightRegEx.exec();
            playlistHeight = heightMatch[1];
            document.getElementById('widgetHeight').value = playlistHeight;
            asavedheight = 1;
        }
    }
    var postContentIframe = document.getElementById('content_ifr');
    if (postContentIframe != null) {
        if (postContentIframe.value != null) {
            if (postContentIframe.value.match(playlistRegEx)) {
                playlistMatch = playlistRegEx.exec();
                playlistID = playlistMatch[1];
                setTimeout('updateCount()',500);
            }
            if (postContentIframe.value.match(heightRegEx)) {
                heightMatch = heightRegEx.exec();
                playlistHeight = heightMatch[1];
                document.getElementById('widgetHeight').value = playlistHeight;
                asavedheight = 1;
            }
        }
    }
    playlistID = parseInt(playlistID);
    if (document.getElementById('wpVersion') != null) {
        addToSelectedPlaylist(playlistID);
    }
    if (document.getElementById('gsSessionID') != null && document.getElementById('gsSessionID').value != null) {
        player = new jsPlayer(document.getElementById('gsSessionID').value);
    }
    if (document.getElementById('wpVersion') != null && document.getElementById('wpVersion').value != null) {
        if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
            document.getElementById('gs-query').style.width = '388px';
        }
    }
    selectedTable = document.getElementById('selected-songs-table');
    if (selectedTable != null) {
        if (selectedTable.rows.length > 0) {
            tableDnD = new TableDnD();
            tableDnD.init(selectedTable);
            for (var i = 0; i < selectedTable.rows.length; i++) {
                tableRow = selectedTable.rows[i];
                tableDnD.addDraggableRow(tableRow);
            }
        }
    }
    addEdGrooveshark();
    setTimeout('addGroovesharkContentToolbar()',1000);
	jQuery('#gs-query').focus(function(){
		if (jQuery('#gs-query').hasClass("empty")) {
			jQuery('#gs-query').removeClass("empty").val("");
		}
	});
});

//Adds a Grooveshark button to the ed_toolbar
function addEdGrooveshark() {
    if (document.getElementById('ed_toolbar') != null) {
        edGroovesharkButton = document.createElement("input");
        edGroovesharkButton.id = 'ed_grooveshark';
        edGroovesharkButton.className = 'ed_button';
        edGroovesharkButton.type = 'button';
        edGroovesharkButton.value = 'music';
        edGroovesharkButton.title = 'Place your music';
        edGroovesharkButton.onclick = function() {insertGroovesharkTag(1);};
        document.getElementById('ed_toolbar').appendChild(edGroovesharkButton);
    }
}


//Adds a Grooveshark button to the visual toolbar
function addGroovesharkContentToolbar() {
    if (document.getElementById('content_toolbar1') != null) {
        gsSpan = document.createElement("span");
        gsSpan.className = 'gsNote';
        gsAnchor = document.createElement("a");
        gsAnchor.id = 'content_grooveshark';
        gsAnchor.className = 'mceButton mceButtonEnabled';
        gsAnchor.title = 'Place your music';
        gsAnchor.onclick = function() {insertGroovesharkTag(2);};
        gsAnchor.href = 'javascript:;';
        gsAnchor.appendChild(gsSpan);
        gsCell =  document.getElementById('content_toolbar1').getElementsByTagName('tr')[0].insertCell(1);
        gsCell.appendChild(gsAnchor);
    } else {
        if (gsToolbarCount <= 20) {
            gsToolbarCount++;
            setTimeout('addGroovesharkContentToolbar()',1000);
        }
    }
}


//Inserts the tag which will be replaced by embed code.
function insertGroovesharkTag(identifier) {
    if (switchEditors.go() != null && identifier == 2) {
        switchEditors.go('content','html');
    }
    if (document.getElementById('gsTagStatus') != null && document.getElementById('gsTagStatus').value == 0) {
        //IE support
        if (document.selection) {
            if (document.getElementById('content') != null) {
                var edCanvas = document.getElementById('content');
            }
            edCanvas.focus();
            sel = document.selection.createRange();
            if (sel.text.length > 0) {
                sel.text = sel.text + '[[grooveshark]]';
            }
            sel.text = '[[grooveshark]]';
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
        document.getElementById('ed_grooveshark').disabled = true;
        document.getElementById('content_grooveshark').title = 'One tag at a time';
        document.getElementById('content_grooveshark').onclick = function() {};
    }
    if (switchEditors.go() != null && identifier == 2) {
        switchEditors.go('content','tinymce');
    }
}

//Intermediate between the user and player.js. Gets the song ID for the song, and the session ID for the stream key.
function toggleStatus(obj) {
    var gsObj = obj;
    sessionID = document.getElementById('gsSessionID').value;
    wpurl = document.getElementById('gsBlogUrl').value;
    //streamKey = 0;
    songID = obj.name;
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
    if (songID) {
        player.toggleStatus(gsObj, songID, wpurl);
    }
}

//Handles the user's searches.
function gsSearch() {
    aquery = document.getElementById("gs-query").value;
    sessionID = document.getElementById('gsSessionID').value;
    limit = document.getElementById('gsLimit').value;
    wpurl = document.getElementById('gsBlogUrl').value;
    if (aquery != '') {
        jQuery(document).ready(function($) {
            jQuery.post(wpurl + "/wp-content/plugins/grooveshark/gsSearch.php", {call: 'query', query: aquery, sessionID: sessionID, limit: limit}, function(returnedSongs) {
                if (returnedSongs.length > 0) {
					jQuery('#search-results-container').show();
                    document.getElementById('queryResult').innerHTML = "Search results for \"" + aquery + "\":";
                    songTable = document.createElement("table");
                    songTable.id = "save-music-choice-search";
                    if (typeof returnedSongs.fault != 'undefined') {
                        tableRow = songTable.insertRow(0);
                        tableCell = tableRow.insertCell(0);
                        pre = document.createElement("pre");
                        pre.innerHTML = 'Error Code ' + returnedSongs.fault + '. Contact author for support.';
                        return;
                    }
                    for(var i = 0; i < returnedSongs.length; i++) {
                        var songName = returnedSongs[i].songName;
                        var artistName = returnedSongs[i].artistName;
                        var songID = returnedSongs[i].songID;
                        if (songName && artistName && songID) {
                            tableRow = songTable.insertRow(0);
                            if (i % 2) {
                                if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                                    tableRow.className = "gsTr26";
                                } else {
                                    tableRow.className = "gsTr27";
                                }
                            } else {
                                tableRow.className = "gsTr1";
                            }
                            td1 = tableRow.insertCell(0);
                            pre1 = document.createElement("pre");
                            pre1.innerHTML = songNameComplete = songName + " by " + artistName;
                            preClass = 'gsPreSingle27';
                            stringLimit = 80;
                            if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                                stringLimit = 73;
                                preClass = 'gsPreSingle26';
                            }
                            pre1.className = preClass;
                            if (songNameComplete.length > stringLimit) {
                                songNameComplete = songName.substring(0,stringLimit - 3 - artistName.length) + "&hellip;" + " by " + artistName;
                                pre1.innerHTML = songNameComplete;
                            }
                            td1.appendChild(pre1);
                            if (songName != 'No Results Found') {
                                td2a = tableRow.insertCell(0);
                                td2a.className = 'gsTableButton';
                                image0 = document.createElement("a");
                                image0.className = "gsPlay";
                                image0.name = songID;
                                image0.onclick = function() {toggleStatus(this);};
                                image0.style.cursor = "pointer";
                                td2a.appendChild(image0);
                                td2 = tableRow.insertCell(0);
                                td2.className = 'gsTableButton';
                                image1 = document.createElement("a");
                                image1.className = "gsAdd";
                                image1.name = songNameComplete + "::" + songID;
                                image1.onclick = function() {addToSelected(this.name);};
                                image1.style.cursor = "pointer"
                                td2.appendChild(image1);
                            }
                        }
                    }
                    document.getElementById("save-music-choice-search").parentNode.replaceChild(songTable,document.getElementById("save-music-choice-search"));
                }
            }, "json");
        });
    }
}

//Handles selecting a song for addition to the post.
function addToSelected(songInfo) {
    wpurl = document.getElementById('gsBlogUrl').value;
    tableDND = new TableDnD();
    tableDND.init(document.getElementById('selected-songs-table'));
    temp = new Array();
    temp = songInfo.split("::");
    songNameComplete = temp[0];
    songID = temp[1];
    if (songNameComplete && songID) {
        selectedTable = document.getElementById("selected-songs-table");
        tableRow = selectedTable.insertRow(selectedTable.rows.length);
        tableDND.addDraggableRow(tableRow);
        if (selectedTable.rows.length % 2 || document.getElementById('userCheck').value == 0) {
            if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                tableRow.className = 'gsTr26';
            } else {
                tableRow.className = 'gsTr27';
            }
        } else {
            tableRow.className = 'gsTr1';
        }
        td1 = tableRow.insertCell(0);
        td1.className = 'gsTableButton'
        image1 = document.createElement("a");
        image1.className = "gsRemove";
        image1.name = selectedTable.rows.length;
        image1.style.cursor = "pointer";
        image1.style.float = "right";
        image1.onclick = function() {removeFromSelected(parseInt(this.name));};
        td1.appendChild(image1);
        td2 = tableRow.insertCell(0);
        pre1 = document.createElement("pre");
        preClass = 'gsPreSingle27';
        if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
            preClass = 'gsPreSingle26';
        }
        pre1.className = preClass;
        pre1.innerHTML = songNameComplete;
        input1 = document.createElement("input");
        input1.type= "hidden";
        input1.name = "songsInfoArray[]";
        input1.className = "songsInfoArrayClass";
        input1.value = songID;
        td2.appendChild(pre1);
        td2.appendChild(input1);
        td3 = tableRow.insertCell(0);
        td3.className = 'gsTableButton';
        image2 = document.createElement("a");
        image2.className = "gsPlay";
        image2.name = songID;
        image2.style.cursor = "pointer";
        image2.onclick = function() {toggleStatus(this);};
        td3.appendChild(image2);
        if (document.getElementById('widgetHeight').value < 1000 && asavedheight == 0) {
            document.getElementById('widgetHeight').value = parseInt(document.getElementById('widgetHeight').value) + 22;
        }
    }
    updateCount();
}

//Handles selecting a playlist for addition to the post.
function addToSelectedPlaylist(songInfo) {
    if (songInfo == 0) {
        return;
    }
    tableDND = new TableDnD();
    tableDND.init(document.getElementById('selected-songs-table'));
    sessionID = document.getElementById('gsSessionID').value;
    wpurl = document.getElementById('gsBlogUrl').value;
    playlistID = songInfo;
    selectedTable = document.getElementById("selected-songs-table");
    count = selectedTable.rows.length;
    jQuery(document).ready(function($) {
        jQuery.post(wpurl + "/wp-content/plugins/grooveshark/gsSearch.php", {call: 'aplaylistsearch', sessionID: sessionID, playlistID: playlistID}, function(returnedSongs) {
            if (returnedSongs.length > 0) {
                if (typeof returnedSongs.fault != 'undefined') {
                    tableRow = selectedTable.insertRow(selectedTable.rows.length);
                    tableCell = tableRow.insertCell(0);
                    pre = document.createElement('pre');
                    pre.innerHTML = 'Error Code ' + returnedSongs.fault + '. Contact the author for support.';
                    return;
                }
                for (var i = 0; i < returnedSongs.length; i++) {
                    var songName = returnedSongs[i].songName;
                    var artistName = returnedSongs[i].artistName;
                    var songNameComplete = songName + " by " + artistName;
                    stringLimit = 78;
                    if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                        stringLimit = 71;
                    }
                    if (songNameComplete.length > stringLimit) {
                        songNameComplete = songName.substring(0,stringLimit - 3 - artistName.length) + "&hellip;" + " by " + artistName;
                    }
                    var songID = returnedSongs[i].songID;
                    if (songNameComplete && songID) {
                        count++;
                        tableRow = selectedTable.insertRow(selectedTable.rows.length);
                        tableDND.addDraggableRow(tableRow);
                        selectedTableRowLength = selectedTable.rows.length;
						if (selectedTableRowLength % 2) {
                            if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                                tableRow.className = 'gsTr26';
                            } else {
                                tableRow.className = 'gsTr27';
                            }
                        } else {
                            tableRow.className = 'gsTr1';
                        }
                        td1 = tableRow.insertCell(0);
                        td1.className = 'gsTableButton';
                        image1 = document.createElement("a");
                        image1.className = "gsRemove";
                        image1.name = selectedTable.rows.length;
                        image1.style.cursor = "pointer";
                        image1.style.float = "right";
                        image1.onclick = function() {removeFromSelected(parseInt(this.name));};
                        td1.appendChild(image1);
                        td2 = tableRow.insertCell(0);
                        pre1 = document.createElement("pre");
                        preClass = 'gsPreSingle27';
                        if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                            preClass = 'gsPreSingle26';
                        }
                        pre1.className = preClass;
                        pre1.innerHTML = songNameComplete;
                        input1 = document.createElement("input");
                        input1.type= "hidden";
                        input1.name = "songsInfoArray[]";
                        input1.className = "songsInfoArrayClass";
                        input1.value = songID;
                        td2.appendChild(pre1);
                        td2.appendChild(input1);
                        td3 = tableRow.insertCell(0);
                        td3.className = 'gsTableButton';
                        image2 = document.createElement("a");
                        image2.className = "gsPlay";
                        image2.name = songID;
                        image2.style.cursor = "pointer";
                        image2.onclick = function() {toggleStatus(this);};
                        td3.appendChild(image2);
                        if (document.getElementById('widgetHeight').value < 1000 && asavedheight == 0) {
                            document.getElementById('widgetHeight').value = parseInt(document.getElementById('widgetHeight').value) + 22;
                        }
                    }
                }
            }
        }, "json");
    });
    setTimeout('updateCount()',500);
}

//Handles unselecting a song for addition.
function removeFromSelected(rowIndex) {
    selectedTable = document.getElementById("selected-songs-table");
    selectedTable.deleteRow(rowIndex);
    if (document.getElementById('widgetHeight').value > 176 && asavedheight == 0) {
        document.getElementById('widgetHeight').value = parseInt(document.getElementById('widgetHeight').value) - 22;
    }
    for (var i = 0; i < selectedTable.rows.length; i++) {
        image1 = selectedTable.rows[i].getElementsByTagName("a")[1];
        curIndex = parseInt(image1.name)
        if (curIndex >= rowIndex) {
            image1.name = curIndex - 1;
        }
        if (image1.name % 2) {
            selectedTable.rows[image1.name].className = 'gsTr1';
        } else {
            if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
                selectedTable.rows[image1.name].className = 'gsTr26'; 
            } else {
                selectedTable.rows[image1.name].className = 'gsTr27';
            }
        }
    }
    updateCount();
}

//Clears all songs that are selected for addition.
function clearSelected() {
    //alert(document.getElementById('groovesharkdiv').offsetWidth);
    newTable = document.createElement("table");
    newTable.id = "selected-songs-table";
    document.getElementById("selected-songs-table").parentNode.replaceChild(newTable,document.getElementById("selected-songs-table"));
    document.getElementById("selectedCount").innerHTML = "Selected Songs (0):"
    if (asavedheight == 0) {
        document.getElementById('widgetHeight').value = 176;
        asavedheight = 0;
    }
}

//Only needed because the addToSelectedPlaylist function for some reason does not update the selected count on its own.
function updateCount() {
    var selectedCount = document.getElementById("selectedCount");
    selectedCountValue = jQuery('#selected-songs-table tr').length;
    selectedCount.innerHTML = "Selected Songs (" + selectedCountValue + "):";
    if (jQuery('#selected-songs-table tr').length > 0) {
        document.getElementById("selected-songs-table").className = 'gsSelectedPopulated';
    } else {
        document.getElementById("selected-songs-table").className = 'gsSelectedEmpty';
    }
    if (document.getElementById('userCheck').value == 0 && selectedCount.innerHTML == "Selected Songs (2):") {
        selectedCount.innerHTML = "Selected Songs (1):";
    }
}

//Change the example display phrase to reflect what the user typed in.
function changeExample(obj) {
    exampleValue = obj.value;
    obj.parentNode.parentNode.getElementsByTagName('td')[2].innerHTML = "Example: \"" + exampleValue + ": song by artist\"";
}

//Change the example playlist name to reflect what the user typed in.
function changeExamplePlaylist(obj) {
    exampleValue = obj.value;
    obj.parentNode.parentNode.getElementsByTagName('td')[2].innerHTML = "Example: \"Grooveshark: " + exampleValue + "\"";
}

//Perform a check on the values for widget width and widget height.
function checkWidgetValue(obj) {
    widgetValue = parseInt(obj.value);
    if (isNaN(widgetValue)) {
        widgetValue = 250;
    }
    if (widgetValue < 150) {
        widgetValue = 150;
    }
    if (widgetValue > 1000) {
        widgetValue = 1000;
    }
    obj.value = widgetValue;
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

//Handles appending a widget/link to the post content.
function gsAppendToContent(obj) {
    //songsArray = document.getElementsByName('songsInfoArray[]');
    songsArray = new Array();
    jQuery("input.songsInfoArrayClass").each(function() {
        songsArray.push(this.value);
    });
    if (songsArray.length > 0) {
        obj.value = 'Saving...';
        obj.disabled = true;
        songString = songsArray[0];
        for (var i = 1; i < songsArray.length; i++) {
            songString += ":" + songsArray[i];
        }
        displayOptions = document.getElementsByName("displayChoice");
        displayOption = 0;
        if (displayOptions[1].checked) {
            displayOption = 1;
        }
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
        positionBeginning = document.getElementById('gsPosition').checked;
        colorScheme = document.getElementById('colors-select').value;
        var displayPhrase = document.getElementById('displayPhrase').value;
        var playlistName = document.getElementById('playlistsName').value;
        jQuery(document).ready(function($) {
            jQuery.post(document.getElementById('gsBlogUrl').value + '/wp-content/plugins/grooveshark/gsPost.php', {songString: songString, displayOption: displayOption, widgetWidth: widgetWidth, widgetHeight: widgetHeight, colorsSelect: colorScheme, displayPhrase: displayPhrase, playlistsName: playlistName, sessionID: document.getElementById('gsSessionID').value}, function(returnedData) {
                if (switchEditors.go() != null) {
                    switchEditors.go('content','html');
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
                document.getElementById('ed_grooveshark').disabled = false;
                document.getElementById('ed_grooveshark').title = 'Place your music';
                document.getElementById('content_grooveshark').onclick = function() {insertGroovesharkTag();};
                document.getElementById('content_grooveshark').title = 'Place your music';
                obj.value = 'Save Music';
                obj.disabled = false;
                if (switchEditors.go() != null) {
                    switchEditors.go('content','tinymce');
                }
            });
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
    tabClass = 'gsTabActive27';
    tabClass2 = 'gsTabInactive27';
    if (document.getElementById('wpVersion').value.indexOf('2.6') != -1 || document.getElementById('wpVersion').value.indexOf('2.5') != -1) {
        tabClass = 'gsTabActive26';
        tabClass2 = 'gsTabInactive26';
    }
    if (myRow == 'Search' || myRow == 'Favorites') {
        document.getElementById('playlists-option').className = tabClass2;
        document.getElementById('songs-playlists').style.display = 'none';
    }
    if (myRow == 'Favorites' || myRow == 'Playlists') {
        document.getElementById('search-option').className = tabClass2;
        document.getElementById('songs-search').style.display = 'none';
		document.getElementById('search-results-container').style.display = 'none';
    }
    if (myRow == 'Playlists' || myRow == 'Search') {
        document.getElementById('favorites-option').className = tabClass2;
        document.getElementById('songs-favorites').style.display = 'none';
    }
    if (myRow == 'Search') {
        document.getElementById('search-option').className = tabClass;
        document.getElementById('songs-search').style.display = 'block';
		if (aquery != '') document.getElementById('search-results-container').style.display = 'block';	
    }
    if (myRow == 'Favorites') {
        document.getElementById('favorites-option').className = tabClass;
        document.getElementById('songs-favorites').style.display = 'block';
    }
    if (myRow == 'Playlists') {
        document.getElementById('playlists-option').className = tabClass;
        document.getElementById('songs-playlists').style.display = 'block';
    }
}

//Update the widget color choice made earlier by the user (appearance settings are saved)
jQuery(document).ready(function($) {
    changeColor(document.getElementById('colors-select'));
});

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
    switch (curValue) {
        case 0:
            baseColor.style.backgroundColor = 'rgb(0,0,0)';
            primaryColor.style.backgroundColor = 'rgb(255,255,255)';
            secondaryColor.style.backgroundColor = 'rgb(102,102,102)';
        break;

        case 1:
            baseColor.style.backgroundColor = 'rgb(204,162,12)';
            primaryColor.style.backgroundColor = 'rgb(77,34,28)';
            secondaryColor.style.backgroundColor = 'rgb(204,124,12)';
        break;

        case 2:
            baseColor.style.backgroundColor = 'rgb(135,255,0)';
            primaryColor.style.backgroundColor = 'rgb(0,136,255)';
            secondaryColor.style.backgroundColor = 'rgb(255,0,84)';
        break;

        case 3:
            baseColor.style.backgroundColor = 'rgb(255,237,144)';
            primaryColor.style.backgroundColor = 'rgb(53,150,104)';
            secondaryColor.style.backgroundColor = 'rgb(168,212,111)';
        break;

        case 4:
            baseColor.style.backgroundColor = 'rgb(224,228,204)';
            primaryColor.style.backgroundColor = 'rgb(243,134,48)';
            secondaryColor.style.backgroundColor = 'rgb(167,219,216)';
        break;

        case 5:
            baseColor.style.backgroundColor = 'rgb(255,255,255)';
            primaryColor.style.backgroundColor = 'rgb(55,125,159)';
            secondaryColor.style.backgroundColor = 'rgb(246,214,31)';
        break;

        case 6:
            baseColor.style.backgroundColor = 'rgb(69,5,18)';
            primaryColor.style.backgroundColor = 'rgb(217,24,62)';
            secondaryColor.style.backgroundColor = 'rgb(138,7,33)';
        break;

        case 7:
            baseColor.style.backgroundColor = 'rgb(180,213,218)';
            primaryColor.style.backgroundColor = 'rgb(129,59,69)';
            secondaryColor.style.backgroundColor = 'rgb(177,186,191)';
        break;

        case 8:
            baseColor.style.backgroundColor = 'rgb(232,218,94)';
            primaryColor.style.backgroundColor = 'rgb(255,71,70)';
            secondaryColor.style.backgroundColor = 'rgb(255,255,255)';
        break;

        case 9:
            baseColor.style.backgroundColor = 'rgb(153,57,55)';
            primaryColor.style.backgroundColor = 'rgb(90,163,160)';
            secondaryColor.style.backgroundColor = 'rgb(184,18,7)';
        break;

        case 10:
            baseColor.style.backgroundColor = 'rgb(255,255,255)';
            primaryColor.style.backgroundColor = 'rgb(0,150,9)';
            secondaryColor.style.backgroundColor = 'rgb(233,255,36)';
        break;

        case 11:
            baseColor.style.backgroundColor = 'rgb(255,255,255)';
            primaryColor.style.backgroundColor = 'rgb(122,122,122)';
            secondaryColor.style.backgroundColor = 'rgb(214,214,214)';
        break;

        case 12:
            baseColor.style.backgroundColor = 'rgb(255,255,255)';
            primaryColor.style.backgroundColor = 'rgb(215,8,96)';
            secondaryColor.style.backgroundColor = 'rgb(154,154,154)';
        break;

        case 13:
            baseColor.style.backgroundColor = 'rgb(0,0,0)';
            primaryColor.style.backgroundColor = 'rgb(255,255,255)';
            secondaryColor.style.backgroundColor = 'rgb(98,11,179)';
        break;

        case 14:
            baseColor.style.backgroundColor = 'rgb(75,49,32)';
            primaryColor.style.backgroundColor = 'rgb(166,152,77)';
            secondaryColor.style.backgroundColor = 'rgb(113,102,39)';
        break;

        case 15:
            baseColor.style.backgroundColor = 'rgb(241,206,9)';
            primaryColor.style.backgroundColor = 'rgb(0,0,0)';
            secondaryColor.style.backgroundColor = 'rgb(255,255,255)';
        break;

        case 16:
            baseColor.style.backgroundColor = 'rgb(255,189,189)';
            primaryColor.style.backgroundColor = 'rgb(221,17,34)';
            secondaryColor.style.backgroundColor = 'rgb(255,163,163)';
        break;

        case 17:
            baseColor.style.backgroundColor = 'rgb(224,218,74)';
            primaryColor.style.backgroundColor = 'rgb(255,255,255)';
            secondaryColor.style.backgroundColor = 'rgb(249,255,52)';
        break;

        case 18:
            baseColor.style.backgroundColor = 'rgb(87,157,214)';
            primaryColor.style.backgroundColor = 'rgb(205,35,31)';
            secondaryColor.style.backgroundColor = 'rgb(116,191,67)';
        break;

        case 19:
            baseColor.style.backgroundColor = 'rgb(178,194,230)';
            primaryColor.style.backgroundColor = 'rgb(1,44,95)';
            secondaryColor.style.backgroundColor = 'rgb(251,245,211)';
        break;

        case 20:
            baseColor.style.backgroundColor = 'rgb(96,54,42)';
            primaryColor.style.backgroundColor = 'rgb(232,194,142)';
            secondaryColor.style.backgroundColor = 'rgb(72,46,36)';
        break;
    }
}
