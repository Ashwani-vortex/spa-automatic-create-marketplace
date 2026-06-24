<?php
header('Content-Type: text/html; charset=utf-8');

$request = $_REQUEST;

$domain            = $request['DOMAIN'] ?? null;
$auth_id           = $request['AUTH_ID'] ?? null;
$application_token = $request['APPLICATION_TOKEN'] ?? null;

if (empty($domain) || empty($auth_id)) {
    die("❌ Installation failed - No authorization data.");
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
    return ['http' => $httpCode, 'data' => json_decode($response, true)];
}

// === 1. CREATE SPA ===
$spaParams = ['fields' => [
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
]];

$spaResult = bitrixCall('crm.type.add', $spaParams, $domain, $auth_id);

if (isset($spaResult['data']['result']['type']['entityTypeId'])) {
    $entityTypeId = $spaResult['data']['result']['type']['entityTypeId'];

    echo "<h2>✅ SPA 'Shivam' Created Successfully!</h2>";
    echo "Entity Type ID: <strong>{$entityTypeId}</strong><br><br>";

    // === 2. TRY TO CREATE CUSTOM FIELD (Multiple Attempts) ===
    $fieldParams = [
        'moduleId' => 'crm',
        'field' => [
            'entityId'      => 'CRM_' . $entityTypeId,
            'fieldName'     => 'UF_CRM_' . $entityTypeId . '_SHIVAM_NAME',
            'userTypeId'    => 'string',
            'multiple'      => 'N',
            'mandatory'     => 'N',
            'showFilter'    => 'Y',
            'editFormLabel' => ['en' => 'Shivam Name', 'ru' => 'Имя Shivam'],
            'listLabel'     => ['en' => 'Shivam Name'],
            'formLabel'     => ['en' => 'Shivam Name']
        ]
    ];

    $tokens = array_filter([$auth_id, $application_token]);
    $fieldCreated = false;

    foreach ($tokens as $token) {
        $fieldResult = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $token);
        if ($fieldResult['http'] === 200 && !empty($fieldResult['data']['result'])) {
            echo "✅ Custom field <strong>shivam_name</strong> added automatically!";
            $fieldCreated = true;
            break;
        }
    }

    if (!$fieldCreated) {
        echo "<strong>⚠️ Custom field could not be added automatically.</strong><br>";
        echo "Please reinstall the app as **Portal Administrator** and grant full CRM permissions.";
    }
} else {
    echo "❌ Failed to create SPA.";
}
?>