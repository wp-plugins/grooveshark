<?php

//Defines json_encode and json_decode for PHP < 5.20
if ( !function_exists('json_decode') ){
    function json_decode($content){
        require_once 'JSON.php';
        $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        return $json->decode($content);
    }
}

if ( !function_exists('json_encode') ){
    function json_encode($content){
        require_once 'JSON.php';
        $json = new Services_JSON;
        return $json->encode($content);
    }
}

//This script handles the addition of music to a user's posts. The song ID's and formatting information are passed via $_POST, and the
//embed code or link to the song is returned to the javascript, so it can be added to the post.

//Prepare the wordpress function get_option and the function gs_callRemote that calls the Grooveshark API
if (!function_exists('get_option')) {
    require_once("../../../wp-config.php");
}

if (!function_exists('gs_callRemote')) {
    define('HOST', 'api.grooveshark.com');
    define('ENDPOINT', 'ws/1.0/?json');

    function gs_callRemote($method, $params = array(), $session = '')
    {
        $data = array('header' => array('sessionID' => $session),
                      'method' => $method,
                      'parameters' => $params);

        $data = json_encode($data);
        $header[] = "Host: " . HOST;
        $header[] = "Content-type: text/json";
        $header[] = "Content-length: " . strlen($data) . "\r\n";
        $header[] = $data;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://' . HOST . '/' . ENDPOINT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $decoded = json_decode($result, true);

        return array('raw' => $result, 'decoded' => $decoded);
    }
}

$gs_options = get_option('gs_options');
//If an API key isn't provided, quit.
if ($gs_options['APIKey'] == 0) {
    print "";
    exit;
}

//Execute the code only if the user has typed in a search query and the user has established a session 
//(automatically when the Add Music box is displayed)
if (isset($_POST['songString']) and isset($_POST['sessionID'])) {
    //The songString variable contains all the songID's of the selected songs delimited by colons
    $songsArray = explode(":",$_POST['songString']);
    //Retrieves all the appearance variables
    $displayOption = $_POST['displayOption'];
    $widgetWidth = $_POST['widgetWidth'];
    $widgetHeight = $_POST['widgetHeight'];
    $colorScheme = $_POST['colorsSelect'];
    $displayPhrase = $_POST['displayPhrase']; 
    $playlistName = $_POST['playlistsName'];
    //Gets the userID and token to save the playlists and make playlist widgets
    $userID = $gs_options['userID'];
    $token = $gs_options['token'];
    //Gets the include playlists status
    $includePlaylists = $gs_options['includePlaylists'];
    //The $content variable will contain the link or widget embed code
    $content = '';
    //The $displayOption variable shows whether the widget check box is checked, if so make a widget, else make a link.
    if ($displayOption) {
        $displayOption = 'widget';
    } else {
        $displayOption = 'link';
    }
    //Make sure the widget widths and heights are within an acceptable range
    if (!isset($widgetWidth) or $widgetWidth == '') {
        $widgetWidth = 250;
    }
    if (!isset($widgetHeight) or $widgetHeight == '') {
        $widgetHeight = 400;
    }
    if (!isset($displayPhrase) or $displayPhrase == '') {
        $displayPhrase = 'Grooveshark';
    }
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
    if ($displayOption == 'widget') {
        $content .= "<div id='gsWidget'>";
    }
    if ($displayOption == 'link') {
        $content .= "<div id='gsLink'>";
    }
    $gs_options['widgetWidth'] = $widgetWidth;
    $gs_options['widgetHeight'] = $widgetHeight;
    $gs_options['displayPhrase'] = $displayPhrase;
    $gs_options['colorScheme'] = $colorScheme;
    $gs_options['songsArray'] = $songsArray;
    update_option('gs_options',$gs_options);
    if (count($songsArray) == 1) {
        $songID = $songsArray[0];
        if ($displayOption == 'widget') {
            $widgetCode = gs_callRemote('song.getWidgetEmbedCode', array("songID" => $songID, "pxWidth" => $widgetWidth), $sessionID);
            if (isset($widgetCode['decoded']['fault']['code'])) {
                $content .= 'Error Code ' . $widgetCode['decoded']['fault']['code'] . '. Contact author for support.';
            } else {
                $widgetCode = $widgetCode['decoded']['result']['embed'];
                $content .= $widgetCode;
            }
        }
        if ($displayOption == 'link') {
            $songArray = gs_callRemote('song.about', array("songID" => $songID), $sessionID);
            if (isset($songArray['decoded']['fault']['code'])) {
                $content .= 'Error Code ' . $songArray['decoded']['fault']['code'] . '. Contact author for support.';
            } else {
                $songName = $songArray['decoded']['result']['song']['songName'];
                $songNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]+([a-zA-Z0-9]?)/","$1_$2",$songName,-1);
                $artistName = $songArray['decoded']['result']['song']['artistName'];
                $songURL = "http://listen.grooveshark.com/song/$songNameUrl/$songID";
                $liteUrl = "<a target='_blank' href='$songURL'>$displayPhrase: $songName by $artistName</a>";
                $content .= $liteUrl;
            }
        }
    } else {
        if ($token != 0 and $includePlaylists != 0) {
            gs_callRemote('session.loginViaAuthToken',array('token' => $token), $sessionID);
        }
        if (!isset($playlistName) or $playlistName == '') {
            $playlistName = "Grooveshark Playlist";
        }
        $playlist = gs_callRemote('playlist.create', array('name' => $playlistName), $sessionID);
        if (isset($playlist['decoded']['fault']['code'])) {
            $content .= 'Error Code ' . $playlist['decoded']['fault']['code'] . '. Contact author for support.';
        }
        $playlistID = $playlist['decoded']['result']['playlistID'];
        foreach ($songsArray as $songID) {
            gs_callRemote('playlist.addSong', array('playlistID' => $playlistID, 'songID' => $songID), $sessionID);
        }
        $playlistNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]([a-zA-Z0-9]?)/","$1_$2",$playlistName,-1);
        $playlistURL = "http://listen.grooveshark.com/playlist/$playlistNameUrl/$playlistID";
        $playlistLiteUrl = "<a target='_blank' href='$playlistURL'>$displayPhrase: $playlistName</a>";
        if ($displayOption == 'widget') {            
            $embedCode = '';
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
            $embedCode = gs_callRemote('playlist.getWidgetEmbedCode',array('playlistID' => $playlistID, 'width' => $widgetWidth, 'height' => $widgetHeight, 'name' => $playlistName, 'bodyText' => $color2, 'bodyTextHover' => $color1, 'bodyBackground' => $color1, 'bodyForeground' => $color3, 'playerBackground' => $color2, 'playerForeground' => $color1, 'playerBackgroundHover' => $color3, 'playerForegroundHover' => $color2, 'listBackground' => $color2, 'listForeground' => $color1, 'listBackgroundHover' => $color3, 'listForegroundHover' => $color2, 'scrollbar' => $color2, 'scrollbarHover' => $color3, 'secondaryIcon' => $color2), $sessionID);
            $embedCode = $embedCode['decoded']['result']['embed'];
            $embedCode .= "<input type='hidden' name='gsPlaylistID' id='gsPlaylistID' value='$playlistID'/>";
            $content .= $embedCode;
        } elseif ($displayOption == 'link') {
            // The playlist link just displays the playlist name after the display phrase
            $content .= $playlistLiteUrl;
        }
    }
    if ($displayOption == 'widget' or $displayOption == 'link') {
        $content .= "</div>";
    }
    print $content;
}
