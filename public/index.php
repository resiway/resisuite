<?php
/*
* Public entry point for Qinoa framework
* For obvious security reasons, developers should ensure that this script remains the only entry-point.
*/


function getAppOutput() {
    ob_start();	
    include('../app.php'); 
    return ob_get_clean();
};

        
// This script is used to cache result of 'show' requests (that should return static HTML, and expect no params)
if(isset($_REQUEST['show'])) {
    $cache_filename = '../cache/'.$_REQUEST['show'];
    if(file_exists($cache_filename)) {
        print(file_get_contents($cache_filename));
        exit();
    }
}

$content = getAppOutput();
if( isset($cache_filename) && is_writable(dirname($cache_filename)) ) {
    file_put_contents($cache_filename, $content);
}
print($content);