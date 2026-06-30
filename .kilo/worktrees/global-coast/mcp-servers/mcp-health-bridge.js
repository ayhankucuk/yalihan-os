import express from 'express';

const app = express();
const PORT = 4001;

app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        saglik_durumu: 'healthy',
        message: 'Yalıhan Bekçi MCP health bridge is active and listening.',
        timestamp: new Date().toISOString()
    });
});

app.listen(PORT, 'localhost', () => {
    console.log(`🛡️ Yalıhan Bekçi MCP Health Bridge running on http://localhost:${PORT}`);
});
