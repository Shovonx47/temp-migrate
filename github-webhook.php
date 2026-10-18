<?php
$secretFile = "/root/.sms-whiteinfo-webhook-secret";
$secret = trim(@file_get_contents($secretFile));
$body = file_get_contents("php://input");
$sig = $_SERVER["HTTP_X_HUB_SIGNATURE_256"] ?? "";
$event = $_SERVER["HTTP_X_GITHUB_EVENT"] ?? "";

if (!$secret || !$body || !$sig) {
    http_response_code(403);
    exit("Forbidden");
}

$expected = "sha256=" . hash_hmac("sha256", $body, $secret);

if (!hash_equals($expected, $sig)) {
    http_response_code(403);
    exit("Invalid signature");
}

if ($event !== "push") {
    http_response_code(204);
    exit;
}

$data = json_decode($body, true);

if (($data["ref"] ?? "") !== "refs/heads/main") {
    http_response_code(204);
    exit;
}

$deploy = "/usr/local/bin/sms-whiteinfo-deploy";

http_response_code(202);
header("Content-Type: application/json");
echo json_encode(["status"=>"accepted"]);

if (function_exists("fastcgi_finish_request")) {
    fastcgi_finish_request();
}

exec("sudo -n " . escapeshellarg($deploy) . " >/dev/null 2>&1 &");
?>
