<?php
if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}
header('Access-Control-Allow-Methods: GET');
header("Access-Control-Max-Age: 600");
header("Content-Type: application/json");

$typoInputJSONString = file_get_contents('php://input');
if ($typoInputJSONString == '') {
// TODO : CURL zum TYPO, um den JSON für die Navigation abzuholen:
    // $typoInputJSONString = return CURL_Response
}

$typoInputJSONStringBase64Encoded = base64_encode($typoInputJSONString);

$menu_as_json = shell_exec('php restapimage.php --params ' . $typoInputJSONStringBase64Encoded . ' 2>&1');
echo $menu_as_json;

die();
