<?php
/*
Plugin Name: Grooveshark for Wordpress
Plugin URI: http://www.grooveshark.com/wordpress
Description: Search for <a href="http://www.grooveshark.com">Grooveshark</a> songs and add links to a song or song widgets to your blog posts. 
Author: Roberto Sanchez and Vishal Agarwala
Version: 1.1.0
Author URI: http://www.grooveshark.com
*/

/*
Copyright 2009 Escape Media Group (email: vishal.agarwala@escapemg.com)

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

//The following lines and function definition are used to access the Grooveshark API via the callRemote function
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

//Retrievs the API Key
$gs_options = get_option('gs_options');
$APIKey = $gs_options['APIKey'];

// Sets up a sessionID for use with the rest of the script when making API calls
$sessionID = '';
if (isset($_POST['sessionID']) and $_POST['sessionID'] != 0) {
    $sessionID = $_POST['sessionID'];
} elseif ($APIKey != 0) {
    $resp = gs_callRemote('session.start', array('apiKey' => $APIKey));
    $sessionID = $resp['decoded']['header']['sessionID'];
}
// Checks to see if the options for this plugin exists. If not, the options are added
if (get_option('gs_options') == FALSE) {
    add_option('gs_options',array(
        'token' => 0,
        'userID' => 0,
        'numberOfSongs' => 30,
        'APIKey' => '1100e42a014847408ff940b233a39930',
        'displayPhrase' => 'Grooveshark',
        'widgetWidth' => 250,
        'widgetHeight' => 176,
        'colorScheme' => 'default',
        'includePlaylists' => 0,
        'autosaveMusic' => 1,));
}

add_action('admin_menu','addGroovesharkBox');

function addGroovesharkBox() {
    if( function_exists('add_meta_box')) {
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','post','advanced','high');
        add_meta_box('groovesharkdiv','Add Music','groovesharkBox','page','advanced','high');
    } else {
        add_action('dbx_post_advanced','oldGroovesharkBox');
        add_action('dbx_page_advanced','oldGroovesharkBox');
    }
}

// The code for the Add a Song to Your Post box below the content editing text area.
function groovesharkBox() {
    global $sessionID;
    $siteurl = get_option('siteurl');
    $version = get_bloginfo('version');
    // The basic code to display the postbox. The ending tags for div are at the end of this function
    print "<input type='hidden' name='sessionID' value='$sessionID' />
           <div id='jsPlayerReplace'></div>
		   <!--[if IE 7]>
		   <div id='IE7'>
		   <![endif]-->
           <div id='gsInfo'>
           <p>Add music to your posts. Go to the <a href='$siteurl/wp-admin/options-general.php?page=grooveshark.php' target='_blank'>settings page</a> for more music options.</p>
           </div>";
    // Retrieves the saved options for this plugin
    $gs_options = get_option('gs_options');
    // If the user has an API key, the function continues. If not, the user is notified of the need for an API key
    if ($gs_options['APIKey'] == 0) {
        print "<p style='font-size: 13px;'>An API key is required to use this plugin. Please set the API key on the settings page.</p>";
    }
    if ($gs_options['APIKey'] == 0) {
        print "</div></div>";
        return;
    }
    $tabClass = 'gsTabActive27';
    $tabClass2 = 'gsTabInactive27';
    $tabContainerClass = 'gsTabContainer27';
    $songClass = 'gsSongBox27';
	$versionClass = 'gs27';
    if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
        $tabContainerClass = 'gsTabContainer26';
        $tabClass = 'gsTabActive26';
        $tabClass2 = 'gsTabInactive26';
        $songClass = 'gsSongBox26';
		$versionClass = 'gs26';
    }
    $autosaveMusic = $gs_options['autosaveMusic'];
    print "<div id='gsSongSelection'>
    <input type='hidden' name='autosaveMusic' id='autosaveMusic' value='$autosaveMusic' />
    <input type='hidden' name='songIDs' id='songIDs' value='0' />
    <input type='hidden' name='gsTagStatus' id='gsTagStatus' value='0' />
    <ul class='$tabContainerClass'>
    	<li><a id='search-option' class='$tabClass' href='javascript:;' onclick=\"gsToggleSongSelect('Search')\">Search</a></li>
    	<li><a id='favorites-option' class='$tabClass2' href='javascript:;' onclick=\"gsToggleSongSelect('Favorites')\">Favorites</a></li>
    	<li><a id='playlists-option' class='$tabClass2' href='javascript:;' onclick=\"gsToggleSongSelect('Playlists')\">Playlists</a></li>
		<div class='clear' style='height:0'></div>
    </ul>
	<div class='clear' style='height:0'></div>";
    $userID = $gs_options['userID'];
    $token = $gs_options['token'];
    //Check if the user has registered
    $userCheck = 0;
    if ($userID != '' and $userID != 0) {
        $userCheck = 1;
    }
    // The keywords search div
    print "
	<div id='songs-search' class='$songClass' style='display: block;'>
		<div id='searchInputWrapper'>
			<div id='searchInput'>
    			<input tabindex='100' id='gs-query' type='text' name='gs-query' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {document.getElementById(\"gsSearchButton\").click(); return false;} else return true;' value='Search For A Song' class='empty' />
			    <input type='hidden' name='gsSessionID' value='$sessionID' id='gsSessionID' />
		        <input type='hidden' name='gsLimit' value='$numberOfSongs' id='gsLimit' />
		        <input type='hidden' name='userCheck' value='$userCheck' id='userCheck' />
		        <input type='hidden' name='gsBlogUrl' value='$siteurl' id='gsBlogUrl' />
		        <input type='hidden' name='wpVersion' value='$version' id='wpVersion' />
			</div>
		</div>
		<div id='searchButton'>
	    	<input tabindex='101' type='button' id='gsSearchButton' value='Search' class='button' onclick=\"gsSearch()\" />
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
    // The favorites div
    print "<div id='songs-favorites' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        print "<p>Search for your favorite songs on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page.</p>";
    } else {
        print "<table id='save-music-choice-favorites'>";
        $songsArray = array();
        $result = gs_callRemote('session.loginViaAuthToken',array('token' => $token), $sessionID);
        $songsArray = gs_callRemote('user.getFavoriteSongs',array('userID' => $userID), $sessionID);
        if (isset($songsArray['decoded']['fault']['code'])) {
            print "<tr><td colspan='3'>Error Code " . $songsArray['decoded']['fault']['code'] . ". Contact the author for support.";
        } else {
            $songsArray = $songsArray['decoded']['result']['songs'];
            $numberOfResults = count($songsArray);
            for ($id = 0; $id < $numberOfResults; $id++) {
                $songName = $songsArray[$id]['songName'];
                $artistName = $songsArray[$id]['artistName'];
                $songNameComplete = "$songName by $artistName";
                $stringLimit = 78;
                if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
                    $stringLimit = 71;
                }
                if (strlen($songNameComplete) > $stringLimit) {
                    $songNameComplete = substr($songName, 0, $stringLimit - 3 - strlen($artistName)) . "&hellip; by $artistName";
                }
                $songNameComplete = preg_replace("/\'/","&lsquo;",$songNameComplete,-1);
                $songNameComplete = preg_replace("/\"/","&quot;",$songNameComplete,-1);
                $songID = $songsArray[$id]['songID'];
                if ($id % 2) {
                    $rowClass = "gsTr1";
                } else {
                    if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
                        $rowClass = "gsTr26";
                    } else {
                        $rowClass = "gsTr27";
                    }
                }
                $preClass = 'gsPreSingle27';
                if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
                    $preClass = 'gsPreSingle26';
                }
                print "<tr class='$rowClass'>
                       <td class='gsTableButton'><a class='gsAdd' name='$songNameComplete::$songID' onclick='addToSelected(this.name);' style='cursor: pointer'></a></td>
                       <td class='gsTableButton'><a class='gsPlay' name='$songID' onclick='toggleStatus(this)' style='cursor: pointer'></a></td>
                       <td><pre class='$preClass'>$songNameComplete</pre></td>
                       </tr>";
            }
        }
        print "</table>";
    }
    print "</div>";
    //The playlists div
    print "<div id='songs-playlists' class='$songClass' style='display: none;'>";
    if ($userID == 0) {
        print "<p>Search for your playlists on Grooveshark. To use this feature, you must provide your Grooveshark login information in the Settings page.</p>";
    } else {
        print "<table id='save-music-choice-playlists'>";
        $playlistArray = gs_callRemote('user.getPlaylists', array('userID' => $userID, 'page' => 1), $sessionID);
        if (isset($playlistArray['decoded']['fault']['code'])) {
            print "<tr><td colspan='2'>Error Code " . $playlistArray['decoded']['fault']['code'] . ". Contact the author for support.";
        } else {
            $numberOfResults = count($playlistArray['decoded']['result']['playlists']);
            $colorId = 0;
            for ($id = 0; $id < $numberOfResults; $id++) {
                $playlistID = $playlistArray['decoded']['result']['playlists'][$id]['playlistID'];
                $playlistSubArray = gs_callRemote('playlist.about', array('playlistID' => $playlistID), $sessionID);
                $playlistName = $playlistSubArray['decoded']['result']['name'];
                $playlistSongs = $playlistSubArray['decoded']['result']['numSongs'];
                if (isset($playlistSongs) and $playlistSongs != '' and $playlistSongs != 0) {
                    if ($colorId % 2) {
                        $rowClass = "gsTr1";
                    } else {
                        if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
                            $rowClass = "gsTr26";
                        } else {
                            $rowClass = "gsTr27";
                        }
                    }
                    $colorId++;
                    $preClass = 'gsPrePlaylist27';
                    if (stripos($version,'2.6') !== false or stripos($version,'2.5') !== false) {
                        $preClass = 'gsPrePlaylist26';
                    }
                    print "<tr class='$rowClass'>
                    <td class='gsTableButton'><a class='gsAdd' name='$playlistID' onclick='addToSelectedPlaylist(this.name);' style='cursor: pointer'></a></td>
                    <td><pre class='$preClass'>$playlistName ($playlistSongs)</pre></td>
                    </tr>";
                }
            }
        }
        print "</table>";
    }
    print "</div>";
    $selectedCount = 0;
    //The selected songs div: dynamically updated with the songs the user wants to add to their post
    print "
    <div id='selected-song' class='$songClass'>
	<div id='selected-songs-header'>
		<a href=\"javascript:;\" onmousedown=\"clearSelected();\" id='clearSelected'>Clear All</a>
		<h4 id='selectedCount'>Selected Songs ($selectedCount):</h4>
	</div>
	<table id='selected-songs-table'>";
    print "</table>
    </div>
    </div>";
    //The appearance div: customizes options for displaying the widget 
    $widgetWidth = $gs_options['widgetWidth'];
    $widgetHeight = $gs_options['widgetHeight'];
    $displayPhrase = '';
    $playlistsName = ''; 
    if (!isset($playlistsName) or $playlistsName == '') {
        $playlistsName = 'Grooveshark Playlist';
    }
    if (!isset($displayPhrase) or $displayPhrase == '') {
        $displayPhrase = 'Grooveshark';
    }
    if (!isset($widgetHeight)) {
        $widgetHeight = 400;
    }
    if (!isset($widgetWidth)) {
        $widgetWidth = 250;
    }
    print "<a id='jsLink' href=\"javascript:;\" onmousedown=\"gsToggleAppearance();\">&darr; Appearance</a>
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
			<span class='key'><label for='playlistsName'>Playlist Name:</label></span>
			<span class='value''>
				<input tabindex='105' type='text' name='playlistsName' id='playlistsName' value='$playlistsName' onchange='changeExamplePlaylist(this)' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {changeExamplePlaylist(this); return false;} else return true;'/></td><td>Example: \"$displayPhrase: $playlistsName\"
			</span>
		</li>
    	<li style='display:none' id='gsDisplayLink'>
			<span class='key'><label for='displayPhrase'>Link Display Phrase:</label></span>
			<span class='value'>
				<input tabindex='106' type='text' name='displayPhrase' id='displayPhrase' value='$displayPhrase' onchange='changeExample(this)' onkeydown='if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {changeExample(this); return false;} else return true;'/></td><td> Example: \"$displayPhrase: song by artist\"			
			</span>
		</li>
   	</ul>
	
	<div id='gsDisplayWidget'>
	<h2 id='playlistAppearance'>Appearance of Multi-Song Widgets</h2>
	
	<ul class='gsAppearanceOptions'>
		<li>
			<span class='key'><label for='widgetWidth'>Widget Width:</label></span>
			<span class='value''>
				<input tabindex='107' type='text' name='widgetWidth' id='widgetWidth' value='$widgetWidth' onkeydown='if((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {checkWidgetValue(); return false;} else return true;'/></td><td><span>Range: 150px to 1000px</span>
			</span>
		</li>	
		<li>
			<span class='key'><label for='widgetHeight'>Widget Height:</label></span>
			<span class='value'>
				<input tabindex='108' type='text' name='widgetHeight' id='widgetHeight' value='$widgetHeight' ononkeydown='if((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {checkWidgetValue(); return false;} else return true;' /></td><td><span>Range: 150px to 1000px</span>
			</span>
		</li>
    ";
    // Customize the color scheme of the widget
    $colorScheme = $gs_options['colorScheme']; //use this to save the user's colorscheme preferences
    print "
		<li>
			<span class='key'><label>Color Scheme:</label></span>
			<span class='value'>
				<select tabindex='109' type='text' onchange='changeColor(this.form.colorsSelect);' id='colors-select' name='colorsSelect'>
	";
	$colorsArray = array("Default","Walking on the Sun","Neon Disaster","Golf Course","Creamcicle at the Beach Party","Toy Boat","Wine and Chocolate Covered Strawberries","Japanese Kite","Eggs and Catsup","Shark Bait","Sesame Street","Robot Food","Asian Haircut","Goth Girl","I Woke Up And My House Was Gone","Too Drive To Drunk","She Said She Was 18","Lemon Party","Hipster Sneakers","Blue Moon I Saw You Standing Alone","Monkey Trouble In Paradise");
	for ($i = 0; $i < count($colorsArray); $i++) {
	    $curScheme = $colorsArray[$i];
	    print "<option value='$i' ";
	    if ($i == $colorScheme) {
	        print "selected ";
	    }
	    print ">$curScheme</option>";
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

function oldGroovesharkBox() {
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

function add_gs_options_page() {
    add_options_page('Grooveshark Options','Grooveshark',8,basename(__FILE__),'grooveshark_options_page');
}

// Registers the action to add the options page for the plugin.
add_action('admin_menu','add_gs_options_page');

add_action('admin_print_scripts','groovesharkcss');

function groovesharkcss() {
    print "<link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark.css' />\n";
	print "<!--[if IE]><link type='text/css' rel='stylesheet' href='" . get_bloginfo('wpurl') . "/wp-content/plugins/grooveshark/css/grooveshark-ie.css' /><![endif]-->\n";
    if (is_admin()) {
        //wp_enqueue_script('jquery126',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/jquery-1.2.6.js');
        wp_enqueue_script('jquery');
        $gs_options = get_option('gs_options');
        wp_enqueue_script('swfobject',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/js/swfobject.js');
        wp_enqueue_script('player',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/js/player.js');
        wp_enqueue_script('tablednd',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/js/tablednd.js');
        wp_enqueue_script('grooveshark',get_bloginfo('wpurl').'/wp-content/plugins/grooveshark/js/grooveshark.js');
    }
}

/* 
//Comment Related code
// Registers the filter to add music to the comment, and the action to show the search box
// Remind users that to enable this option, their template must display comment_form. Also, for modification in the comments.php file or in themes:
// Add <?php do_action(’comment_form’, $post->ID); ?> just above </form> ending tag in comments.php. Save the file.
add_action('comment_form','groovesharkCommentBox');
add_filter('preprocess_comment','gs_appendToComment'); 

function groovesharkCommentBox() {
    $gs_options = get_option('gs_options');
    if ($gs_options['allowInComments'] == 1) {
    //All the code to show the commenter the Grooveshark Box goes here
    }
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
*/

function gs_autosaveMusic($content) {
    global $sessionID;
    $autosaveMusic = $_POST['autosaveMusic'];
    $gs_options = get_option('gs_options');
    $APIKey = $gs_options['APIKey'];
    if ($APIKey == 0 or !isset($autosaveMusic) or $autosaveMusic == 0 or $autosaveMusic != 1) {
        return $content;
    }
    //Here goes all the content preparation
    $songsArray = $_POST['songsInfoArray'];
        if (count($songsArray) <= 0) {
        return $content;
    }
    $displayOption = $_POST['displayChoice'];
    $widgetWidth = $_POST['widgetWidth'];
    $widgetHeight = $_POST['widgetHeight'];
    $colorScheme = $_POST['colorsSelect'];
    $displayPhrase = $_POST['displayPhrase']; 
    $playlistName = $_POST['playlistsName'];
    $userID = $gs_option['userID'];
    $token = $gs_option['token'];
    $includePlaylists = $gs_option['includePlaylists'];
    //Determine the display option
    if ($displayOption) {
        $displayOption = 'widget';
    } else {
        $displayOption = 'link';
    }
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
    $music = '';
     if ($displayOption == 'widget') {
        $music .= "<div id='gsWidget'>";
    }
    if ($displayOption == 'link') {
        $music .= "<div id='gsLink'>";
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
                $music .= 'Error Code ' . $widgetCode['decoded']['fault']['code'] . '. Contact author for support.';
            } else {
                $widgetCode = $widgetCode['decoded']['result']['embed'];
                $music .= $widgetCode;
            }
        }
        if ($displayOption == 'link') {
            $songArray = gs_callRemote('song.about', array("songID" => $songID), $sessionID);
            if (isset($songArray['decoded']['fault']['code'])) {
                $music .= 'Error Code ' . $songArray['decoded']['fault']['code'] . '. Contact author for support.';
            } else {
                $songName = $songArray['decoded']['result']['song']['songName'];
                $songNameUrl = preg_replace("/([a-zA-Z0-9]?)[^a-zA-Z0-9]+([a-zA-Z0-9]?)/","$1_$2",$songName,-1);
                $artistName = $songArray['decoded']['result']['song']['artistName'];
                $songURL = "http://listen.grooveshark.com/song/$songNameUrl/$songID";
                $liteUrl = "<a target='_blank' href='$songURL'>$displayPhrase: $songName by $artistName</a>";
                $music .= $liteUrl;
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
            $music .= 'Error Code ' . $playlist['decoded']['fault']['code'] . '. Contact author for support.';
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
            $music .= $embedCode;
            $music .= "<input type='hidden' name='gsPlaylistID' id='gsPlaylistID' value='$playlistID'/>";
        } elseif ($displayOption == 'link') {
            // The playlist link just displays the playlist name after the display phrase
            $music .= $playlistLiteUrl;
        }
    }
    if ($displayOption == 'widget' or $displayOption == 'link') {
        $music .= "</div>";
    }
    /*if ($displayOption == 'widget') {
        //remove the old widget
        $contentPattern = '/(?<prewidget>.+)\<div id\=\'gsWidget\'\>.+\<\/div\>(?<postwidget>.+)/';
        preg_match($contentPattern, $content, $contentMatches);
        $content = $contentMatches['prewidget'] . $contentMatches['postwidget'];
        foreach($contentMatches as $item) {
            $content .= "::$item::";
        }
    }*/
    $content .= $music;
    return $content;
}

add_filter('content_save_pre','gs_autosaveMusic');

// The function to display the options page.
function grooveshark_options_page() {
    global $sessionID;
    $errorCodes = array();
    $gs_options = get_option('gs_options');
    $settingsSaved = 0;
    // If the user wants to update the options...
    if ($_POST['status'] == 'update' or $_POST['Submit'] == 'Enter') {
        $updateOptions = array();
        $username = $_POST['gs-username'];
        $password = $_POST['gs-password'];
        /* If a username and password was entered, checks to see if they are valid via the 
        session.loginViaAuthToken method. If they are valid, the userID and token are retrieved and saved. */
        if (isset($username) and isset($password)) {
            $userID = 0;
            $token = 0;
            $hashpass = md5($password);
            $hashpass = $username . $hashpass;
            $hashpass = md5($hashpass);
            $result = gs_callRemote('session.createUserAuthToken',array('username' => $username, 'hashpass' => $hashpass), $sessionID);
            if (isset($result['decoded']['fault']['code'])) {
                $errorCodes[] = $result['decoded']['fault']['code'];
            }
            if (isset($result['decoded']['result']['userID'])) {
	        $userID = $result['decoded']['result']['userID'];
                $token = $result['decoded']['result']['token'];
            }
            $updateOptions += array('userID' => $userID, 'token' => $token);
        }
        // Sets the number of songs the user wants to search for. If no number was enter, it just saved the default (30).
        $numberOfSongs = $_POST['numberOfSongs'];
        if (!isset($numberOfSongs)) {
            $numberOfSongs = 30;
        }
        $updateOptions += array('numberOfSongs' => $numberOfSongs);
        // Sets the API key needed to use the plugin
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
        $gs_options = array_merge($gs_options,$updateOptions);
        // Updates the options and lets the user know the settings were saved.
        update_option('gs_options',$gs_options);
        $settingsSaved = 1;
    }
    $loginReset = 0;
    if ($_POST['Status'] == 'Reset' and $_POST['Submit'] != 'Enter') {
        //If the user wants to reset login information, destroy the saved token and set the userID to 0.
        $result = gs_callRemote('session.destroyAuthToken', array('token' => $gs_options['token']), $sessionID);
        if (isset($result['decoded']['fault']['code'])) {
            $errorCodes[] = $result['decoded']['fault']['code'];
        }
        $updateArray = array('userID' => 0);
        if (isset($result['decoded']['result'])) {
            $updateArray += array('token' => 0);
        }
        $gs_options = array_merge($gs_options, $updateArray);
        update_option('gs_options',$gs_options);
        $loginReset = 1;
    }
    $includeEnabled = 0;
    if ($_POST['includePlaylists'] == 'Enable' and $_POST['Submit'] != 'Enter') {
        $gs_options['includePlaylists'] = 1;
        $includeEnabled = 1;
    }
    $includeDisabled = 0;
    if ($_POST['includePlaylists'] == 'Disable' and $_POST['Submit'] != 'Enter') {
        $gs_options['includePlaylists'] = 0;
        $includeDisabled = 1;
    }
    $autosaveMusicEnabled = 0;
    if ($_POST['autosaveMusic'] == 'Enable' and $_POST['Submit'] != 'Enter') {
        $gs_options['autosaveMusic'] = 1;
        $autosaveMusicEnabled = 1;
    }
    $autosaveMusicDisabled = 0;
    if ($_POST['autosaveMusic'] == 'Disable' and $_POST['Submit'] != 'Enter') {
        $gs_options['autosaveMusic'] = 0;
        $autosaveMusicDisabled = 1;
    }
    print "<div class='updated'>";
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
    print "</div>";
    update_option('gs_options',$gs_options);
    // Prints all the inputs for the options page. Here, the login information, login reset option, search option, and number of songs can be set.
    print "<form method=\"post\" action=\"\">
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
        if ($userID == 0 and isset($username) and isset($password)) {
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
