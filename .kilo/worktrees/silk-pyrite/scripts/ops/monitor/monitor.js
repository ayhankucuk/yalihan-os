const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const axios = require('axios');
const cron = require('node-cron');
const colors = require('colors');

class EmlakProMonitor {
    constructor() {
        this.app = express();
        this.server = http.createServer(this.app);
        this.io = socketIo(this.server, {
            cors: {
                origin: '*',
                methods: ['GET', 'POST'],
            },
        });

        this.baseUrl = process.env.LARAVEL_URL || 'http://localhost:8001';
        this.monitoringData = {
            lastCheck: null,
            pages: {},
            alerts: [],
            uptime: new Date(),
        };

        this.setupRoutes();
        this.setupSocketHandlers();
        this.startMonitoring();
    }

    setupRoutes() {
        this.app.use(express.json());

        // Health check endpoint
        this.app.get('/health', (req, res) => {
            res.json({
                status: 'healthy',
                uptime: Date.now() - this.monitoringData.uptime.getTime(),
                lastCheck: this.monitoringData.lastCheck,
                connectedClients: this.io.engine.clientsCount,
            });
        });

        // Get monitoring data
        this.app.get('/data', (req, res) => {
            res.json(this.monitoringData);
        });

        // Manual trigger for checks
        this.app.post('/check', async (req, res) => {
            try {
                await this.performHealthCheck();
                res.json({ success: true, message: 'Health check completed' });
            } catch (error) {
                res.status(500).json({ success: false, error: error.message });
            }
        });
    }

    setupSocketHandlers() {
        this.io.on('connection', (socket) => {
            console.log(`✅ Client connected: ${socket.id}`.green);

            // Send current data to new client
            socket.emit('monitoring-data', this.monitoringData);

            socket.on('disconnect', () => {
                console.log(`❌ Client disconnected: ${socket.id}`.red);
            });

            // Allow clients to request manual checks
            socket.on('manual-check', async () => {
                console.log('🔍 Manual check requested'.blue);
                await this.performHealthCheck();
            });
        });
    }

    startMonitoring() {
        console.log('🚀 EmlakPro Monitor Starting...'.cyan);

        // Initial check
        this.performHealthCheck();

        // Schedule regular checks every 30 seconds
        cron.schedule('*/30 * * * * *', () => {
            this.performHealthCheck();
        });

        // Schedule daily reports at 9 AM
        cron.schedule('0 9 * * *', () => {
            this.generateDailyReport();
        });

        console.log('⏰ Monitoring scheduled - checks every 30 seconds'.yellow);
    }

    async performHealthCheck() {
        const timestamp = new Date();
        console.log(`🔍 Performing health check at ${timestamp.toISOString()}`.blue);

        const pages = [
            { name: 'telegram-bot', path: '/admin/telegram-bot' },
            { name: 'adres-yonetimi', path: '/admin/adres-yonetimi' },
            { name: 'my-listings', path: '/admin/my-listings' },
            { name: 'analytics', path: '/admin/analytics' },
            { name: 'notifications', path: '/admin/notifications' },
        ];

        const results = {};
        const alerts = [];

        for (const page of pages) {
            try {
                const startTime = Date.now();
                const response = await axios.get(`${this.baseUrl}${page.path}`, {
                    timeout: 10000,
                    validateStatus: (status) => status < 500, // Accept 4xx as success for monitoring
                });

                const responseTime = Date.now() - startTime;
                const isHealthy = response.status < 400;

                results[page.name] = {
                    status: response.status,
                    responseTime,
                    isHealthy,
                    lastCheck: timestamp,
                    error: null,
                };

                // Generate alerts for issues
                if (!isHealthy) {
                    alerts.push({
                        type: response.status >= 500 ? 'critical' : 'warning',
                        page: page.name,
                        message: `HTTP ${response.status} - ${response.statusText}`,
                        timestamp,
                    });
                }

                if (responseTime > 2000) {
                    alerts.push({
                        type: 'warning',
                        page: page.name,
                        message: `Slow response time: ${responseTime}ms`,
                        timestamp,
                    });
                }

                console.log(`  ${page.name}: ${response.status} (${responseTime}ms)`.green);
            } catch (error) {
                results[page.name] = {
                    status: 0,
                    responseTime: 0,
                    isHealthy: false,
                    lastCheck: timestamp,
                    error: error.message,
                };

                alerts.push({
                    type: 'critical',
                    page: page.name,
                    message: `Connection failed: ${error.message}`,
                    timestamp,
                });

                console.log(`  ${page.name}: ERROR - ${error.message}`.red);
            }
        }

        // Update monitoring data
        this.monitoringData.lastCheck = timestamp;
        this.monitoringData.pages = results;

        // Keep only last 10 alerts
        this.monitoringData.alerts = [...alerts, ...this.monitoringData.alerts].slice(0, 10);

        // Broadcast to all connected clients
        this.io.emit('monitoring-update', {
            timestamp,
            pages: results,
            alerts: alerts,
            summary: this.generateSummary(results),
        });

        // Log summary
        const healthyPages = Object.values(results).filter((p) => p.isHealthy).length;
        const totalPages = Object.keys(results).length;
        console.log(`📊 Summary: ${healthyPages}/${totalPages} pages healthy`.cyan);

        if (alerts.length > 0) {
            console.log(`🚨 ${alerts.length} new alerts generated`.red);
        }
    }

    generateSummary(results) {
        const total = Object.keys(results).length;
        const healthy = Object.values(results).filter((p) => p.isHealthy).length;
        const avgResponseTime =
            Object.values(results)
                .filter((p) => p.responseTime > 0)
                .reduce((sum, p) => sum + p.responseTime, 0) / total;

        return {
            totalPages: total,
            healthyPages: healthy,
            healthPercentage: Math.round((healthy / total) * 100),
            avgResponseTime: Math.round(avgResponseTime),
            status: healthy === total ? 'healthy' : healthy > total / 2 ? 'warning' : 'critical',
        };
    }

    generateDailyReport() {
        console.log('📅 Generating daily report...'.yellow);

        const report = {
            date: new Date().toISOString().split('T')[0],
            summary: this.generateSummary(this.monitoringData.pages),
            alerts: this.monitoringData.alerts,
            uptime: Date.now() - this.monitoringData.uptime.getTime(),
        };

        // In a real implementation, this could be saved to a file or database
        console.log('📊 Daily Report:', JSON.stringify(report, null, 2));

        // Broadcast daily report to clients
        this.io.emit('daily-report', report);
    }

    start(port = 3001) {
        this.server.listen(port, () => {
            console.log(`🌐 EmlakPro Monitor running on port ${port}`.green);
            console.log(`📊 Dashboard: http://localhost:${port}`.cyan);
            console.log(`🔗 Laravel App: ${this.baseUrl}`.blue);
        });
    }
}

// Start the monitor
const monitor = new EmlakProMonitor();
monitor.start(process.env.PORT || 3001);

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🔴 Shutting down EmlakPro Monitor...'.red);
    process.exit(0);
});

module.exports = EmlakProMonitor;
