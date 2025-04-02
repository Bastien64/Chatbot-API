async function sendMessage() {
    const input = document.getElementById('userMessage');
    const chatBox = document.getElementById('chatBox');
    const userMessage = input.value.trim();

    if (!userMessage) return;

    // Affiche le message utilisateur dans le chat
    chatBox.innerHTML += `<div class='message user'><div class='bubble'>${userMessage}</div></div>`;
    input.value = '';
    chatBox.scrollTop = chatBox.scrollHeight;

    // Préparation de la requête
    const formData = new FormData();
    formData.append('message', userMessage);

    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        const botMessage = data.response || data.error || "Désolé, une erreur s'est produite.";

        chatBox.innerHTML += `<div class='message bot'><div class='bubble'>${botMessage}</div></div>`;
        chatBox.scrollTop = chatBox.scrollHeight;

    } catch (error) {
        chatBox.innerHTML += `<div class='message bot'><div class='bubble'>Erreur de connexion au serveur.</div></div>`;
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// Initialisation au chargement
document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById('chatBox');
    chatBox.innerHTML = `<div class='message bot'><div class='bubble'>Bonjour, veuillez entrer votre adresse email pour commencer.</div></div>`;

    const input = document.getElementById("userMessage");
    input.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });
});

// Fermer le chat
function closeChat() {
    console.log("clic sur le bouton");
    document.querySelector(".chat-container").style.display = "none";
}
