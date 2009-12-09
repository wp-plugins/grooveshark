<?php
// JSON backend to just get widget embed code

require_once 'GSAPI.php';

if ((isset($_POST['sessionID']) && ($_POST['sessionID'] != ''))) {
    // Gets a GSAPI object for API calls
    $gsapi = GSAPI::getInstance(array('sessionID' => $_POST['sessionID']));
    if (isset($_POST['songID'])) {
        // Get the embed code
        $singleEmbedCode = $gsapi->songGetApWidgetEmbedCode($_POST['songID']);
        $returnCode = array();
        if (!(bool)stripos($singleEmbedCode, 'Error')) {
            $singleEmbedCode = preg_replace("/height=\"(\d+)\"/", "height=\"1\"", $singleEmbedCode);
            // If no error code, save the embed code
            $returnCode['embedCode'] = $singleEmbedCode;
            $returnCode['error'] = false;
        } else {
            $returnCode['error'] = true;
            $returnCode['embedCode'] = '';
        }
        print json_encode($returnCode);
    } else {
        // songID not provided, return nothing
        print '';
    }
} else {
    // No session provided, return nothing
    print '';
}
?>
