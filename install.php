<?php
header('Content-Type: text/html; charset=utf-8');

// === Logging for debugging ===
$logFile = __DIR__ . '/install_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - FULL REQUEST: " . print_r($_REQUEST, true) . "\n\n", FILE_APPEND);

// Get authorization data (Marketplace style)
$request = $_REQUEST;

$domain = $request['DOMAIN'] ?? null;
$auth_id = $request['AUTH_ID'] ?? null;
$refresh_id = $request['REFRESH_ID'] ?? null;
$application_token = $request['APPLICATION_TOKEN'] ?? null;
$server_endpoint = $request['SERVER_ENDPOINT'] ?? 'https://oauth.bitrix.info/rest/';

if (empty($domain) || empty($auth_id)) {
    die("❌ Missing DOMAIN or AUTH_ID. Installation failed.");
}

// For installation, we can use AUTH_ID as access_token for the first call
$access_token = $auth_id;

function bitrixCall($method, $params, $domain, $access_token) {
    $url = "https://{$domain}/rest/{$method}?auth={$access_token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "HTTP {$httpCode}"];
    }

    return json_decode($response, true);
}

// ===================== CREATE SPA "Shivam" =====================
$spaParams = [
    'fields' => [
        'title'                    => 'Shivam',
        'isCategoriesEnabled'      => 'Y',
        'isStagesEnabled'          => 'Y',
        'isClientEnabled'          => 'Y',
        'isAutomationEnabled'      => 'Y',
        'isBizProcEnabled'         => 'Y',
        'isObserversEnabled'       => 'Y',
        'isSourceEnabled'          => 'Y',
        'isUseInUserfieldEnabled'  => 'Y',
        'isRecyclebinEnabled'      => 'Y'
    ]
];

$spaResult = bitrixCall('crm.type.add', $spaParams, $domain, $access_token);

if (isset($spaResult['result']['type']['entityTypeId'])) {
    $entityTypeId = $spaResult['result']['type']['entityTypeId'];

    echo "<h2>✅ SPA 'Shivam' Created Successfully!</h2>";
    echo "Entity Type ID: <strong>{$entityTypeId}</strong><br><br>";

    // ===================== ADD FIELD "shivam_name" =====================
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

    if (!empty($fieldResult['result'])) {
        echo "✅ Custom field <strong>shivam_name</strong> added successfully!<br>";
        echo "<p>You can now find the new SPA in CRM → Smart Process Automation.</p>";
    } else {
        echo "⚠️ Field error: " . json_encode($fieldResult);
    }
} else {
    echo "❌ SPA creation failed: " . json_encode($spaResult['error'] ?? $spaResult);
}
?>