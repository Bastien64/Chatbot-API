<?php

header('Content-Type: application/json');

$filename = 'naturedelademande.json';
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lire tout le contenu
        if (file_exists($filename)) {
            $data = file_get_contents($filename);
            echo $data;
        } else {
            echo json_encode([]);
        }
        break;

    case 'POST':
        // Ajouter une nouvelle entrée
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Données JSON invalides']);
            exit;
        }

        $entries = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];

        $entries[] = $input;

        file_put_contents($filename, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(['message' => 'Ajout réussi']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
