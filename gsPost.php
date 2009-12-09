<?php

// JSON backend for getting song link/embed code for posts

//Prepare the wordpress function get_option
if (!function_exists('get_option')) {
    require_once("../../../wp-config.php");
}

if ((isset($_POST['sessionID']) && ($_POST['sessionID'] != ''))) {
    $gsapi = GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
    $gsapi->logout(); // Make sure the user is logged out so that playlists aren't accidentally attached to user accounts
    if (isset($_POST['songString'])) {
        $gs_options = get_option('gs_options');
        // songString contains all songID's for selected songs, delimited by colons
        $songsArray = explode(":", $_POST['songString']);
        // Set whether to display song as a link to GS or as a GS widget
        $displayOption = isset($_POST['displayOption']) ? ($_POST['displayOption'] ? 'widget' : 'link') : 'widget';
        // Set whether to save this song to the wordpress sidebar (1 for yes, 0 for no)
        $sidebarOption = isset($_POST['sidebarOption']) ? ($_POST['sidebarOption'] ? 1 : 0) : 0;
        // Set whether to save this song to the wordpress dashboard (1 for yes, 0 for no)
        $dashboardOption = isset($_POST['dashboardOption']) ? ($_POST['dashboardOption'] ? 1 : 0) : 0;
        // Set the width and height of the widget
        $widgetWidth = (isset($_POST['widgetWidth']) && ($_POST['widgetWidth'] != '')) ? $_POST['widgetWidth'] : 250;
        $widgetHeight = (isset($_POST['widgetHeight']) && ($_POST['widgetHeight'] != '')) ? $_POST['widgetHeight'] : 400;
        // Set the display phrase for song links
        $displayPhrase = (isset($_POST['displayPhrase']) && ($_POST['displayPhrase'] != '')) ? $_POST['displayPhrase'] : 'Grooveshark';
        // Set the playlist name
        $playlistName = (isset($_POST['playlistName']) && ($_POST['playlistName'] != '')) ? $_POST['playlistName'] : 'Grooveshark Playlist';
        // Set the widget colorscheme
        $colorScheme = $_POST['colorsSelect'];
        // Get the userID and token to save playlists and make playlist widgets
        $userID = $gs_options['userID'];
        $token = $gs_options['token'];
        // Get the include playlists status
        $includePlaylists = $gs_options['includePlaylists'];
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
        // Update saved options with new user options
        $gs_options['widgetWidth'] = $widgetWidth;
        $gs_options['widgetHeight'] = $widgetHeight;
        $gs_options['displayPhrase'] = $displayPhrase;
        $gs_options['colorScheme'] = $colorScheme;
        $gs_options['songsArray'] = $songsArray;
        update_option('gs_options', $gs_options);
        // The $content variable will contain the link or widget embed code
        $content = ($displayOption == 'widget') ? "<div id='gsWidget'>" : "<div id='gsLink'>";
        if (count($songsArray) == 1) {
            $songID = $songsArray[0];
            if ($displayOption == 'widget') {
                // single-song widget
                // NOTE: The songGetWidgetEmbedCode returns a string on success or failure, so no need for error checking on this end
                $singleEmbedCode = $gsapi->songGetWidgetEmbedCode($songID, $widgetWidth);
                if ((!(bool)stripos($singleEmbedCode, 'Error')) && ($sidebarOption)) {
                    // If no error code and user wants to save widget, save the widget to the sidebar
                    $gs_options['sidebarPlaylists'] = array('id' => $songID, 'embed' => preg_replace("/width=\"\d+\"/", "width=\"200\"", $singleEmbedCode));
                    update_option('gs_options', $gs_options);
                }
                if ((!(bool)stripos($singleEmbedCode, 'Error')) && ($dashboardOption)) {
                    $gs_options['dashboardPlaylists'] = array('id' => $songID, 'embed' => $singleEmbedCode);
                    update_option('gs_options', $gs_options);
                }
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
            if (!empty($token) && ($includePlaylists != 0)) {
                // User is logged in and chose to include created playlists in their GS account
                $gsapi->loginViaAuthToken($token);
            }

            // Create a playlist for the new selected songs
            $playlist = $gsapi->playlistCreate($playlistName);
            if (isset($playlist['error'])) {
                // There was a problem creating the playlist
                $content .= 'Error Code ' . $playlist['error'] . '. Contact the author for support.';
            } else {
                $playlistID = $playlist['playlistID'];
                // Add the option in case it doesn't exist
                // Add the songs to the playlist
                $numSongs = 0;
                foreach ($songsArray as $songID) {
                    $result = $gsapi->playlistAddSong($playlistID, $songID);
                    if ($result) {
                        $songInformation = $gsapi->songAbout($songID);
                        if (!isset($songInformation['error'])) {
                            $numSongs++;
                            $gs_options['userPlaylists'][$playlistID][$songID] = array('songName' => $songInformation['songName'], 'artistName' => $songInformation['artistName']);
                        }
                    }
                }
                if ($numSongs == 0) {
                    $content .= "There was a problem adding your songs. Please try again. If the problem persists, try logging in on the Grooveshark Settings page in your Wordpress admin. If this does not solve your problem, contact the plugin author.</div>";
                    die($content);
                } else {
                    $gs_options['userPlaylists'][$playlistID]['playlistInfo'] = array('name' => $playlistName, 'numSongs' => $numSongs);
                    update_option('gs_options', $gs_options);
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
                    if ((!(bool)stripos($embedCode, 'Error')) && ($sidebarOption)) {
                        // If no error code and user wants to save widget, save the widget to the sidebar
                        $gs_options['sidebarPlaylists'] = array('id' => $playlistID, 'embed' => preg_replace("/width=\"\d+\"/", "width=\"200\"", $embedCode));
                        update_option('gs_options', $gs_options);
                    }
                    if ((!(bool)stripos($embedCode, 'Error')) && ($dashboardOption)) {
                        $gs_options['dashboardPlaylists'] = array('id' => $playlistID, 'embed' => $embedCode);
                        update_option('gs_options', $gs_options);
                    }
                    // Add the playlistID to the post as a hidden variable to automatically pull song list when this post is edited
                    $embedCode .= "<input type='hidden' id='gsPlaylistID' value='$playlistID'/><span style='font-size:1px; clear:both;'>Grooveshark Plugin Widget</span>";
                    $content .= $embedCode;
                } elseif ($displayOption == 'link') {
                    // Get the link to the playlist on GS and add it
                    $playlistNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]([a-zA-Z0-9]?)/", "$1_$2", $playlistName, -1);
                    $content .= "<a target='_blank' href='http://listen.grooveshark.com/playlist/$playlistNameUrl/$playlistID'>$displayPhrase: $playlistName</a>";
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
