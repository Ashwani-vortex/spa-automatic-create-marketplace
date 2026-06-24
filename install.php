<?php
header('Content-Type: text/html; charset=utf-8');

$logFile = __DIR__ . '/install_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_REQUEST, true) . "\n\n", FILE_APPEND);

$request = $_REQUEST;

$domain            = $request['DOMAIN'] ?? null;
$auth_id           = $request['AUTH_ID'] ?? null;
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

// Create SPA
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

    // Try to add field with multiple tokens
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

    $tokens = array_filter([$auth_id, $application_token]);
    $fieldAdded = false;

    foreach ($tokens as $token) {
        $result = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $token);
        if ($result['http'] === 200 && !empty($result['data']['result'])) {
            echo "✅ Custom field <strong>shivam_name</strong> added successfully!";
            $fieldAdded = true;
            break;
        }
    }

    if (!$fieldAdded) {
        echo "<h3>⚠️ Field could not be added automatically (common during installation).</h3>";
        echo "<p><strong>What to do now:</strong></p>";
        echo "<ol>";
        echo "<li>Go to <strong>Applications → Installed Apps</strong></li>";
        echo "<li>Find your app → Click <strong>Update Permissions</strong> (or Uninstall & Reinstall as Administrator)</li>";
        echo "<li>Grant <strong>full CRM access</strong></li>";
        echo "<li>Reinstall the app</li>";
        echo "</ol>";
        echo "<p><strong>Alternative:</strong> Manually add the field <strong>shivam_name</strong> in the SPA settings.</p>";
    }
} else {
    echo "❌ SPA creation failed.";
}
?>