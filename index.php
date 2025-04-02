<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si requête AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $apiKey = '';
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

    $promptFile = __DIR__ . '/prompt.txt';
    if (!file_exists($promptFile)) {
        echo json_encode(['error' => 'Fichier de prompt introuvable.']);
        exit;
    }
    $prompt = file_get_contents($promptFile);

    $conversationFile = __DIR__ . '/conversation.json';
    $ip = $_SERVER['REMOTE_ADDR'];
    $currentTime = time();

    $conversations = file_exists($conversationFile)
        ? json_decode(file_get_contents($conversationFile), true)
        : [];

    if (!isset($conversations[$ip])) {
        $conversations[$ip] = [
            'email' => null,
            'sessions' => []
        ];
    }

    $userMessage = trim($_POST['message'] ?? '');

    // Étape 1 : demande de l'email
    if (empty($conversations[$ip]['email'])) {
        if (filter_var($userMessage, FILTER_VALIDATE_EMAIL)) {
            $conversations[$ip]['email'] = $userMessage;
            file_put_contents($conversationFile, json_encode($conversations, JSON_PRETTY_PRINT));
            echo json_encode(['response' => 'Merci. Comment puis-je vous aider ?']);
        } else {
            echo json_encode(['response' => 'Veuillez entrer une adresse email valide.']);
        }
        exit;
    }

    // Étape 2 : Appel à l'API Gemini
    $data = [
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => $prompt . "\n\n" . $userMessage]]
        ]]
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        $error = error_get_last();
        echo json_encode(['error' => 'Erreur API : ' . ($error['message'] ?? 'Inconnue')]);
        exit;
    }

    $responseData = json_decode($response, true);
    $botMessage = $responseData['candidates'][0]['content']['parts'][0]['text']
        ?? 'Réponse inattendue de Gemini.';

    // Sauvegarde de la session
    $entry = [
        'timestamp' => date('Y-m-d H:i:s', $currentTime),
        'user' => $userMessage,
        'bot' => $botMessage
    ];

    $lastSession = end($conversations[$ip]['sessions']);
    $lastTimestamp = $lastSession ? strtotime(end($lastSession)['timestamp']) : 0;

    if (($currentTime - $lastTimestamp) >= 600 || !$lastSession) {
        $conversations[$ip]['sessions'][] = [$entry];
    } else {
        $conversations[$ip]['sessions'][array_key_last($conversations[$ip]['sessions'])][] = $entry;
    }

    file_put_contents($conversationFile, json_encode($conversations, JSON_PRETTY_PRINT));
    echo json_encode(['response' => $botMessage]);
    exit;
}
?>

<!-- Le HTML ci-dessous NE S'AFFICHE QUE si la requête n'est pas POST -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TerraBidet Chatbot</title>
    <link href="style.css" rel="stylesheet" />
    <script src="script.js" defer></script>
</head>
<body>
    <div class="main">
        <h2>TerraBidet</h2>
        <p>The perfect yugoslavia butt cleaner</p>
        <div class="chat-container">
            <div id="chatBox" class="chat-box"></div>
            <div class="input-container">
                <input type="text" id="userMessage" placeholder="Ask a question...">
                <button onclick="sendMessage()">Envoyer</button>
            </div>
            <button onclick="closeChat()">Fermer le chat</button>
        </div>
    </div>
</body>
</html>
