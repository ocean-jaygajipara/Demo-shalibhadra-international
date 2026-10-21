<?php

return [
    'sandbox' => [
        'auth' => 'https://apisandbox.whitebooks.in/einvoice/authenticate',
        'generate_irn' => 'https://apisandbox.whitebooks.in/einvoice/type/GENERATE/version/V1_03',
        'cancel_irn' => 'https://apisandbox.whitebooks.in/einvoice/type/CANCEL/version/V1_03',
        'get_irn' => 'https://sandbox-api.whitebooks.in/einvoice/type/IRNBYIRN/version/V1_03',
    ],
    'production' => [
        'auth' => 'https://api.whitebooks.in/einvoice/authenticate', // Or other IRPs
        'generate_irn' => 'https://api.whitebooks.in/einvoice/type/GENERATE/version/V1_03',
        'cancel_irn' => 'https://api.whitebooks.in/einvoice/type/CANCEL/version/V1_03',
        'get_irn' => 'https://api.whitebooks.in/einvoice/type/CANCEL/version/V1_03',
    ],
];
?>
