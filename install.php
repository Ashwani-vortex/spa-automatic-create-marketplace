<?php
// ==================== Shivam SPA Auto Creator ====================

$request = $_REQUEST;

// Bitrix24 sends authorization data during installation
if (empty($request['auth']['access_token']) || empty($request['auth']['domain'])) {
    die("❌ Installation failed. No authorization data received from Bitrix24.");
}

$domain       = $request['auth']['domain'];
$access_token = $request['auth']['access_token'];

// Helper function to call Bitrix24 REST
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
        return ['error' => "HTTP Error {$httpCode}"];
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

    echo "<h2>✅ Success!</h2>";
    echo "SPA <strong>Shivam</strong> created.<br>";
    echo "Entity Type ID: <strong>{$entityTypeId}</strong><br><br>";

    // ===================== ADD CUSTOM FIELD "shivam_name" =====================
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
        echo "✅ Custom field <strong>shivam_name</strong> added successfully!";
    } else {
        echo "⚠️ Field creation warning: " . json_encode($fieldResult['error'] ?? $fieldResult);
    }
} else {
    echo "❌ Failed to create SPA: " . json_encode($spaResult['error'] ?? $spaResult);
}
?>