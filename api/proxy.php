<?php
// =========================================================================
// PROXY COMPLET POUR L'OCR (Déployé sur Vercel)
// =========================================================================

// 1. Autoriser les requêtes depuis n'importe quelle origine (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");

// 2. Gérer la requête "Preflight" OPTIONS
// C'est indispensable pour que le navigateur autorise le POST depuis Flutter Web
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // 204 No Content
    exit;
}

// 3. Refuser toutes les autres méthodes que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Utilisez POST.']);
    exit;
}

// 4. Vérifier qu'une image a bien été envoyée
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucune image fournie ou erreur d\'upload.']);
    exit;
}

$file = $_FILES['image'];

// 5. Préparer le fichier pour l'envoi via cURL
$cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
$postData = ['image' => $cfile];

// 6. L'URL de l'API d'extraction (Vercel)
$targetUrl = 'https://image-table-extractor.vercel.app/api/extract';

// 7. Configuration de cURL
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 120 secondes de timeout maximum

// 8. Exécution de la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 9. Gestion des erreurs cURL
if ($curlError) {
    http_response_code(502); // Bad Gateway
    echo json_encode([
        'error' => 'Upstream request failed', 
        'detail' => $curlError
    ]);
    exit;
}

// 10. Renvoyer la réponse au format JSON
http_response_code($httpCode);
header('Content-Type: application/json; charset=utf-8');
echo $response;
