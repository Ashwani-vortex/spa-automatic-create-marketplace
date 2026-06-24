<?php
header('Content-Type: text/html; charset=utf-8');

$logFile = __DIR__ . '/install_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_REQUEST, true) . "\n\n", FILE_APPEND);

$request = $_REQUEST;

$domain             = $request['DOMAIN'] ?? null;
$auth_id            = $request['AUTH_ID'] ?? null;
$refresh_id         = $request['REFRESH_ID'] ?? null;
$application_token  = $request['APPLICATION_TOKEN'] ?? null;

if (empty($domain) || empty($auth_id)) {
    die("❌ Missing authorization data.");
}

// Try with AUTH_ID first
$access_token = $auth_id;

function bitrixCall($method, $params, $domain, $access_token) {
    $url = "https://{$domain}/rest/{$method}?auth={$access_token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'httpCode' => $httpCode,
        'data'     => json_decode($response, true)
    ];
}

// === Create SPA (already working) ===
$spaParams = ['fields' => [
    'title' => 'Shivam',
    'isCategoriesEnabled' => 'Y',
    'isStagesEnabled' => 'Y',
    'isClientEnabled' => 'Y',
    'isAutomationEnabled' => 'Y',
    'isBizProcEnabled' => 'Y',
    'isObserversEnabled' => 'Y',
    'isSourceEnabled' => 'Y',
    'isUseInUserfieldEnabled' => 'Y',
    'isRecyclebinEnabled' => 'Y'
]];

$spaResult = bitrixCall('crm.type.add', $spaParams, $domain, $access_token);

if (isset($spaResult['data']['result']['type']['entityTypeId'])) {
    $entityTypeId = $spaResult['data']['result']['type']['entityTypeId'];

    echo "<h2>✅ SPA 'Shivam' Created (ID: {$entityTypeId})</h2>";

    // === Try to add field with better error handling ===
    $fieldParams = [
        'moduleId' => 'crm',
        'field' => [
            'entityId'      => 'CRM_' . $entityTypeId,
            'fieldName'     => 'UF_CRM_' . $entityTypeId . '_SHIVAM_NAME',
            'userTypeId'    => 'string',
            'multiple'      => 'N',
            'mandatory'     => 'N',
            'showFilter'    => 'Y',
            'editFormLabel' => ['en' => 'Shivam Name'],
            'listLabel'     => ['en' => 'Shivam Name'],
            'formLabel'     => ['en' => 'Shivam Name']
        ]
    ];

    $fieldResult = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $access_token);

    if ($fieldResult['httpCode'] === 200 && !empty($fieldResult['data']['result'])) {
        echo "✅ Custom field <strong>shivam_name</strong> added successfully!";
    } else {
        echo "<strong>⚠️ Field creation failed (HTTP {$fieldResult['httpCode']})</strong><br>";
        echo "<pre>" . htmlspecialchars(json_encode($fieldResult['data'], JSON_PRETTY_PRINT)) . "</pre>";
        
        // Fallback: Try with APPLICATION_TOKEN if available
        if (!empty($application_token)) {
            echo "<br><em>Trying alternative token...</em><br>";
            $altResult = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $application_token);
            if ($altResult['httpCode'] === 200 && !empty($altResult['data']['result'])) {
                echo "✅ Field added using APPLICATION_TOKEN!";
            }
        }
    }
} else {
    echo "SPA Error: " . json_encode($spaResult['data']);
}
?>