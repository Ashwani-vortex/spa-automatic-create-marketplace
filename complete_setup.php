<?php
header('Content-Type: text/html; charset=utf-8');

if (!file_exists(__DIR__ . '/setup.json')) {
    die("Setup file not found. Please reinstall the app.");
}

$setup = json_decode(file_get_contents(__DIR__ . '/setup.json'), true);
$domain = $setup['domain'];
$entityTypeId = $setup['entityTypeId'];
$token = $setup['auth_id'];   // Usually works better after install

function bitrixCall($method, $params, $domain, $token) {
    $url = "https://{$domain}/rest/{$method}?auth={$token}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

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

$result = bitrixCall('userfieldconfig.add', $fieldParams, $domain, $token);

if (!empty($result['result'])) {
    echo "<h2>🎉 Success! Custom field 'shivam_name' has been added.</h2>";
    echo "<p>Your Shivam SPA is now ready to use.</p>";
    unlink(__DIR__ . '/setup.json'); // cleanup
} else {
    echo "<h2>❌ Failed to add field.</h2>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    echo "<p>Try clicking the button again or reinstall the app as Administrator.</p>";
}
?>