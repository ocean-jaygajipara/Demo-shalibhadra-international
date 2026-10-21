<?php

return [

    'sandbox' => [
        'auth' => 'https://apisandbox.whitebooks.in/ewaybillapi/v1.03/authenticate',
        'generate' => 'https://apisandbox.whitebooks.in/ewaybillapi/v1.03/ewayapi/genewaybill',
        'cancel' => 'https://apisandbox.whitebooks.in/ewaybillapi/v1.03/ewayapi/canewb',
        'update_vehicle' => 'https://apisandbox.whitebooks.in/ewaybillapi/v1.03/ewayapi/vehewb',
    ],

    'production' => [
        'auth' => 'https://ewaybillgst.gov.in/ewaybill/authenticate',
        'generate' => 'https://ewaybillgst.gov.in/ewaybill/generate',
        'cancel' => 'https://ewaybillgst.gov.in/ewaybill/cancel',
    ],

];
?>
