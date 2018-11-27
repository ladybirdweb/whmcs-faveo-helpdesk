<?php
require_once __DIR__ . '/../../../init.php';

try {
  $url = trim($_REQUEST['url'], '/');
  if (filter_var($url, FILTER_VALIDATE_URL)) {
    $client = new GuzzleHttp\Client([
      'base_uri' => $url,
    ]);
    $response = $client->get('/api/v1/helpdesk/url', [
      'query' => ['url' => $url]
    ]);
    echo $response->getBody();
  } else {
    echo json_encode(['result' => 'error', 'message' => 'Not a valid URL']);
  }
} catch (Exception $e) {
  echo json_encode(['result' => 'error', 'message' => 'Not a valid URL']);
}