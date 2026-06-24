<?php
header('Content-Type: text/html; charset=utf-8');

$logFile = __DIR__ . '/install_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_REQUEST, true) . "\n\n", FILE_APPEND);

$request = $_REQUEST;

$domain            = $request['DOMAIN'] ?? null;
$auth_id           = $request['AUTH_ID'] ?? null;
$refresh_id        = $request['REFRESH_ID'] ?? null;
$application_token = $request['APPLICATION_TOKEN'] ?? null;

if (empty($domain) || empty($auth_id)) {
    die("❌ Missing authorization data.");
}

function bitrixCall($method, $params, $domain, $token) {
    $url = "https://{$domain}/rest/{$method}?auth={$token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// 1. Create SPA
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

$spaResult = bitrixCall('crm.type.add', $spaParams, $domain, $auth_id);

if (isset($spaResult['data']['result']['type']['entityTypeId'])) {
    $entityTypeId = $spaResult['data']['result']['type']['entityTypeId'];
    echo "<h2>✅ SPA 'Shivam' Created (ID: {$entityTypeId})</h2>";

    // Try different tokens for field creation
    $tokensToTry = [$auth_id];
    if (!empty($application_token)) $tokensToTry[] = $application_token;

    $fieldAdded = false;
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

    foreach ($tokensToTry as $token) {
        $fieldResult = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $token);
        if ($fieldResult['http'] === 200 && !empty($fieldResult['data']['result'])) {
            echo "✅ Custom field <strong>shivam_name</strong> added successfully!";
            $fieldAdded = true;
            break;
        }
    }

    if (!$fieldAdded) {
        echo "<strong>⚠️ Could not add field due to insufficient permissions.</strong><br>";
        echo "This is common during Marketplace installation.<br><br>";
        echo "<strong>Recommended Solution:</strong><br>";
        echo "1. Go to your Bitrix24 → <strong>Applications → Installed</strong><br>";
        echo "2. Open this app → Click <strong>Update Permissions</strong> or reinstall it as Administrator.<br>";
        echo "3. Grant full <strong>CRM</strong> access.<br>";
        echo "4. Reinstall the app.";
    }
} else {
    echo "SPA Error: " . json_encode($spaResult['data']);
}
?>