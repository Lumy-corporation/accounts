const express = require('express');
const axios = require('axios');
const cors = require('cors');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// VRAIES CONFIGURATIONS
const MAILCOW_URL = process.env.MAILCOW_URL || 'https://mail.lumycorp.com';
const MAILCOW_API_KEY = process.env.MAILCOW_API_KEY;

app.post('/api/register', async (req, res) => {
    const { name, username, password } = req.body;

    if (!name || !username || !password) {
        return res.status(400).json({ success: false, error: "Tous les champs sont requis." });
    }

    try {
        // Envoi réel à la vraie API Mailcow
        const response = await axios.post(`${MAILCOW_URL}/api/v1/add/mailbox`, {
            active: "1",
            domain: "lumymail.com",
            local_part: username.toLowerCase().trim(),
            name: name,
            authsource: "mailcow",
            password: password,
            password2: password,
            quota: "1024",
            force_pw_update: "0",
            force_tfa: "0",
            tls_enforce_in: "0",
            tls_enforce_out: "0",
            tags: ["b2c", "lumymail"]
        }, {
            headers: {
                'X-API-Key': MAILCOW_API_KEY,
                'Content-Type': 'application/json'
            }
        });

        const result = response.data;

        if (Array.isArray(result) && result[0] && result[0].type === "success") {
            return res.json({ success: true, message: "Compte réel créé avec succès !" });
        } else {
            const errorMsg = result[0]?.msg || "Erreur lors de la création du compte.";
            return res.status(400).json({ success: false, error: errorMsg });
        }

    } catch (error) {
        console.error("Erreur API Mailcow:", error.response?.data || error.message);
        return res.status(500).json({ success: false, error: "Impossible de contacter le serveur Mailcow." });
    }
});

app.listen(PORT, () => console.log(`Serveur prêt sur le port ${PORT}`));
