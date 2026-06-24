<?php
header('Content-Type: text/html; charset=utf-8');

// Log everything for debugging
$logFile = __DIR__ . '/install_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_REQUEST, true) . "\n\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n\n", FILE_APPEND);

$request = $_REQUEST;

// Try multiple possible locations for auth data
$auth = null;
if (!empty($request['auth'])) {
    $auth = $request['auth'];
} elseif (!empty($request['AUTH'])) {
    $auth = $request['AUTH'];
} elseif (!empty($_POST['auth'])) {
    $auth = $_POST['auth'];
}

if (empty($auth) || empty($auth['access_token']) || empty($auth['domain'])) {
    echo "<h2>❌ Installation failed.</h2>";
    echo "<p>No authorization data received from Bitrix24.</p>";
    echo "<p><strong>Debug Info:</strong></p>";
    echo "<pre>" . htmlspecialchars(print_r($_REQUEST, true)) . "</pre>";
    echo "<p>Please check the app configuration in Developer Resources.</p>";
    exit;
}

$domain       = $auth['domain'];
$access_token = $auth['access_token'];

// Helper function
function bitrixCall($method, $params, $domain, $access_token) {
    $url = "https://{$domain}/rest/{$method}?auth={$access_token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// === Create SPA ===
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

if (isset($spaResult['result']['type']['entityTypeId'])) {
    $entityTypeId = $spaResult['result']['type']['entityTypeId'];
    
    echo "<h2>✅ SPA 'Shivam' Created Successfully!</h2>";
    echo "Entity Type ID: <strong>{$entityTypeId}</strong><br><br>";

    // Add field
    $fieldParams = ['moduleId' => 'crm', 'field' => [
        'entityId'      => 'CRM_' . $entityTypeId,
        'fieldName'     => 'UF_CRM_' . $entityTypeId . '_SHIVAM_NAME',
        'userTypeId'    => 'string',
        'multiple'      => 'N',
        'mandatory'     => 'N',
        'showFilter'    => 'Y',
        'editFormLabel' => ['en' => 'Shivam Name'],
        'listLabel'     => ['en' => 'Shivam Name'],
        'formLabel'     => ['en' => 'Shivam Name']
    ]];

    $fieldResult = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $access_token);

    if (!empty($fieldResult['result'])) {
        echo "✅ Field 'shivam_name' added successfully!";
    } else {
        echo "Field error: " . json_encode($fieldResult);
    }
} else {
    echo "SPA Error: " . json_encode($spaResult);
}
?>