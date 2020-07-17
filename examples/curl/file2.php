<?php

function makeRequest() {
    $postRequest = array(
        'firstFieldData' => 'foo',
        'secondFieldData' => 'bar'
    );
    $cURLConnection = curl_init('https://postman-echo.com/post?id=123');
    curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    $apiResponse = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    return $apiResponse;

}
?>
