<?php
// JSON backend for search

require_once 'GSAPI.php';


if ((isset($_POST['sessionID']) && ($_POST['sessionID'] != ''))) {
    // Gets a GSAPI object for API calls
    $gsapi = GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
    if ((isset($_POST['query']) && isset($_POST['limit']))) {
        // Get the list of songs from the API search
        $songs = $gsapi->searchSongs($_POST['query'], $_POST['limit']);
        print "<table id='save-music-choice-search'>";
        if (isset($songs['error'])) {
            // There was an error getting songs
            print "<tr><td><pre>Error Code {$songs['error']}. Please try again.</pre></td></tr>";
        } else {
            // Set up different styles for different wp versions
            $preClass = ($_POST['isVersion26'] == 1) ? 'gsPreSingle26' : 'gsPreSingle27';
            $altClass = ($_POST['isVersion26'] == 1) ? 'gsTr26' : 'gsTr27';
            $isSmallBox = ($_POST['isSmallBox'] == 1) ? true : false;
            $stringLimit = ($_POST['isVersion26'] == 1) ? 73 : 80;
            if (empty($songs)) {
                // No songs were found
                print "<tr class='gsTr1'><td><pre class='{$preClass}'>No Results Found by Grooveshark Plugin</pre></td></tr>";
            } else {
                $index = 0;
                foreach ($songs as $song) {
                    // Loop through all songs
                    $songNameComplete = $song['songName'] . ' by ' . $song['artistName'];
                    if (strlen($songNameComplete) > $stringLimit) {
                        // Displays the song and artist, truncated if too long
                        $songNameComplete = substr($song['songName'], 0, $stringLimit - 3 - strlen($song['artistName'])) . "&hellip; by" . $song['artistName'];
                    }
                    $songNameComplete = preg_replace("/\'/", "&lsquo;", $songNameComplete, -1);
                    $songNameComplete = preg_replace("/\"/", "&quot;", $songNameComplete, -1);
                    // print the row containing all of this song's data
                    print (($index % 2) ? "<tr class='gsTr1'>" : "<tr class='$altClass'>") .
                          "<td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd' name='$songNameComplete::{$song['songID']}' style='cursor:pointer;' onclick='addToSelected(this.name)'></a></td>" .
                          (($isSmallBox) ? '' : "<td class='gsTableButton'><a class='gsPlay' title='Play This Song' name='{$song['songID']}' style='cursor:pointer;' onclick='toggleStatus(this);'></a></td>") .
                          "<td><pre class='$preClass'>$songNameComplete</pre></td>";
                    if ($isSmallBox) {
                        print "<td><a title='Link to {$song['songName']} on Grooveshark' target='_blank' href='{$song['liteUrl']}'>&rarr;</a></td>";
                    }
                    $index++;
                }
            }
        }
        print "</table>";
    } else {
        // query and limit not provided, return nothing
        print '';
    }
} else {
    // No session provided, return nothing
    print '';
}
?>
