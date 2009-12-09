<?php

// JSON backend for getting song link/embed code for posts

//Prepare the wordpress function get_option
if (!function_exists('get_option')) {
    require_once("../../../wp-config.php");
}

$reportData = '';

if ((isset($_POST['sessionID']) && ($_POST['sessionID'] != ''))) {
    $gsapi = GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
    $gsapi->logout(); // Make sure the user is logged out so that playlists aren't accidentally attached to user accounts
    if (isset($_POST['songString'])) {
        $gs_options = get_option('gs_options');
        // songString contains all songID's for selected songs, delimited by colons
        $songsArray = explode(":", $_POST['songString']);
        // Should get all these options from the blog admin
        // Set whether to display song as a link to GS or as a GS widget
        $displayOption = $gs_options['commentDisplayOption'];
        // Set the width and height of the widget
        $widgetWidth = $gs_options['commentWidgetWidth'];
        $widgetHeight = (isset($_POST['widgetHeight']) && ($_POST['widgetHeight'] != '')) ? $_POST['widgetHeight'] : 400;
        if ($gs_options['commentWidgetHeight'] != 0) {
            $widgetHeight = $gs_options['commentWidgetHeight'];
        }
        // Set the display phrase for song links
        $displayPhrase = $gs_options['commentDisplayPhrase'];
        // Set the playlist name
        $playlistName = $gs_options['commentPlaylistName'];
        // Set the widget colorscheme
        $colorScheme = $gs_options['commentColorScheme'];
        // Get the userID and token to save playlists and make playlist widgets
        // Make sure the widget width and height are in acceptable ranges
        if ($widgetWidth < 150) {
            $widgetWidth = 150;
        }
        if ($widgetWidth > 1000) {
            $widgetWidth = 1000;
        }
        if ($widgetHeight < 150) {
            $widgetHeight = 150;
        }
        if ($widgetHeight > 1000) {
            $widgetHeight = 1000;
        }
        // The $content variable will contain the link or widget embed code
        $content = ($displayOption == 'widget') ? "<div id='gsWidget'>" : "<div id='gsLink'>";
        if (count($songsArray) == 1) {
            $songID = $songsArray[0];
            if ($displayOption == 'widget') {
                // single-song widget
                // NOTE: The songGetWidgetEmbedCode returns a string on success or failure, so no need for error checking on this end
                $singleEmbedCode = $gsapi->songGetWidgetEmbedCode($songID, $widgetWidth);
                $content .= $singleEmbedCode;
            } elseif ($displayOption == 'link') {
                $songArray = $gsapi->songAbout($songID);
                if (isset($songArray['error'])) {
                    // Could not get song information, return error
                    $content .= 'Error Code ' . $songArray['error'] . '. Contact the author for support.';
                } else {
                    $songNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]+([a-zA-Z0-9]?)/", "$1_$2", $songArray['songName'], -1);
                    // Add the link for single-song
                    $content .= 
            "<a target='_blank' href='http://listen.grooveshark.com/song/$songNameUrl/$songID'>$displayPhrase: {$songArray['songName']} by {$songArray['artistName']}</a>";
                }
            }
        } else {
            // More than one song
            // Create a playlist for the new selected songs
            $playlist = $gsapi->playlistCreate($playlistName);
            $reportData .= " $playlist";
            if (isset($playlist['error'])) {
                // There was a problem creating the playlist
                $content .= 'Error Code ' . $playlist['error'] . '. Contact the author for support.';
            } else {
                $playlistID = $playlist['playlistID'];
                // Add the songs to the playlist
                $songLimit = 99999;
                if ($displayOption = 'widget') {
                    // Limit songs only if being added to an embedded widget
                    if ($gs_options['commentSongLimit'] != 0) {
                        $songLimit = $gs_options['commentSongLimit'];
                    }
                }
                $numSongs = 0;
                foreach ($songsArray as $songID) {
                    if ($numSongs < $songLimit) {
                        $gsapi->playlistAddSong($playlistID, $songID);
                    } 
                    $numSongs++;
                }
                if ($displayOption == 'widget') {
                    // playlist widget
                    // Get the colors depending on colorscheme
                    $color1 = '000000';
                    $color2 = 'FFFFFF';
                    $color3 = '666666';
                    switch ($colorScheme) {
                        case 1:
                            $color1 = 'CCA20C';
                            $color2 = '4D221C'; 
                            $color3 = 'CC7C0C';
                            break;
                        case 2:
                            $color1 = '87FF00';
                            $color2 = '0088FF'; 
                            $color3 = 'FF0054'; 
                            break;
                        case 3:
                            $color1 = 'FFED90';
                            $color2 = '359668';
                            $color3 = 'A8D46F';
                            break;
                        case 4:
                            $color1 = 'F0E4CC';
                            $color2 = 'F38630';
                            $color3 = 'A7DBD8';
                            break;
                        case 5:
                            $color1 = 'FFFFFF';
                            $color2 = '377D9F';
                            $color3 = 'F6D61F';
                            break;
                        case 6:
                            $color1 = '450512';
                            $color2 = 'D9183D';
                            $color3 = '8A0721';
                            break;
                        case 7:
                            $color1 = 'B4D5DA';
                            $color2 = '813B45';
                            $color3 = 'B1BABF';
                            break;
                        case 8:
                            $color1 = 'E8DA5E';
                            $color2 = 'FF4746';
                            $color3 = 'FFFFFF';
                            break;
                        case 9:
                            $color1 = '993937';
                            $color2 = '5AA3A0';
                            $color3 = 'B81207';
                            break;
                        case 10:
                            $color1 = 'FFFFFF';
                            $color2 = '009609';
                            $color3 = 'E9FF24';
                            break;
                        case 11:
                            $color1 = 'FFFFFF';
                            $color2 = '7A7A7A';
                            $color3 = 'D6D6D6';
                            break;
                        case 12:
                            $color1 = 'FFFFFF';
                            $color2 = 'D70860';
                            $color3 = '9A9A9A';
                            break;
                        case 13:
                            $color1 = '000000';
                            $color2 = 'FFFFFF';
                            $color3 = '620BB3';
                            break;
                        case 14:
                            $color1 = '4B3120';
                            $color2 = 'A6984D';
                            $color3 = '716627';
                            break;
                        case 15:
                            $color1 = 'F1CE09';
                            $color2 = '000000';
                            $color3 = 'FFFFFF';
                            break;
                        case 16:
                            $color1 = 'FFBDBD';
                            $color2 = 'DD1122';
                            $color3 = 'FFA3A3';
                            break;
                        case 17:
                            $color1 = 'E0DA4A';
                            $color2 = 'FFFFFF';
                            $color3 = 'F9FF34';
                            break;
                        case 18:
                            $color1 = '579DD6';
                            $color2 = 'CD231F';
                            $color3 = '74BF43';
                            break;
                        case 19:
                            $color1 = 'B2C2E6';
                            $color2 = '012C5F';
                            $color3 = 'FBF5D3';
                            break;
                        case 20:
                            $color1 = '60362A';
                            $color2 = 'E8C28E';
                            $color3 = '482E24';
                            break;
                        default:
                            break;
                    }
                    // NOTE: If you are capable and wish to change the colors for your widget to a custom color scheme of your choice,
                    //       you can change all the colors here on line 203. See lines 327-347 in GSAPI.php for descriptions of parameters.
                    //       ALSO, all color parameters must be hex codes of the rgb colors WITHOUT prefixes ('FFFFFF' for white, 'FF0000' for red, '00FF00' for blue, etc).
                    //       FINALLY, if you do change it to custom colors, comment out lines 92-198 in this file to save some processing time.
                    $embedCode = $gsapi->playlistGetWidgetEmbedCode($playlistID, $widgetWidth, $widgetHeight, $playlistName, $color2, $color1, $color1, $color3, $color2, $color1, $color3, $color2, $color2, $color1, $color3, $color2, $color2, $color3, $color2);
                    $content .= $embedCode;
                } elseif ($displayOption == 'link') {
                    // Get the link to the playlist on GS and add it
                    $playlistNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]([a-zA-Z0-9]?)/", "$1_$2", $playlistName, -1);
                    $content .= "<a target='_blank' href='http://listen.grooveshark.com/playlist/$playlistNameUrl/$playlistID'>$displayPhrase: $playlistName</a>";
                    $content .= " $reportData";
                    update_option('gs_options', $gs_options);
                }
            }
        }
        if ((($displayOption == 'widget') || ($displayOption == 'link'))) {
            $content .= "</div>";
        }
        print $content;
    } else {
        // No song information is available, return nothing
        print '';
    }
} else {
    // No sessionID was provided, return nothing
    print '';
}

