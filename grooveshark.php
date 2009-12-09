<?php
/*
Plugin Name: Grooveshark for Wordpress
Plugin URI: http://www.grooveshark.com/wordpress
Description: Search for <a href="http://www.grooveshark.com">Grooveshark</a> songs and add links to a song or song widgets to your blog posts. 
Author: Roberto Sanchez and Vishal Agarwala
Version: 1.2.0
Author URI: http://www.grooveshark.com
*/

/*
Copyright 2009 Escape Media Group (email: roberto.sanchez@escapemg.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'GSAPI.php';


// Checks to see if the options for this plugin exists. If not, the options are added
if (get_option('gs_options') == FALSE) {
    add_option('gs_options',array(
        'token' => '', // auth token used for API login
        'userID' => 0, // GS userID used for favorites/playlists
        'numberOfSongs' => 30, // restrict results returned for search
        'APIKey' => '1100e42a014847408ff940b233a39930', // used to access GS API for plugin
        'displayPhrase' => 'Grooveshark Song Link', // Display phrase precedes song/playlist name as a cue to readers
        'widgetWidth' => 250, // width of the GS widget used to embed songs in posts
        'widgetHeight' => 176, // height of the GS widget
        'colorScheme' => 'default', // color scheme used for the GS widget
        'sidebarPlaylists' => array('id' => '', 'embed' => ''), // Save the playlist id and embed code for the sidebar playlist
        'userPlaylists' => array(),
        'playlistsModifiedTime' => 0,
        'dashboardPlaylists' => array(), // Save playlists to display on the dashboard
        'musicComments' => 0, // Toggle the option to enable music comments
        'commentDisplayOption' => 'widget', // Display music in comments as widget/link
        'commentWidgetWidth' => 200, // Width of music widgets in comments
        'commentWidgetHeight' => 0, // Height of music widgets in comments (0 for autoadjust)
        'commentDisplayPhrase' => 'Grooveshark Song Link', // Display phrase for music comment links
        'commentPlaylistName' => 'Blog Playlist', // Names of playlists saved using music comments
        'commentColorScheme' => 0, // Color scheme of music comment playlists
        'commentSongLimit' => 1, // Limit the number of songs that can be added to comment widgets (0 for no limit, also limit only applies to widget)
        'includePlaylists' => 1, // include playlists created by the plugin in a user's GS profile
        'sidebarRss' => array(),
        'autosaveMusic' => 1)); // saves the music to the post when the user updates post and has songs selected
}

//Retrievs the API Key
$gs_options = get_option('gs_options');
$APIKey = $gs_options['APIKey'];

// Sets up a sessionID for use with the rest of the script when making API calls
if (isset($_POST['sessionID']) and $_POST['sessionID'] != 0) {
    GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
} elseif ($APIKey != 0) {
    GSAPI::getInstance(array('APIKey' => $APIKey));
}

if (empty($gs_options['commentDisplayOption'])) {
    // This is an update, reset a few essential options to ensure a smooth transition
    $gs_options['commentDisplayOption'] = 'widget';
    $gs_options['includePlaylist'] = 1;
    $gs_options['displayPhrase'] = 'Grooveshark Song Link';
    update_option('gs_options', $gs_options);
}

add_action('admin_menu','addGroovesharkBox');

function addGroovesharkBox() 
{
    // Adds the GS "Add Music" box to the post edit and page edit pages
    if( function_exists('add_meta_box')) {
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','post','advanced','high');
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','page','advanced','high');
    } else {
        add_action('dbx_post_advanced','oldGroovesharkBox');
        add_action('dbx_page_advanced','oldGroovesharkBox');
    }
}

// The code for the "Add Music" box below the content editing text area.
function groovesharkBox() 
{
    // Get a GSAPI object for API calls in the groovesharkBox() function
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $siteurl = get_option('siteurl'); // used to provide links to js/images
    $version = get_bloginfo('version'); // used to load fixes specific to certain WP versions
    $isVersion26 = stripos($version, '2.6') !== false;
    $isVersion25 = stripos($version, '2.5') !== false;
    //get_bloginfo('wpurl') . '/wp-content/plugins/grooveshark/js/jquery.elementReady.js
    // The basic code to display the postbox. The ending tags for div are at the end of the groovesharkBox() function
    // jsPlayerReplace is used for inline playback of songs
    print "
            <input type='hidden' name='autosaveMusic' id='autosaveMusic' value='1' />
            <input type='hidden' name='isSmallBox' id='isSmallBox' value='0' />
            <input type='hidden' name='songIDs' id='songIDs' value='0' />
            <input type='hidden' name='gsTagStatus' id='gsTagStatus' value='0' />
            <input type='hidden' name='gsSessionID' value='$sessionID' id='gsSessionID' />
            <input type='hidden' name='gsBlogUrl' value='$siteurl' id='gsBlogUrl' />
            <input type='hidden' name='wpVersion' value='$version' id='wpVersion' />
            <input type='hidden' id='gsDataStore' />
            
                
    
           <div id='jsPlayerReplace'></div>
		   <!--[if IE 7]>
		   <div id='IE7'>
		   <![endif]-->
           <div id='gsInfo'>
           <p>Add music to your posts. Go to the <a href='$siteurl/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a> for more music options.</p>
           <div id='apContainer'></div>
           </div>";
    // Retrieves the saved options for this plugin
    $gs_options = get_option('gs_options');
    // If the user has an API key, the function continues. If not, the user is notified of the need for an API key
    // NOTE: This should not be an issue for current release of plugin since users are not yet required to sign up for an API key
    if ($gs_options['APIKey'] == 0) {
        print "<p style='font-size: 13px;'>An API key is required to use this plugin. Please set the API key on the settings page.</p></div></div>";
        return;
    }
    // Sets up the tabs for "search," "favorites," and "playlists."
    $tabClass = 'gsTabActive27';
    $tabClass2 = 'gsTabInactive27';
    $tabContainerClass = 'gsTabContainer27';
    $songClass = 'gsSongBox27';
    $versionClass = 'gs27';
    if ($isVersion26 || $isVersion25) {
        $tabContainerClass = 'gsTabContainer26';
        $tabClass = 'gsTabActive26';
        $tabClass2 = 'gsTabInactive26';
        $songClass = 'gsSongBox26';
        $versionClass = 'gs26';
    }
    print "<div id='gsSongSelection'>
                <ul class='$tabContainerClass'>
                    <li><a id='search-option' class='$tabClass' href='javascript:;' onclick=\"gsToggleSongSelect('Search')\">Search</a></li>
                    <li><a id='favorites-option' class='$tabClass2' href='javascript:;' onclick=\"gsToggleSongSelect('Favorites')\">Favorites</a></li>
                    <li><a id='playlists-option' class='$tabClass2' href='javascript:;' onclick=\"gsToggleSongSelect('Playlists')\">Playlists</a></li>
                    <div class='clear' style='height:0'></div>
                </ul>
            <div class='clear' style='height:0'></div>";
    $userID = $gs_options['userID'];
    $token = $gs_options['token'];
    //Check if the user has registered (0 for no, 1 for yes and logged in)
    $userCheck = 0;
    if ((($userID != '') && ($userID != 0))) {
        $userCheck = 1;
    }
    // The keywords search div
    print "
	<div id='songs-search' class='$songClass' style='display: block;'>
            <div id='searchInputWrapper'>
                    <div id='searchInput'>
                        <input tabindex='100' id='gs-query' type='text' name='gs-query' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {document.getElementById(\"gsSearchButton\").click(); return false;} else return true;' value='Search For A Song' class='empty' />
                        <input type='hidden' name='gsLimit' value='{$gs_options['numberOfSongs']}' id='gsLimit' />
                    </div>
            </div>
            <div id='searchButton'>
                <input tabindex='101' type='button' name='editPage-1' id='gsSearchButton' value='Search' class='button gsMainButton' onclick=\"gsSearch(this)\" />
            </div>
            <div class='clear' style='height:0;' /></div>
	</div>
	<div class='clear' style='height:0'></div>
	<div id='search-results-container' class='$versionClass' style='display:none;'>
            <div id='search-results-header'>
                <h4 id='queryResult'></h4>
            </div>
            <div id='search-results-wrapper'>
                <table id='save-music-choice-search' style='display:none'></table>
            </div>
	</div>
	    ";
    // The favorites div (hidden by default)
    print "<div id='songs-favorites' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        // User most be logged in to access their favorites
        print "<p>Search for your favorite songs on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page.</p>";
    } else {
        print "<table id='save-music-choice-favorites'>";
        $result = $gsapi->loginViaAuthToken($token); // login to use API functions
        $songsArray = $gsapi->userGetFavoriteSongs(); // Gets the user's favorite songs
        if (isset($songsArray['error'])) {
            // There was a problem getting the user's favorite songs
            print "<tr><td colspan='3'>Error Code " . $songsArray['error'] . ". Contact the author for support.";
        } else {
            // Get all favorite songs as rows in the table
            foreach ($songsArray as $id => $songInfo) {
                // Get necessary song information
                $songName = $songInfo['songName'];
                $artistName = $songInfo['artistName'];
                $songID = $songInfo['songID'];
                // Set a limit to how long song strings should be depending on WP versions (where boxes have smaller widths)
                // Should come up with a dynamic width system but this is good enough for most users
                $stringLimit = ($isVersion26 || $isVersion25) ? 71 : 78;
                // Sets up the name that is displayed in song list
                $songNameComplete = (strlen("$songName by $artistName") > $stringLimit) 
                                    ? substr($songName, 0, $stringLimit - 3 - strlen($artistName)) . "&hellip; by $artistName" 
                                    : "$songName by $artistName";
                // Replaces all single and double quotes with the html character entities
                $songNameComplete = preg_replace("/\'/", "&lsquo;", $songNameComplete, -1);
                $songNameComplete = preg_replace("/\"/", "&quot;", $songNameComplete, -1);
                $songName = preg_replace("/\'/", "&lsquo;", $songName, -1);
                $songName = preg_replace("/\'/", "&lsquo;", $songName, -1);
                $artistName = preg_replace("/\"/", "&quot;", $artistName, -1);
                $artisName = preg_replace("/\"/", "&quot;", $artistName, -1);
                // Sets up alternating row colors depending on WP version
                if ($id % 2) {
                    $rowClass = 'gsTr1';
                } else {
                    $rowClass = ($isVersion26 || $isVersion25) ? 'gsTr26' : 'gsTr27';
                }
                $preClass = ($isVersion26 || $isVersion25) ? 'gsPreSingle26' : 'gsPreSingle27';
                print "<tr class='$rowClass'>
                           <td class='gsTableButton'><a title='Add This Song To Your Post' class='gsAdd' name='$songNameComplete::$songID' onclick='addToSelected(this.name);' style='cursor: pointer'></a></td>
                           <td class='gsTableButton'><a title='Play This Song' class='gsPlay' name='$songID' onclick='toggleStatus(this);' style='cursor: pointer'></a></td>
                           <td><pre class='$preClass'>$songNameComplete</pre></td>
                       </tr>";
            }
        }
        print "</table>";
    }
    print "</div>"; // End favorites div
    //The playlists div (hidden by default)
    // Get the user's saved playlists and display them here
    print "<div id='songs-playlists' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        // User must be logged in to access their playlists
        print "<p>Search for your playlists on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page.</p>";
    } else {
        // NOTE: User should already be logged in from favorites div, so call to loginViaAuthToken not necessary
        print "<table id='save-music-choice-playlists'>";
        $userPlaylists = array();
        if ($gs_options['playlistsModifiedTime'] == 0) {
            // Get all the user playlists for the first time
            // NOTE: Cannot just simply call playlistsModified from the start, since for users who frequently modify playlists
            //       the plugin will have to make too many requests for duplicate playlists, dead playlists, etc...
            //       Better to just retrieve the user's current playlists and track modified playlists since this request was made
            $playlists = $gsapi->userGetPlaylists();
            if (isset($playlistArray['error'])) {
                print "<tr><td colspan='2'>Error Code " . $playlists['error'] . ". Contact the author for support.";
            } else {
                foreach ($playlists as $playlistData) {
                    $playlistID = $playlistData['playlistID'];
                    $playlistInfo = $gsapi->playlistAbout($playlistID);
                    $playlistName = $playlistInfo['name'];
                    $numberOfSongs = $playlistInfo['numSongs'];
                    if (!empty($playlistName) && !empty($numberOfSongs)) {
                        $playlistSongs = $gsapi->playlistGetSongs($playlistID);
                        foreach ($playlistSongs as $song) {
                            // Add the new song information
                            if (!empty($song['songID']) && !empty($song['songName']) && !empty($song['artistName'])) {
                                $userPlaylists[$playlistID][$song['songID']] = array('songName' => $song['songName'], 'artistName' => $song['artistName']);
                            }
                        }
                        $userPlaylists[$playlistID]['playlistInfo'] = array('name' => $playlistName, 'numSongs' => $numberOfSongs);
                    }
                }
                if (!empty($userPlaylists)) {
                    $gs_options['playlistsModifiedTime'] = time();
                    $gs_options['userPlaylists'] = $userPlaylists;
                    update_option('gs_options', $gs_options);
                }
            }
        } else {
            // Load all the user playlists and check for new ones
            $userPlaylists = $gs_options['userPlaylists'];
            $newPlaylists = array_unique($gsapi->playlistModified($gs_options['playlistsModifiedTime']));
            if (!empty($newPlaylists)) {
                // Update time, loop through the new playlists and save their information
                foreach ($newPlaylists as $playlistID) {
                    $userPlaylists[$playlistID] = array(); // clear out the old playlist entry if any to allow for addition and deletion
                    $playlistInfo = $gsapi->playlistAbout($playlistID);
                    $playlistName = $playlistInfo['name'];
                    $numberOfSongs = $playlistInfo['numSongs'];
                    if (!empty($playlistName) && !empty($numberOfSongs)) {
                        $playlistSongs = $gsapi->playlistGetSongs($playlistID);
                        foreach ($playlistSongs as $song) {
                            // Add the new song information to the playlist's song array
                            if (!empty($song['songID']) && !empty($song['songName']) && !empty($song['artistName'])) {
                                $userPlaylists[$playlistID][$song['songID']] = array('songName' => $song['songName'], 'artistName' => $song['artistName']);
                            }
                        }
                        // Add the playlist info in 
                        if (empty($userPlaylists[$playlistID])) {
                            // Remove the playlist since it no longer has songs
                            unset($userPlaylists[$playlistID]);
                        } else {
                            $userPlaylists[$playlistID]['playlistInfo'] = array('name' => $playlistName, 'numSongs' => $numberOfSongs);
                        }
                    } else {
                        // unset a playlist without a name or songs
                        unset($userPlaylists[$playlistID]);
                    }
                }
                // Save the new playlist information
                $gs_options['playlistsModifiedTime'] = time();
                $gs_options['userPlaylists'] = $userPlaylists;
                update_option('gs_options', $gs_options);
            }
        }
        if (!empty($userPlaylists)) {
            $colorId = 0;
            foreach ($userPlaylists as $playlistID => $playlistData) {
                // print a table row containing current playlist's data
                // Prepare style information
                if ($colorId % 2) {
                    $rowClass = 'gsTr1';
                } else {
                    $rowClass = ($isVersion26 || $isVersion25) ? 'gsTr26' : 'gsTr27';
                }
                $colorId++;
                $preClass = ($isVersion26 || $isVersion25) ? 'gsTr26' : 'gsTr27';
                // First, remove the entry in the array that does not correspond to a song
                $playlistInfo = $playlistData['playlistInfo'];
                unset($playlistData['playlistInfo']);
                // Then prepare the songs list
                $songString = array();
                foreach ($playlistData as $songID => $songData) {
                    $songString[] = array('songID' => $songID, 'songName' => $songData['songName'], 'artistName' => $songData['artistName']);
                }
                if (empty($songString) || empty($playlistInfo['numSongs'])) {
                    // Remove empty playlists
                    unset($userPlaylists[$playlistID]);
                } else {
                    $songString = json_encode($songString);
                    print "<tr class='$rowClass'>
                               <td class='gsTableButton'><a title='Add This Playlist To Your Post' class='gsAdd' name='$playlistID' onclick='addToSelectedPlaylist(this);' style='cursor: pointer'>$songString<a></td>
                               <td class='gsTableButton'><a title='Show All Songs In This Playlist' class='gsShow' name='$playlistID' style='cursor: pointer' onclick='showPlaylistSongs(this);'>$songString</a></td>
                               <td><pre class='$gsPreClass'>{$playlistInfo['name']} ({$playlistInfo['numSongs']})</pre></rd>
                          </tr>";
                }
            }
        } else {
            print "<tr><td>No playlists were found. When you create playlists on Grooveshark, they will show up here. If you have playlists on Grooveshark, reload this page.</td></tr>";
        }
        print "</table>";
    }
    print "</div>"; // End playlist div
    //The selected songs div: dynamically updated with the songs the user wants to add to their post
    print "
    <div id='selected-song' class='$songClass'>
	<div id='selected-songs-header'>
		<a title='Remove All Your Selected Songs' href=\"javascript:;\" onmousedown=\"clearSelected();\" id='clearSelected'>Clear All</a>
		<h4 id='selectedCount'>Selected Songs (0):</h4>
	</div>
	<table id='selected-songs-table'></table>
    </div>
    </div>"; // Ends selected songs div and the song selection (search, favorites, playlists, selected) div
    //The appearance div: customizes options for displaying the widget 
    $widgetWidth = (!isset($gs_options['widgetWidth'])) ? 250 : $gs_options['widgetWidth'];
    $widgetHeight = (!isset($gs_options['widgetHeight'])) ? 400 : $gs_options['widgetHeight'];
    $displayPhrase = ((!isset($gs_options['displayPhrase'])) || ($gs_options['displayPhrase'] == '')) ? 'Grooveshark' : $gs_options['displayPhrase'];
    print "
<a title='Toggle Showing Appearance Options' id='jsLink' href=\"javascript:;\" onmousedown=\"gsToggleAppearance();\">&darr; Appearance</a>
<div id='gsAppearance' class='gsAppearanceHidden'>
    <h2 id='gsAppearanceHead'>Customize the Appearance of Your Music</h2>
    <ul class='gsAppearanceOptions'>
        <li>
            <span class='key'>Display Music As:</span>
            <span class='value'>
                <input tabindex='103' type='radio' name='displayChoice' value='link' onclick='changeAppearanceOption(this.value)'>&nbsp; Link</input><br/>
                <input tabindex='103' type='radio' name='displayChoice' value='widget' onclick='changeAppearanceOption(this.value)' checked>&nbsp; Widget</input>
            </span>
        </li>
        <li>
            <span class='key'>Position Music At:</span>
            <span class='value'>
                <input id='gsPosition' tabindex='104' type='radio' name='positionChoice' value='beginning'>&nbsp; Beginning of Post</input><br/>
                <input tabindex='104' type='radio' name='positionChoice' value='end' checked>&nbsp; End of Post</input>
            </span>
        </li>
        <li>
            <span class='key'>Add to Sidebar:</span>
            <span class='value'>
                <input tabindex='105' type='radio' name='sidebarChoice' value='yes'>&nbsp; Yes (will override current Grooveshark Sidebar)</input><br />
                <input tabindex='105' type='radio' name='sidebarChoice' value='no' checked>&nbsp; No</input>
            </span>
        </li>
        <li>
            <span class='key'>Add to Dashboard:</span>
            <span class='value'>
                <input tabindex='105' type='radio' name='dashboardChoice' value='yes'>&nbsp; Yes (will override current Grooveshark Dashboard)</input><br />
                <input tabindex='105' type='radio' name='dashboardChoice' value='no' checked>&nbsp; No</input>
            </span>
        </li>
        <li>
            <span class='key'><label for='playlistsName'>Playlist Name:</label></span>
            <span class='value''>
                <input tabindex='105' type='text' name='playlistsName' id='playlistsName' value='Grooveshark Playlist' onchange='changeExamplePlaylist(this)' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {changeExamplePlaylist(this); return false;} else return true;'/><span id='displayPhrasePlaylistExample'>Example: \"$displayPhrase: Grooveshark Playlist\"</span>
            </span>
        </li>
        <li style='display:none' id='gsDisplayLink'>
            <span class='key'><label for='displayPhrase'>Link Display Phrase:</label></span>
            <span class='value'>
                <input tabindex='106' type='text' name='displayPhrase' id='displayPhrase' value='$displayPhrase' onchange='changeExample(this)' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {changeExample(this); return false;} else return true;'/><span id='displayPhraseExample'>Example: \"$displayPhrase: song by artist\"</span>			
            </span>
        </li>
    </ul>
    <div id='gsDisplayWidget'>
        <h2 id='playlistAppearance'>Appearance of Multi-Song Widgets</h2>
        <ul class='gsAppearanceOptions'>
            <li>
                <span class='key'><label for='widgetWidth'>Widget Width:</label></span>
                <span class='value''>
                    <input tabindex='107' type='text' name='widgetWidth' id='widgetWidth' value='250' onkeydown='if((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {checkWidgetValue(); return false;} else return true;'/></td><td><span>Range: 150px to 1000px</span>
                </span>
            </li>	
            <li>
                <span class='key'><label for='widgetHeight'>Widget Height:</label></span>
                <span class='value'>
                    <input tabindex='108' type='text' name='widgetHeight' id='widgetHeight' value='176' ononkeydown='if((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {checkWidgetValue(); return false;} else return true;' /></td><td><span>Range: 150px to 1000px</span>
                </span>
            </li>
            <li>
                <span class='key'><label>Color Scheme:</label></span>
                <span class='value'>
                    <select tabindex='109' type='text' onchange='changeColor(this.form.colorsSelect);' id='colors-select' name='colorsSelect'>
    ";
    // Customize the color scheme of the widget
    $colorScheme = $gs_options['colorScheme']; //use this to save the user's colorscheme preferences
    $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
    foreach ($colorsArray as $id => $colorOption) {
        print "<option value='$id' ";
        if ($i == $colorScheme) {
            print "selected ";
        }
        print ">$colorOption</option>";
    }
print "
                    </select>
                    <div class='clear'></div>
                    <br/>
                    <div class='gsColorBlockContainer'>
                        Base
                        <div style='background-color: #777777' id='base-color' class='gsColorBlock'></div>
                    </div>
                    <div class='gsColorBlockContainer'>
                        Primary
                        <div style='background-color: rgb(255,255,255)' id='primary-color' class='gsColorBlock'></div>
                    </div>
                    <div class='gsColorBlockContainer'>
                        Secondary
                        <div style='background-color: rgb(102,102,102)' id='secondary-color' class='gsColorBlock'></div>
                    </div>
                </span>
            </li>
        </ul>
        <div class='clear'></div>
        <div id='gsWidgetExample'></div>
    </div>
    <div class='clear'></div>
</div>
";
//Closes the Grooveshark box div: gives two display options and the save button
print "
       <table id='gsSave'>
       <tr>
       <td>
       <input tabindex='110' type='button' class='button-primary button' value='Save Music' id='save-post' name='save' onclick='gsAppendToContent(this)'/>
       </td>
       </tr>
       </table>
       <!--[if IE 7]>
       </div>
       <![endif]-->";
}

function oldGroovesharkBox() 
{
    print "<div class='dbx-b-ox-wrapper'>
         <fieldset id='groovesharkdiv' class='dbx-box'>
         <div class='dbx-h-andle-wrapper'>
         <h3 class='dbx-handle'>
         Add Music
         </h3>
         </div>
         <div class='dbx-c-ontent-wrapper'>
         <div class='dbx-content'>";
    groovesharkBox();
    print "</div>
           </div>
           </fieldset>
           </div>";
}

function add_gs_options_page() 
{
    add_options_page('Grooveshark Options', 'Grooveshark', 8, basename(__FILE__), 'grooveshark_options_page');
}

// Registers the action to add the options page for the plugin.
add_action('admin_menu', 'add_gs_options_page');

add_action('admin_print_scripts', 'groovesharkcss');

function groovesharkcss() 
{
    print "<link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark.css' />\n";
    print "<!--[if IE]><link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark-ie.css' /><![endif]-->\n";
    if (is_admin()) {
        $wpurl = get_bloginfo('wpurl');
        //wp_enqueue_script('jquery126',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/jquery-1.2.6.js');
        wp_enqueue_script('jquery');
        //wp_enqueue_script('jqueryElementReady', get_bloginfo('wpurl') . '/wp-content/plugins/grooveshark/js/jquery.elementReady.js');
        //$gs_options = get_option('gs_options');
        //wp_enqueue_script('swfobject', get_bloginfo('wpurl') . '/wp-content/plugins/grooveshark/js/swfobject.js');
        //wp_enqueue_script('player', get_bloginfo('wpurl') . '/wp-content/plugins/grooveshark/js/player.js');
        wp_enqueue_script('gsjson', $wpurl . '/wp-content/plugins/grooveshark/js/gsjson.js', array(), false, true);
        wp_enqueue_script('playback', $wpurl . '/wp-content/plugins/grooveshark/js/playback.js');
        wp_enqueue_script('tablednd', $wpurl . '/wp-content/plugins/grooveshark/js/tablednd.js', array(), false, true);
        wp_enqueue_script('grooveshark', $wpurl . '/wp-content/plugins/grooveshark/js/grooveshark.js', array(), false, true);
    }
}


// Code for Sidebar Widget
function groovesharkSidebarContent($args) {
    $gs_options = get_option('gs_options'); // Embed code is saved in the gs_options array
    if (!empty($gs_options['sidebarPlaylists'])) {
        print $args['before_widget'] . $args['before_title'] . 'Grooveshark Sidebar' . $args['after_title'] . $gs_options['sidebarPlaylists']['embed'] . $args['after_widget'];
    }
}

function groovesharkDashboardContent($args) {
    $gs_options = get_option('gs_options');
    if (!empty($gs_options['dashboardPlaylists'])) {
        print $gs_options['dashboardPlaylists']['embed'];
    }
}

function groovesharkRssContent($args) {
    $gs_options = get_option('gs_options');
    $wpurl = get_bloginfo('wpurl');
    if (!empty($gs_options['sidebarRss'])) {
        if (!empty($gs_options['sidebarRss']['favorites'])) {
            print $args['before_widget'] . $args['before_title'] . 
                  "<a class='rsswidget' title='Syndicate this content' href='{$gs_options['sidebarRss']['favorites']['url']}'>
                       <img width='14' height='14' alt='RSS' src='$wpurl/wp-includes/images/rss.png' style='border: medium none; background: orange none repeat scroll 0% 0%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: white;'/>
                   </a>
                   <a class='rsswidget' title='Last 100 favorited songs on Grooveshark by me' href='{$gs_options['sidebarRss']['favorites']['url']}'>
                       {$gs_options['sidebarRss']['favorites']['title']}
                   </a>"
                    . $args['after_title'];
            // RSS feed content goes here IF the user's wordpress has fetch_feed
            if (function_exists('fetch_feed')) {
                $favoritesFeed = fetch_feed($gs_options['sidebarRss']['favorites']['url']);
                if ($favoritesFeed instanceof WP_Error) {
                    // Display an error message to the visitor
                    print "<p>This feed is currently unavailable. Please try again later.</p>";
                    // Attempt to get the correct feed
                    $gsapi = GSAPI::getInstance();
                    $gsapi->loginViaAuthToken($gs_options['token']);
                    $gs_options['sidebarRss']['favorites']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gsapi->getUsername()) . "/recent_favorite_songs.rss";
                    update_option('gs_options', $gs_options);
                } elseif ($gs_options['sidebarRss']['count'] > 0) {
                    // Add the 
                    print "<ul>";
                    $count = 0;
                    $limit = $gs_options['sidebarRss']['count'];
                    $displayContent = $gs_options['sidebarRss']['displayContent'];
                    foreach ($favoritesFeed->get_items() as $item) {
                        $count++;
                        if ($count <= $limit) {
                            print "<li>
                                      <a class='rsswidget' target='_blank' title='{$item->get_description()}' href='{$item->get_permalink()}'>{$item->get_title()}</a>";
                            if ($displayContent) {
                                print "<div class='rssSummary'>{$item->get_description()}</div>";
                            }
                            print "</li>";
                        }
                    }
                    print "</ul>";
                }
            }
            print $args['after_widget'];
        }
        if (!empty($gs_options['sidebarRss']['recent'])) {
            print $args['before_widget'] . $args['before_title'] . 
                  "<a class='rsswidget' title='Syndicate this content' href='{$gs_options['sidebarRss']['recent']['url']}'>
                      <img width='14' height='14' alt='RSS' src='$wpurl/wp-includes/images/rss.png' style='border: medium none; background: orange none repeat scroll 0% 0%; -moz-background-clip: border; -moz-background-origin: padding; -moz-background-inline-policy: continuous; color: white;'/>
                   </a>
                   <a class='rsswidget' title='Last 100 song plays over 30 seconds on Grooveshark by me' href='{$gs_options['sidebarRss']['recent']['url']}'>
                       {$gs_options['sidebarRss']['recent']['title']}
                   </a> " 
                  . $args['after_title'];
            // RSS feed content goes here IF the user's wordpress has fetch_feed
            if (function_exists('fetch_feed')) {
                $recentFeed = fetch_feed($gs_options['sidebarRss']['recent']['url']);
                if ($recentFeed instanceof WP_Error) {
                    // Display an error message
                    print "<p>This feed is currently unavialable. Please try again later.</p>";
                    $gsapi = GSAPI::getInstance();
                    $gsapi->loginViaAuthToken($gs_options['token']);
                    $gs_options['sidebarRss']['recent']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gsapi->getUsername()) . "/recent_listens.rss";
                    update_option('gs_options', $gs_options);
                } elseif ($gs_options['sidebarRss']['count'] > 0) {
                    print "<ul>";
                    $count = 0;
                    $limit = $gs_options['sidebarRss']['count'];
                    $displayContent = $gs_options['sidebarRss']['displayContent'];
                    foreach ($recentFeed->get_items() as $item) {
                        $count++;
                        if ($count <= $limit) {
                            print "<li>
                                       <a class='rsswidget' target='_blank' title='{$item->get_description()}' href='{$item->get_permalink()}'>{$item->get_title()}</a>";
                            if ($displayContent) {
                                print "<div class='rssSummary'>{$item->get_description()}</div>";
                            }
                            print "</li>";
                        }
                    }
                    print "</ul>";
                }
            }
            print $args['after_widget'];
        }
    }
}

// Widget code
function groovesharkSidebarInit() {
    $gs_options = get_option('gs_options');
    wp_register_sidebar_widget('groovesharkSidebar', 'Grooveshark Sidebar', 'groovesharkSidebarContent');
    register_widget_control('groovesharkSidebar', 'groovesharkSidebarOptions');
}

function groovesharkDashboardInit() {
    $gs_options = get_option('gs_options');
    if (!empty($gs_options['dashboardPlaylists'])) {
        if (function_exists('wp_add_dashboard_widget')) {
            wp_add_dashboard_widget('groovesharkDashboard', 'Grooveshark Dashboard', 'groovesharkDashboardContent');
        }
    }

}

function groovesharkRssInit() {
    $gs_options = get_option('gs_options');
    wp_register_sidebar_widget('groovesharkRss', 'Grooveshark RSS', 'groovesharkRssContent');
    register_widget_control('groovesharkRss', 'groovesharkRssOptions');
}

function groovesharkRssOptions() {
    $gs_options = get_option('gs_options');
    $didSave = 0;
    print "<input type='hidden' id='groovesharkSidebarRssBox' value=''/>";
    if (isset($_POST['groovesharkRss-submit'])) {
        // Update the saved options
        $didSave = 1;
        $gsapi = GSAPI::getInstance();
        $gsapi->loginViaAuthToken($gs_options['token']);
        if (isset($_POST['gsFavoritesFeed'])) {
            $gs_options['sidebarRss']['favorites']['title'] = ($_POST['gsFavoritesTitle'] != '') ? $_POST['gsFavoritesTitle'] : 'My Favorite Songs 2';
            $gs_options['sidebarRss']['favorites']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gsapi->getUsername()) . "/recent_favorite_songs.rss";
        } else {
            $gs_options['sidebarRss']['favorites'] = array();
        }
        if (isset($_POST['gsRecentFeed'])) {
            $gs_options['sidebarRss']['recent']['title'] = ($_POST['gsRecentTitle'] != '') ? $_POST['gsRecentTitle'] : 'My Recent Songs';
            $gs_options['sidebarRss']['recent']['url'] = "http://api.grooveshark.com/feeds/1.0/users/" . strtolower($gsapi->getUsername()) . "/recent_listens.rss";
        } else {
            $gs_options['sidebarRss']['recent'] = array();
        }
        $gs_options['sidebarRss']['count'] = $_POST['gsNumberOfItems'];
        $gs_options['sidebarRss']['displayContent'] = isset($_POST['gsDisplayContent']) ? 1 : 0;
        update_option('gs_options', $gs_options);
    }
    // Have the configuration options here
    print "<h3>Grooveshark RSS Widget</h3>";
    if ($gs_options['userID'] == 0) {
        print "<h4>You must save your login information to display your Grooveshark RSS feeds in the <a href='" . get_option('siteurl') . "/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a>.</h4>";
    } else {
        if ($didSave) {
            print "<h4>Your RSS settings have been saved.</h4>";
        } else {
            print "<h4>Choose how you want your Grooveshark RSS feeds to appear on your sidebar</h4>";
        }
        print "<input name='groovesharkRss-submit' type='hidden' value='1' />
               <ul>
                   <li class='gsTr26'><label><input type='checkbox' name='gsFavoritesFeed' " . (empty($gs_options['sidebarRss']['favorites']) ? '' : " checked='checked'") . "/>&nbsp; Enable Favorites Feed?</label></li>
                   <li><label>Title for Favorites Feed: <input type='text' name='gsFavoritesTitle' value='" . (empty($gs_options['sidebarRss']['favorites']) ? '' : $gs_options['sidebarRss']['favorites']['title']) ."'/></label></li>
                   <li class='gsTr26'><label><input type='checkbox' name='gsRecentFeed' " . (empty($gs_options['sidebarRss']['recent']) ? '' : " checked='checked'") . "/>&nbsp; Enable Recent Songs Feed?</label></li>
                   <li><label>Title for Recent Songs Feed: <input type='text' name='gsRecentTitle' value='" . (empty($gs_options['sidebarRss']['recent']) ? '' : $gs_options['sidebarRss']['recent']['title']) . "'/></label></li>
                   <li class='gsTr26'><label>How many items would you like to display: <select name='gsNumberOfItems' type='text'>";
        for ($i = 0; $i <= 20; $i++) {
            print "<option value='$i' " . ($gs_options['sidebarRss']['count'] == $i ? "selected='selected'" : '') . ">$i</option>";
        }
        print "</select></label></li>
               <li><label><input type='checkbox' name='gsDisplayContent' " . ($gs_options['sidebarRss']['displayContent'] ? "checked='checked'" : '' ) . "/>&nbsp; Display Item Content?</label></li>
               </ul>";
    }
}

function groovesharkSidebarOptions() {
    $gsapi = GSAPI::getInstance();
    $gs_options = get_option('gs_options');
    $didSave = 0;
    print "<input type='hidden' id='groovesharkSidebarOptionsBox' value=''/>";
    if (isset($_POST['groovesharkWidget-submit'])) {
        // Update the saved options
        if ($_POST['selectedPlaylist'] == -1) {
            $gs_options['sidebarPlaylists'] = array();
            $didSave = 1;
        } else {
            $colorScheme = $_POST['colorsSelect'];
            $color1 = '000000';
            $color2 = 'FFFFFF';
            $color3 = '666666';
            // Change colors for selected color scheme
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
            $widgetWidth = $_POST['sidebarWidgetWidth'];
            $widgetHeight = $_POST['sidebarWidgetHeight'];
            $playlistID = $_POST['selectedPlaylist'];
            $embedCode = $gsapi->playlistGetWidgetEmbedCode($playlistID, $widgetWidth, $widgetHeight, 'Sidebar Widget', $color2, $color1, $color1, $color3, $color2, $color1, $color3, $color2, $color2, $color1, $color3, $color2, $color2, $color3, $color2);
            $gs_options['sidebarPlaylists'] = array('id' => $playlistID, 'embed' => $embedCode);
            $didSave = 1;
        }
        update_option('gs_options', $gs_options);
    }
    $sidebarPlaylist = $gs_options['sidebarPlaylists'];
    if (isset($sidebarPlaylist['id'])) {
        $sidebarId = $sidebarPlaylist['id'];
    } else {
        $sidebarId = -1;
    }
    $sidebarId = (int)$sidebarId;
    print "<h3>Grooveshark Sidebar</h3>
           <ul><li>";
    if ($didSave) {
        print "<h4>Your playlist has been saved.</h4>";
    } else {
        print "<h4>Choose one of your playlists to add to your sidebar</h4>";
    }
    /*
    // This experimental functionality not worked out yet for the sidebar options
    print "<h4>You can also create a new playlist by searching for songs:</h4>";
    groovesharkSmallBox();
    */
    $playlistsTotal = 0;
    print "</li><li class='gsTr26'><label><input type='radio' name='selectedPlaylist' value='-1'>Clear Sidebar</input></label></li>";
    $userPlaylists = $gs_options['userPlaylists'];
    if (!empty($userPlaylists)) {
        // If the user has saved playlists
        foreach ($userPlaylists as $playlistID => $playlistInfo) {
            // Retrieve relevant information and print the list item containing playlist information
            $playlistsTotal++;
            $checked = ($sidebarId == $playlistID) ? "checked='checked'" : "";
            $playlistName = $playlistInfo['playlistInfo']['name'];
            $playlistSongs = $playlistInfo['playlistInfo']['numSongs'];
            if (!empty($playlistName) && !empty($playlistSongs)) {
                $playlistNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]([a-zA-Z0-9]?)/", "$1_$2", $playlistName, -1);
                $playlistURL = "http://listen.grooveshark.com/playlist/$playlistNameUrl/$playlistID";
                $class = ($playlistsTotal % 2) ? '' : 'gsTr26';
                print "<li class='$class'>
                          <label><input type='radio' onclick='groovesharkUpdateChoice(this);' class='$playlistSongs' id='playlist-$playlistID' name='selectedPlaylist' value='$playlistID' $checked>$playlistName ($playlistSongs)</input></label>
                          <a target='_blank' href='$playlistURL'>&rarr;</a>
                        </li>";
            } else {
                unset($userPlaylists[$playlistID]);
            }
        }
        $gs_options['userPlaylists'] = $userPlaylists;
        update_option('gs_options', $gs_options);
    } else {
        // No playlists, notify user on how to save playlists
        print "<li>You do not have any playlists available.</li><li>You must either provide your login information in the <a href='" . get_option('siteurl') . "/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a> or save a playlist to one of your posts to see it here.</li>";
    }

    print "</ul>
    <input name='groovesharkWidget-submit' type='hidden' value='1' />
    <h3>Appearance Options</h3>
    <input type='hidden' id='sidebarDataStore' value='-1'>
    <ul>
    <li class='gsTr26'><label>Widget Width (px): <input type='text' name='sidebarWidgetWidth' id='gsSidebarWidgetWidth' value='200' onchange='changeSidebarWidth(this);'/></label></li>
    <li><label>Widget Height (px): <input type='text' name='sidebarWidgetHeight' id='gsSidebarWidgetHeight' value='400' onchange='changeSidebarHeight(this);'/></label></li>
    <li class='gsTr26'><label>Color Scheme: <select type='text' onchange='changeSidebarColor(this.form.colorsSelect)' name='colorsSelect'>";
    // Customize the color scheme of the widget
    $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
    foreach ($colorsArray as $id => $colorOption) {
        print "<option value='$id'>$colorOption</option>";
    }
    print "</select></label>
           
           <div class='gsColorBlockContainer'>
               Base
               <div style='background-color: #777777' id='widget-base-color' class='gsColorBlock'></div>
           </div>
           <div class='gsColorBlockContainer'>
               Primary
               <div style='background-color: #FFFFFF' id='widget-primary-color' class='gsColorBlock'></div>
           </div>
           <div class='gsColorBlockContainer'>
               Secondary
               <div style='background-color: rgb(102, 102, 102)' id='widget-secondary-color' class='gsColorBlock'></div>
            </div>
            </li></ul>
            <div style='clear:both'></div>";
}

add_action('plugins_loaded', 'groovesharkSidebarInit');
add_action('plugins_loaded', 'groovesharkRssInit');
add_action('wp_dashboard_setup', 'groovesharkDashboardInit');


/* 
//Comment Related code
// Registers the filter to add music to the comment, and the action to show the search box
// Remind users that to enable this option, their template must display comment_form. Also, for modification in the comments.php file or in themes:
// Add <?php do_action(’comment_form’, $post->ID); ?> just above </form> ending tag in comments.php. Save the file.
*/
add_action('comment_form','groovesharkCommentBox');
add_filter('preprocess_comment','gs_appendToComment'); 

function groovesharkCommentBox() {
    $gs_options = get_option('gs_options');
    if ($gs_options['musicComments'] == 1) {
        $wpurl = get_bloginfo('wpurl');
        print "<link type='text/css' rel='stylesheet' href='$wpurl/wp-content/plugins/grooveshark/css/grooveshark.css'></link>\n
               <script type='text/javascript' src='$wpurl/wp-content/plugins/grooveshark/js/jquery-1.2.6.js'></script>\n
               <script type='text/javascript' src='$wpurl/wp-content/plugins/grooveshark/js/tablednd.js'></script>\n
               <script type='text/javascript' src='$wpurl/wp-content/plugins/grooveshark/js/grooveshark.js'></script>\n";
        groovesharkSmallBox();
    }
}

function groovesharkSmallBox() {
    // Retrieves saved options for the plugin
    $gs_options = get_option('gs_options');
    if ($gs_options['APIKey'] == 0) {
        return;
    }
    // Get a GSAPI object for API calls
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $siteurl = get_option('siteurl'); // used to provide links to js/images
    $version = get_bloginfo('version'); // used to load fixes specific to certain WP versions
    $isVersion26 = stripos($version, '2.6') !== false;
    $isVersion25 = stripos($version, '2.5') !== false;
    //get_bloginfo('wpurl') . '/wp-content/plugins/grooveshark/js/jquery.elementReady.js
    // The basic code to display the postbox. The ending tags for div are at the end of the groovesharkBox() function
    print "<input type='hidden' name='sessionID' value='$sessionID' />
           <input type='hidden' id='isSmallBox' name='isSmallBox' value='1' />
           <input type='hidden' name='widgetHeight' id='widgetHeight' value='176' />
		   <!--[if IE 7]>
		   <div id='IE7'>
		   <![endif]-->
            <h3>Add Music To Your Comment</h3>";
    // Sets up the tabs for "search," "favorites," and "playlists."
    $tabClass = 'gsTabActive27';
    $tabClass2 = 'gsTabInactive27';
    $tabContainerClass = 'gsTabContainer27';
    $songClass = 'gsSongBox27';
    $versionClass = 'gs27';
    if ($isVersion26 || $isVersion25) {
        $tabContainerClass = 'gsTabContainer26';
        $tabClass = 'gsTabActive26';
        $tabClass2 = 'gsTabInactive26';
        $songClass = 'gsSongBox26';
        $versionClass = 'gs26';
    }
    $autosaveMusic = $gs_options['autosaveMusic'];
    print "<div id='gsSongSelection'>
                <input type='hidden' id='gsDataStore'/>
                <input type='hidden' name='autosaveMusic' id='autosaveMusic' value='$autosaveMusic' />
                <input type='hidden' name='songIDs' id='songIDs' value='0' />";
    $userID = $gs_options['userID'];
    $token = $gs_options['token'];
    //Check if the user has registered (0 for no, 1 for yes and logged in)
    $userCheck = 0;
    if ((($userID != '') && ($userID != 0))) {
        $userCheck = 1;
    }
    // The keywords search div
    print "
	<div id='songs-search' class='$songClass' style='display: block;'>
            <div id='searchInputWrapper'>
                    <div id='searchInput'>
                        <input tabindex='100' id='gs-query' type='text' name='gs-query' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {document.getElementById(\"gsSearchButton\").click(); return false;} else return true;' value='Search For A Song' class='empty' />
                        <input type='hidden' name='gsSessionID' value='$sessionID' id='gsSessionID' />
                        <input type='hidden' name='gsLimit' value='{$gs_options['numberOfSongs']}' id='gsLimit' />
                        <input type='hidden' name='gsBlogUrl' value='$siteurl' id='gsBlogUrl' />
                        <input type='hidden' name='wpVersion' value='$version' id='wpVersion' />
                    </div>
            </div>
            <div id='searchButton'>
                <input tabindex='101' type='button' name='editPage' id='gsSearchButton' value='Search' class='button gsSmallButton' onclick=\"gsSearch(this)\" />
            </div>
            <div class='clear' style='height:0;' /></div>
	</div>
	<div class='clear' style='height:0'></div>
	<div id='search-results-container' class='$versionClass' style='display:none;'>
            <div id='search-results-header'>
                <h4 id='queryResult'></h4>
            </div>
            <div id='search-results-wrapper'>
                <table id='save-music-choice-search' style='display:none'></table>
            </div>
	</div>
	    ";
    //The selected songs div: dynamically updated with the songs the user wants to add to their post
    $commentSongLimit = $gs_options['commentSongLimit'];
    $limitMessage = '';
    if (($commentSongLimit != 0) && ($gs_options['commentDisplayOption'] == 'widget')) {
        $limitMessage = "Allowed A Maximum Of $commentSongLimit";
    }
    print "
    <div id='selected-song' class='$songClass'>
	<div id='selected-songs-header'>
		<a href=\"javascript:;\" onmousedown=\"clearSelected();\" id='clearSelected'>Clear All</a>
		<h4 id='selectedCount'>Selected Songs (0): $limitMessage</h4>
	</div>
	<table id='selected-songs-table'></table>
    </div>
    </div>"; // Ends selected songs div and the song selection (search, favorites, playlists, selected) div
    //The appearance div: customizes options for displaying the widget 
//Closes the Grooveshark box div: gives two display options and the save button
print "
       <table id='gsSave'>
       <tr>
       <td>
       <input tabindex='110' type='button' class='button-primary button gsAppendToComment' value='Save Music' title='Append Music To Your Comment' id='save-post' name='save' onclick='gsAppendToComment(this)'/>
       </td>
       </tr>
       </table>
       <!--[if IE 7]>
       </div>
       <![endif]-->";
}

function gs_appendToComment($data) {
    $gsContent = '';
    //Processing Code
    $data['comment_content'] .= $gsContent;
    return $data;
}
// Note: This would serve the same role as the gs_autosaveMusic has for admin post and page edit.
// Consider adding a new javascript function for this that appends the music to the comment dynamically.
//End of Comment Related Code


function gs_autosaveMusic($content) 
{
    // Get a GSAPI object for API calls in the gs_autosaveMusic function
    $gsapi = GSAPI::getInstance();
    $gsapi->logout();
    $sessionID = $gsapi->getSessionID();
    $gs_options = get_option('gs_options');
    $savedAutosave = $gs_options['autosaveMusic'];
    $autosaveMusic = isset($_POST['autosaveMusic']) ? ($_POST['autosaveMusic'] && $savedAutosave) : 0;
    $gs_options = get_option('gs_options');
    $APIKey = $gs_options['APIKey'];
    if (($APIKey == 0) || ($autosaveMusic == 0)) {
        // Cannot autosave music, just return content unmodified
        return $content;
    }
    //Here goes all the content preparation
    $songsArray = $_POST['songsInfoArray'];
    if (count($songsArray) <= 0) {
        // No songs to save, so return content unmodified
        return $content;
    }
    // Determine the display option
    $displayOption = isset($_POST['displayChoice']) ? ($_POST['displayChoice'] ? 'widget' : 'link') : 'widget';
    $sidebarOption = isset($_POST['sidebarChoice']) ? ($_POST['sidebarChoice'] ? 1 : 0) : 0;
    $dashboardOption = isset($_POST['dashboardChoice']) ? ($_POST['dashboardChoice'] ? 1 : 0) : 0;
    // Determine widget width and height or set defaults
    $widgetWidth = (isset($_POST['widgetWidth']) && ($_POST['widgetWidth'] != '')) ? $_POST['widgetWidth'] : 250;
    $widgetHeight = (isset($_POST['widgetHeight']) && ($_POST['widgetHeight'] != '')) ? $_POST['widgetHeight'] : 400;
    // Determine other information needed to save music
    $colorScheme = $_POST['colorsSelect'];
    $displayPhrase = (isset($_POST['displayPhrase']) && ($_POST['displayPhrase'] != '')) ? $_POST['displayPhrase'] : 'Grooveshark'; 
    $playlistName = $_POST['playlistsName'];
    $userID = $gs_option['userID'];
    $token = $gs_option['token'];
    $includePlaylists = $gs_option['includePlaylists'];
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
    $gs_options['widgetWidth'] = $widgetWidth;
    $gs_options['widgetHeight'] = $widgetHeight;
    $gs_options['displayPhrase'] = $displayPhrase;
    $gs_options['colorScheme'] = $colorScheme;
    $gs_options['songsArray'] = $songsArray;
    // Update saved options with new user options
    update_option('gs_options',$gs_options);
    $music = ($displayOption == 'widget') ? "<div id='gsWidget'>" : "<div id='gsLink'>";
    if (count($songsArray) == 1) {
        // Single-song widget/link
        $songID = $songsArray[0];
        if ($displayOption == 'widget') {
            // Gets the widget embed code (function returns the error message if needed so no need to check for it here)
            $widgetCode = $gsapi->songGetWidgetEmbedCode($songID, $widgetWidth);
            if ((!(bool)stripos($widgetCode, 'Error')) && ($sidebarOption)) {
                // If no error code and user wants to save widget, save the widget to the sidebar
                $gs_options['sidebarPlaylists'] = array('id' => $songID, 'embed' => preg_replace("/width=\"\d+\"/", "width=\"200\"", $widgetCode));
                update_option('gs_options', $gs_options);
            }
            if ((!(bool)stripos($widgetCode, 'Error')) && ($dashboardOption)) {
                $gs_options['dashboardPlaylists'] = array('id' => $songID, 'embed' => $widgetCode);
                update_option('gs_options', $gs_options);
            }
            $music .= $widgetCode;
        }
        if ($displayOption == 'link') {
            // Gets the information needed for song link
            $songArray = $gsapi->songAbout($songID);
            if (isset($songArray['error'])) {
                $music .= 'Error Code ' . $songArray['error'] . '. Contact author for support.';
            } else {
                $songName = $songArray['songName'];
                $songNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]+([a-zA-Z0-9]?)/", "$1_$2", $songName, -1);
                $artistName = $songArray['artistName'];
                // Make the anchor tag to link to the song
                $liteUrl = "<a target='_blank' href='http://listen.grooveshark.com/song/$songNameUrl/$songID'>$displayPhrase: $songName by $artistName</a>";
                $music .= $liteUrl;
            }
        }
    } else {
        if ((($token != 0) && ($includePlaylists != 0))) {
            // If the user is logged in and wants their playlists to be saved with their account on GS
            $gsapi->loginViaAuthToken($token);
        }
        // Get the playlist name and ID
        $playlistName = (!isset($playlistName) || ($playlistName == '')) ? 'Grooveshark Playlist' : $playlistName;
        $playlistID = $gsapi->playlistCreate($playlistName);
        if (isset($playlistID['error'])) {
            $music .= 'Error Code ' . $playlistID['error'] . '. Contact author for support.';
        }
        $playlistID = $playlistID['playlistID'];
        // Add all songs to the playlist
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
        if ($numSongs > 0) {
            $gs_options['userPlaylists'][$playlistID]['playlistInfo'] = array('name' => $playlistName, 'numSongs' => $numSongs);
            update_option('gs_options', $gs_options);
        }
        // Prepare playlist link
        $playlistNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]([a-zA-Z0-9]?)/", "$1_$2", $playlistName, -1);
        $playlistURL = "http://listen.grooveshark.com/playlist/$playlistNameUrl/$playlistID";
        $playlistLiteUrl = "<a target='_blank' href='$playlistURL'>$displayPhrase: $playlistName</a>";
        if ($displayOption == 'widget') {            
            // Default colors
            $color1 = '000000';
            $color2 = 'FFFFFF';
            $color3 = '666666';
            // Change colors for selected color scheme
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
            //       you can change all the colors here on line 630. See lines 327-347 in GSAPI.php for descriptions of parameters.
            //       ALSO, all color parameters must be hex codes of the rgb colors WITHOUT prefixes ('FFFFFF' for white, 'FF0000' for red, '00FF00' for blue, etc).
            //       FINALLY, if you do change it to custom colors, comment out lines 518-625 in this file to save some processing time.
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
            $music .= $embedCode;
            $music .= "<input type='hidden' id='gsPlaylistID' value='$playlistID'/>";
        } elseif ($displayOption == 'link') {
            // The playlist link just displays the playlist name after the display phrase
            $music .= $playlistLiteUrl;
        }
    }
    if ((($displayOption == 'widget') || ($displayOption == 'link'))) {
        $music .= "</div>";
    }
    /*
    if ($displayOption == 'widget') {
        //remove the old widget
        $contentPattern = '/(?<prewidget>.+)\<div id\=\'gsWidget\'\>.+\<\/div\>(?<postwidget>.+)/';
        preg_match($contentPattern, $content, $contentMatches);
        $content = $contentMatches['prewidget'] . $contentMatches['postwidget'];
        foreach($contentMatches as $item) {
            $content .= "::$item::";
        }
    }
    */
    // Append all music embed code to the post content and return
    $content .= $music;
    return $content;
}

add_filter('content_save_pre', 'gs_autosaveMusic');

// widget code
add_action('widgets_init', 'groovesharkSidebarLoad');

function groovesharkSidebarLoad() {
    //register_widget('groovesharkSidebar');
}

// The function to display the options page.
function grooveshark_options_page() {
    $gsapi = GSAPI::getInstance();
    $sessionID = $gsapi->getSessionID();
    $errorCodes = array();
    $gs_options = get_option('gs_options');
    $settingsSaved = 0;
    // If the user wants to update the options...
    if ((($_POST['status'] == 'update') || ($_POST['Submit'] == 'Enter'))) {
        $updateOptions = array();
        $username = $_POST['gs-username'];
        $password = $_POST['gs-password'];
        /* If a username and password was entered, checks to see if they are valid via the 
        session.loginViaAuthToken method. If they are valid, the userID and token are retrieved and saved. */
        if (((isset($username) and $username != '') && (isset($password) and $password != ''))) {
            $userID = 0;
            $token = 0;
            $result = $gsapi->createUserAuthToken($username, $password);
            if (isset($result['error'])) {
                $errorCodes[] = $result['error'];
            }
            if (isset($result['userID'])) {
	        $userID = $result['userID'];
                $token = $result['token'];
            }
            $updateOptions += array('userID' => $userID, 'token' => $token);
        }
        // Sets the number of songs the user wants to search for. If no number was enter, it just saved the default (30).
        $numberOfSongs = $_POST['numberOfSongs'];
        if (!isset($numberOfSongs)) {
            $numberOfSongs = 30;
        }
        $updateOptions += array('numberOfSongs' => $numberOfSongs);
        // Sets the display option for comment music
        $commentDisplayOption = $_POST['commentDisplayOption'];
        if (isset($commentDisplayOption)) {
            $updateOptions += array('commentDisplayOption' => $commentDisplayOption);
        }
        // Set the widget width for comment widgets
        $commentWidgetWidth = $_POST['commentWidgetWidth'];
        if (isset($commentWidgetWidth)) {
            if ($commentWidgetWidth < 150) {
                $commentWidgetWidth = 150;
            }
            if ($commentWidgetHeight > 1000) {
                $commentWidgetHeight = 1000;
            }
            $updateOptions += array('commentWidgetWidth' => $commentWidgetWidth);
        }
        $commentWidgetHeight = $_POST['commentWidgetHeight'];
        if (isset($commentWidgetHeight)) {
            if (($commentWidgetHeight < 150) && ($commentWidgetHeight != 0)) {
                $commentWidgetHeight = 150;
            }
            if ($commentWidgetHeight > 1000) {
                $commentWidgetHeight = 1000;
            }
            $updateOptions += array('commentWidgetHeight' => $commentWidgetHeight);
        }
        $commentSongLimit = $_POST['commentSongLimit'];
        if (isset($commentSongLimit)) {
            $updateOptions += array('commentSongLimit' => $commentSongLimit);
        }
        $commentDisplayPhrase = $_POST['commentDisplayPhrase'];
        if (isset($commentDisplayPhrase)) {
            $updateOptions += array('commentDisplayPhrase' => $commentDisplayPhrase);
        }
        $commentPlaylistName = $_POST['commentPlaylistName'];
        if (isset($commentPlaylistName)) {
            $updateOptions += array('commentPlaylistName' => $commentPlaylistName);
        }
        $commentColorScheme = $_POST['commentColorScheme'];
        if (isset($commentColorScheme)) {
            if ($commentColorScheme < 0) {
                $commentColorScheme = 0;
            }
            if ($commentColorScheme > 22) {
                $commentColorScheme = 22;
            }
            $updateOptions += array('commentColorScheme' => $commentColorScheme);
        }

        // Sets the API key needed to use the plugin
        // NOTE: Not currently used since the user is not required to register for an API key
        /*
        $APIKey = $_POST['APIKey'];
        if (isset($APIKey)) {
            $APIKey = '1100e42a014847408ff940b233a39930';
            // Tests whether the API key was correct
            $test = gs_callRemote('session.start',array('apiKey' => $APIKey));
            if (isset($test['decoded']['fault']['code'])) {
                $errorCodes[] = $test['decoded']['fault']['code'];
            }
            if (isset($test['decoded']['header']['sessionID'])) {
                $updateOptions += array('APIKey' => $APIKey);
            } else {
                $updateOptions += array('APIKey' => 0);
            }
        }
        */
        $gs_options = array_merge($gs_options,$updateOptions);
        // Updates the options and lets the user know the settings were saved.
        update_option('gs_options',$gs_options);
        $settingsSaved = 1;
    }
    $loginReset = 0;
    if ((($_POST['Status'] == 'Reset') && ($_POST['Submit'] != 'Enter'))) {
        //If the user wants to reset login information, destroy the saved token and set the userID to 0.
        $updateArray = array('userID' => 0);
        if ($gsapi->destroyAuthToken($gs_options['token'])) {
            $updateArray += array('token' => 0);
        }
        $gs_options = array_merge($gs_options, $updateArray);
        update_option('gs_options',$gs_options);
        $loginReset = 1;
    }
    $includeEnabled = 0;
    if ((($_POST['includePlaylists'] == 'Enable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to include created playlists with their GS account
        $gs_options['includePlaylists'] = 1;
        $includeEnabled = 1;
    }
    $includeDisabled = 0;
    if ((($_POST['includePlaylists'] == 'Disable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user does not want to include created playlists with their GS account
        $gs_options['includePlaylists'] = 0;
        $includeDisabled = 1;
    }
    $autosaveMusicEnabled = 0;
    if ((($_POST['autosaveMusic'] == 'Enable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to enable music autosave
        $gs_options['autosaveMusic'] = 1;
        $autosaveMusicEnabled = 1;
    }
    $autosaveMusicDisabled = 0;
    if ((($_POST['autosaveMusic'] == 'Disable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user does not want to enable music autosave
        $gs_options['autosaveMusic'] = 0;
        $autosaveMusicDisabled = 1;
    }
    $sidebarCleared = 0;
    if ((($_POST['sidebarOptions'] == 'Clear') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to clear their sidebar
        $gs_options['sidebarPlaylists'] = array();
        $sidebarCleared = 1;
    }
    $dashboardCleared = 0;
    if ((($_POST['dashboardOptions'] == 'Clear') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to clear their dashboard
        $gs_options['dashboardPlaylists'] = array();
        $dashboardCleared = 1;
    }
    $musicCommentsEnabled = 0;
    if ((($_POST['musicComments'] == 'Enable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to enable music comments
        $gs_options['musicComments'] = 1;
        $musicCommentsEnabled = 1;
    }
    $musicCommentsDisabled = 0;
    if ((($_POST['musicComments'] == 'Disable') && ($_POST['Submit'] != 'Enter'))) {
        // If the user wants to disable music comments
        $gs_options['musicComments'] = 0;
        $musicCommentsDisabled = 1;
    }
    print "<div class='updated'>";
    // Show user all updates that were made
    if ($settingsSaved) {
        print "<p>Settings Saved</p>";
    }
    if ($loginReset) {
        print "<p>Login Has Been Reset</p>";
    }
    if ($includeEnabled) {
        print "<p>Playlist Inclusion Enabled</p>";
    }
    if ($includeDisabled) {
        print "<p>Playlist Inclusion Disabled</p>";
    }
    if ($autosaveMusicEnabled) {
        print "<p>Autosave Music Enabled</p>";
    }
    if ($autosaveMusicDisabled) {
        print "<p>Autosave Music Disabled</p>";
    }
    if ($sidebarCleared) {
        print "<p>Grooveshark Sidebar Cleared</p>";
    }
    if ($dashboardCleared) {
        print "<p>Grooveshark Dashboard Cleared</p>";
    }
    if ($musicCommentsEnabled) {
        print "<p>Music Comments Enabled</p>";
    }
    if ($musicCommentsDisabled) {
        print "<p>Music Comments Disabled</p>";
    }
    print "</div>";
    update_option('gs_options',$gs_options);
    // Prints all the inputs for the options page. Here, the login information, login reset option, search option, and number of songs can be set.
    print "
    <form method=\"post\" action=\"\">
        <div class=\"wrap\">
            <h2>Grooveshark Plugin Options</h2>
            <input type=\"hidden\" name=\"status\" value=\"update\">
            <input type='hidden' name='sessionID' value='$sessionID'>
            <fieldset>";
    /*
    $APIKey = $gs_options['APIKey'];
    if ($APIKey == 0) { 
        if (isset($_POST['APIKey'])) {
            print "<legend><b>There was an error with your API key. Please try again.</b></legend>";
        } else {
            print "<legend>You must enter an API key to use this plugin.</legend>";
        }
    } else {
        if (isset($_POST['APIKey'])) {
            print "<legend>This API key is valid.</legend>";
        }
    }
    if ($APIKey == 0) {
        $value = '';
    } else {
        $value = $APIKey;
    }
    print "<table class='form-table'>
		<tr><th><label for='APIKey'>API Key:</label></th> <td><input type=\"text\" name=\"APIKey\" value=\"$value\" size='40' id='APIKey' /> </td></tr>";
    if ($APIKey == 0) {
        print "</table>
		<p class='submit'><input type=\"submit\" name=\"Submit\" value=\"Update Options\"></p>
		</div></fieldset></form>";
        return;
    }
    */
    print "<table class='form-table'>";
    if (count($errorCodes) > 0) {
        foreach($errorCodes as $code) {
            print "<tr><td colspan='2'><b>Error Code $code. Contact the author for support.</b></td></tr>";
        }
    }
    $userID = $gs_options['userID'];
    /* If the login failed, the user is notified. If no login information was saved, 
    then the user is reminded that they can enter their Grooveshark login information. */
    if ($userID == 0) {
        if ((($userID == 0) && ((isset($username) && $username != '') && (isset($password) && $password != '')))) {
            print "<tr><td colspan='2'><b>There was an error with your login information. Please try again.</b></td></tr>";
        } else {
            print "<tr><td colspan='2'>If you have a <a target='_blank' href='http://www.grooveshark.com'>Grooveshark</a> account, you can input your username and password information to access songs from your favorites list.</td></tr>";
        }
        // Displays the form to enter the login information.
        print "<tr><th><label for='gs-username'>Username: </label></th> <td><input type=\"text\" name=\"gs-username\" id='gs-username'> </td></tr>
           <tr><th><label for='gs-password'>Password: </label></th> <td><input type=\"password\" name=\"gs-password\" id='gs-password'></td></tr>";
    } else {
        // Displays the form to reset the login information. Also displays an option to allow the user to choose whether
        // plugin-created playlists are attached to their Grooveshark account.
        print "<tr align=\"top\">
           <th scope=\"row\"><label for='resetSong'>Reset your login information:</label></th>
           <td class='submit'><input type='submit' name='Submit' value='Enter' style='display: none;' /><input type='submit' name='Status' id='resetSong' value='Reset' />&nbsp; Your login information has been saved. Click this button to reset your login information.</td></tr>";
        $includePlaylists = $gs_options['includePlaylists'];
        print "<tr align='top'>
               <th scope='row'><label for='includePlaylists'>Include Playlists:</label></th>";
        if ($includePlaylists == 0) {
            print "<td class='submit'><input type='submit' name='includePlaylists' id='includePlaylists' value='Enable' />&nbsp; Click this button to enable adding Wordpress playlists you create to your Grooveshark account.</td>";
        } else {
            print "<td class='submit'><input type='submit' name='includePlaylists' id='includePlaylists' value='Disable' />&nbsp; Click this button to disable including Wordpress playlists in your Grooveshark account.</td>";
        }
        print "</tr>";
    }
    //Autosave option
    $autosaveMusic = $gs_options['autosaveMusic'];
    print "<tr align='top'>
           <th scope='row'><label for='autosaveMusic'><input type='submit' name='Submit' value='Enter' style='display: none;' />
           Autosave Music:
           </label></th>";
    if ($autosaveMusic) {
        print "<td class='submit'><input type='submit' name='autosaveMusic' id='autosaveMusic' value='Disable' />&nbsp; Click this button to disable autosave when you save and publish posts.</td>";
    } else {
        print "<td class='submit'><input type='submit' name='autosaveMusic' id='autosaveMusic' value='Enable' />&nbsp; Click this button to enable autosave when you save and publish posts.</td>";
    }
    print "</tr>";
    $sidebarOption = $gs_options['sidebarPlaylists'];
    if (!empty($sidebarOption)) {
        // Display option to clear sidebar
        print "<tr align='top'>
               <th scope='row'><label for='sidebarOptions'><input type='submit' name='Submit' value='Enter' style='display: none;' />
               Clear Sidebar:
               </label></th>
               <td class='submit'><input type='submit' name='sidebarOptions' id='sidebarOptions' value='Clear' />&nbsp; Click this button to clear the Grooveshark Sidebar Widget.</td>";
    }
    $dashboardOption = $gs_options['dashboardPlaylists'];
    if (!empty($dashboardOption)) {
        // Display option to clear dashboard
        print "<tr align='top'>
               <th scope='row'><label for='dashboardOptions'><input type='submit' name='Submit' value='Enter' style='display: none;' />
               Clear Dashboard:
               </label></th>
               <td class='submit'><input type='submit' name='dashboardOptions' id='dashboardOptions' value='Clear' />&nbsp; Click this button to clear the Grooveshark Dashboard Widget.</td>";
    }
    $musicComments = $gs_options['musicComments'];
    print "<tr align='top'>
           <th scope='row'><label for='musicComments'><input type='submit' name='Submit' value='Enter' style='display: none;' />
           Allow Music Comments:
           </label></th>";
    if ($musicComments) {
        print "<td class='submit'><input type='submit' name='musicComments' id='musicComments' value='Disable' />&nbsp; Click this button to disable music in readers' comments.</td>";
    } else {
        print "<td class='submit'><input type='submit' name='musicComments' id='musicComments' value='Enable' />&nbsp; Click this button to allow your blog readers to add music to their comments.</td>";
    }
    print "</tr>";
    if ($musicComments) {
        $commentDisplayOption = $gs_options['commentDisplayOption'];
        $commentWidget = '';
        $commentLink = '';
        if ($commentDisplayOption == 'widget') {
            $commentWidget = 'checked';
        } else {
            $commentLink = 'checked';
        }
        $commentWidgetWidth = $gs_options['commentWidgetWidth'];
        $commentWidgetHeight = $gs_options['commentWidgetHeight'];
        $commentSongLimit = $gs_options['commentSongLimit'];
        $commentDisplayPhrase = $gs_options['commentDisplayPhrase'];
        $commentPlaylistName = $gs_options['commentPlaylistName'];
        $commentColorScheme = $gs_options['commentColorScheme'];
        $colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
        print "<tr align='top'><th scope='row'><label for='commentDisplayOption'>Display Comment Music As:</label></th>
                   <td><label>Widget &nbsp;<input type='radio' name='commentDisplayOption' value='widget' $commentWidget />&nbsp;</label><label> Link &nbsp;<input type='radio' name='commentDisplayOption' value='link' $commentLink /></label> &nbsp; Specify whether you want music in comments to be displayed as a link to Grooveshark or as a widget.</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentWidgetWidth'>Width for Comment Widgets:</label></th>
                   <td><input type='text' name='commentWidgetWidth' value='$commentWidgetWidth' id='commentWidgetWidth'>&nbsp; Specify the width in pixels of widgets embeded in user comments.</td>
               </tr> 
               <tr align='top'><th scope='row'><label for='commentWidgetHeight'>Height for Comment Widgets:</label></th>
                   <td><input type='text' name='commentWidgetHeight' value='$commentWidgetHeight' id='commentWidgetHeight'>&nbsp; Specify the height in pixels of widgets embeded in user comments <b>(set to 0 for auto-adjustment)</b>.</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentSongLimit'>Comment Song Limit:</label></th>
                   <td><input type='text' name='commentSongLimit' value='$commentSongLimit' id='commentSongLimit'>&nbsp; Specify a limit on how many songs a user may embed as a widget in comments <b>(set to 0 for no limit)</b>.</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentDisplayPhrase'>Display Phrase for Comment Music Links:</label></th>
                   <td><input type='text' name='commentDisplayPhrase' value='$commentDisplayPhrase' id='commentDisplayPhrase'>&nbsp; Used in song links. Example: <b>$commentDisplayPhrase</b>: $commentPlaylistName</td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentPlaylistName'>Comment Playlist Names:</label></th>
                   <td><input type='text' name='commentPlaylistName' value='$commentPlaylistName' id='commentPlaylistName'>&nbsp; Used in songs links. Example: $commentDisplayPhrase: <b>$commentPlaylistName</b></td>
               </tr>
               <tr align='top'><th scope='row'><label for='commentColorScheme'>Color Scheme for Comment Widgets:</label></th>
                   <td><select type='text' id='commentColorScheme' name='commentColorScheme'>";
                   foreach ($colorsArray as $id => $colorOption) {
                       print "<option value='$id' ";
                       if ($id == $commentColorScheme) {
                           print "selected ";
                       }
                       print ">$colorOption</option";
                   }
                   print "&nbsp; Specify the color scheme of widgets embeded in user comments.</td>
                </tr>";
    }
    // Finished displaying the form for search options, and displays the form to enter how many songs to search for.
    print "<tr align=\"top\"> <th scope=\"row\"><label for='numberOfSongs'>Number of Results:<label></th>";
    $numberOfSongs = $gs_options['numberOfSongs'];
    print "<td><input type=\"text\" name=\"numberOfSongs\" value=\"$numberOfSongs\" id='numberOfSongs'>&nbsp; Specify how many songs or playlists you want the
           search to return.<input type='submit' name='Submit' value='Enter' style='display: none;' /></td></tr>";
    // Finished displaying all forms, and then ends.
    print "</table><p class='submit'><input type=\"submit\" name=\"Submit\" value=\"Update Options\"></p>
          </div>
          </form>";
}
?>
