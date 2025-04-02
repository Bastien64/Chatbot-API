<?php
// Configuration de l'API Gemini
$apiKey = 'AIzaSyA9KFv_T1diUIAtxh4xqDOmmqQkpcmjMIo';
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

// Charger les conversations
$json = file_get_contents('conversation.json');
$data = json_decode($json, true);

// Appel à l'API Gemini
function appelerGemini($prompt, $apiUrl) {
    $postData = [
        'contents' => [[
            'role' => 'user',
            'parts' => [[ 'text' => $prompt ]]
        ]]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? "Erreur ou réponse vide";
}

// Construction du tableau final
$resultats = [];

foreach ($data as $ip => $info) {
    $messagesUser = [];
    $conversationComplete = [];
    $date = null;

    if (isset($info['sessions'])) {
        foreach ($info['sessions'] as $session) {
            foreach ($session as $entry) {
                if (isset($entry['user'])) {
                    $messagesUser[] = $entry['user'];
                    $conversationComplete[] = "🧑 " . $entry['user'];
                }
                if (isset($entry['bot'])) {
                    $conversationComplete[] = "🤖 " . $entry['bot'];
                }
                if (!$date && isset($entry['timestamp'])) {
                    $date = $entry['timestamp'];
                }
            }
        }
    }

    // Génération du prompt pour la nature
    $promptCategorie = "Voici une série de messages envoyés par un utilisateur.\n" .
        "Analyse l'ensemble de la conversation et classe-la dans UNE SEULE des catégories suivantes :\n" .
        "- Demande de livraison\n" .
        "- Demande commerciale\n" .
        "- Demande d'installation\n\n" .
        "Si aucun message ne correspond, réponds : Non catégorisée.\n" .
        "Réponds uniquement par la catégorie, sans explication.\n\n" .
        "Messages :\n" . implode("\n", array_map(fn($m) => "- $m", $messagesUser));

    // Génération du prompt pour le résumé
    $promptResume = "Voici une conversation entre un utilisateur et un assistant. Résume brièvement cette conversation en une phrase claire :\n\n" .
        implode("\n", $conversationComplete);

    // Appels API
    $categorie = trim(appelerGemini($promptCategorie, $apiUrl));
    $resume = trim(appelerGemini($promptResume, $apiUrl));

    $resultats[] = [
        'ip' => $ip,
        'email' => $info['email'] ?? 'Non renseigné',
        'date' => $date ?? 'Inconnue',
        'nature' => $categorie,
        'résumé' => $resume
    ];
}

// Enregistrement dans naturedelademande.json
file_put_contents('naturedelademande.json', json_encode($resultats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Analyse terminée. Fichier naturedelademande.json créé avec résumés.\n";
?>
