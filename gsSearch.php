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

//This code handles any search the user wants to perform with the plugin.
if(!function_exists('gs_callRemote')) {
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

//Takes the search query and runs a search for songs. Returns the results in JSON format.
if (isset($_POST['call']) and $_POST['call'] == 'query' and isset($_POST['query']) and isset($_POST['sessionID']) and isset($_POST['limit'])) {
    $query = $_POST['query'];
    $sessionID = $_POST['sessionID'];
    $limit = $_POST['limit'];
    $songs = array();
    $songs = gs_callRemote('search.songs', array('query' => $query, 'limit' => $limit, 'page' => 1, 'streamableOnly' => 1), $sessionID);
    if (isset($songs['decoded']['fault']['code'])) {
        print json_encode(array("fault" => $songs['decoded']['fault']['code']));
    } else {
        $songs = $songs['decoded']['result']['songs'];
        $returnSongs = array();
        $results = count($songs);
        for ($i = 0; $i < $results; $i++) {
            $songName = $songs[$i]['songName'];
            $artistName = $songs[$i]['artistName'];
            $songID = $songs[$i]['songID'];
            $returnSongs[] = array("songName" => $songName, "artistName" => $artistName, "songID" => $songID, "songURL" => $songURL);
        }
        if (count($returnSongs) == 0) {
            $returnSongs = array();
            $returnSongs[] = array("songName" => "No Results Found", "artistName" => "Plugin", "songID" => -1, "songURL" => -1);
        }
        print json_encode($returnSongs);
    }
}

//Takes a playlist ID and returns all the songs associated with that playlist in JSON format.
if (isset($_POST['call']) and $_POST['call'] == 'aplaylistsearch' and isset($_POST['sessionID']) and isset($_POST['playlistID'])) {
    $sessionID = $_POST['sessionID'];
    $playlistID = $_POST['playlistID'];
    $songs = array();
    $songs = gs_callRemote('playlist.getSongs', array('playlistID' => $playlistID, 'page' => 1), $sessionID);
    if (isset($songs['decoded']['fault']['code'])) {
        print json_encode(array("fault" => $songs['decoded']['fault']['code']));
    } else {
        $songs = $songs['decoded']['result']['songs'];
        $returnSongs = array();
        $results = count($songs);
        for ($i = 0; $i < $results; $i++) {
            $songName = $songs[$i]['songName'];
            $artistName = $songs[$i]['artistName'];
            $songID = $songs[$i]['songID'];
            $returnSongs[] = array("songName" => $songName, "artistName" => $artistName, "songID" => $songID);
        }
        print json_encode($returnSongs);
    }
}

//Retrieves a stream key for a song. The stream key allows the external player to play songs in the post edit area, via player.js
if (isset($_POST['call']) and $_POST['call'] == 'getStreamKey' and isset($_POST['sessionID']) and isset($_POST['songID'])) {
    $sessionID = $_POST['sessionID'];
    $songID = $_POST['songID'];
    $streamKey = array();
    $streamKey = gs_callRemote('song.getStreamKey', array('songID' => $songID), $sessionID);
    $fault = $streamKey['decoded']['fault']['code'];
    if (isset($fault)) {
        print "Code $fault";
    } else {
        $streamKey = $streamKey['decoded']['result']['streamKey'];
        if ($streamKey) {
            print $streamKey;
        }
    }
}
?>
