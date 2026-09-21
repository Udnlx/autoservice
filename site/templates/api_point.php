<?php

$token = $pages->get("template=api_point")->token;

$ch = curl_init("https://api.avtovincod.ru/brief?vin=X7L5SRLVG67904446");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $token],
]);
$data = json_decode(curl_exec($ch), true);
curl_close($ch);

// print_r ($data);
?>

<div id="content">
    <?php print_r ($data); ?>
</div>