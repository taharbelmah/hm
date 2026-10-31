export const config = {
  api: {
    bodyParser: false,
  },
};

export default async function handler(req, res) {
  // 1. Gérer le CORS (autoriser toutes les origines)
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  // 2. Répondre à la requête preflight OPTIONS
  if (req.method === 'OPTIONS') {
    return res.status(204).end();
  }

  // 3. Refuser les autres méthodes
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method Not Allowed' });
  }

  try {
    // 4. Transférer la requête vers l'API d'extraction
    const response = await fetch('https://image-table-extractor.vercel.app/api/extract', {
      method: 'POST',
      headers: {
        'Content-Type': req.headers['content-type'], // Important pour le multipart
      },
      body: req, // Transfère le flux directement
      duplex: 'half', // Requis pour le streaming de body en Node.js
    });

    const data = await response.json();
    
    // 5. Renvoyer la réponse JSON
    return res.status(response.status).json(data);
  } catch (error) {
    return res.status(500).json({ 
      error: 'Proxy failed', 
      detail: error.message 
    });
  }
}
