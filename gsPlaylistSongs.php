<?php
// JSON backend for playlist songs

require_once 'GSAPI.php';

if ((isset($_POST['sessionID']) && ($_POST['sessionID'] != ''))) {
    // Gets a GSAPI object for API calls
    $gsapi = GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
    if (isset($_POST['playlistID'])) {
        // Get the song list in this playlist
        $songs = $gsapi->playlistGetSongs($_POST['playlistID']);
        if (isset($songs['error'])) {
            // There was an error getting songs
            print json_encode(array('fault' => $song['error']));
        } else {
            $returnSongs = array();
            foreach ($songs as $song) {
                // Format all relevant data
                $returnSongs[] = array('songName' => $song['songName'], 'artistName' => $song['artistName'], 'songID' => $song['songID']);
            }
            print json_encode($returnSongs);
        }
    } else {
        // No playlistID provided, return nothing
        print '';
    }
} else {
    // No sessionID provided, return nothing
    print '';
}
?>
