<template>
  <div class="ai-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div class="row align-items-center">
        <div class="col">
          <h2 class="mb-1">
            <i class="fas fa-robot text-primary me-2"></i>
            AI Asistan Dashboard
          </h2>
          <p class="text-muted mb-0">Yapay zeka destekli emlak yönetim araçları</p>
        </div>
        <div class="col-auto">
          <div class="ai-status">
            <span class="status-indicator" :class="aiStatus"></span>
            <small class="text-muted">{{ getStatusText() }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <div class="stat-icon bg-primary">
            <i class="fas fa-comments"></i>
          </div>
          <div class="stat-content">
            <h4 class="stat-number">{{ stats.total_chats || 0 }}</h4>
            <p class="stat-label">AI Sohbetler</p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <div class="stat-icon bg-success">
            <i class="fas fa-calculator"></i>
          </div>
          <div class="stat-content">
            <h4 class="stat-number">{{ stats.price_predictions || 0 }}</h4>
            <p class="stat-label">Fiyat Tahminleri</p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <div class="stat-icon bg-warning">
            <i class="fas fa-edit"></i>
          </div>
          <div class="stat-content">
            <h4 class="stat-number">{{ stats.descriptions_generated || 0 }}</h4>
            <p class="stat-label">Açıklama Üretimi</p>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="stat-card">
          <div class="stat-icon bg-info">
            <i class="fas fa-search"></i>
          </div>
          <div class="stat-content">
            <h4 class="stat-number">{{ stats.request_analyses || 0 }}</h4>
            <p class="stat-label">Talep Analizleri</p>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Tools Grid -->
    <div class="row">
      <!-- Chat Assistant -->
      <div class="col-lg-6 mb-4">
        <div class="tool-card">
          <div class="tool-header">
            <h5 class="tool-title">
              <i class="fas fa-comments text-primary me-2"></i>
              AI Sohbet Asistanı
            </h5>
            <button class="btn btn-sm btn-outline-primary" @click="openChatWidget">
              <i class="fas fa-external-link-alt"></i>
            </button>
          </div>
          <div class="tool-content">
            <p class="text-muted mb-3">
              Emlak sorularınızı sorun, müşteri bilgilerini sorgulayın ve sistem hakkında bilgi alın.
            </p>
            <div class="quick-chat">
              <div class="chat-preview" v-if="recentChats.length > 0">
                <h6>Son Sohbetler</h6>
                <div class="chat-item" v-for="chat in recentChats.slice(0, 3)" :key="chat.id">
                  <div class="chat-question">{{ truncateText(chat.question, 50) }}</div>
                  <small class="text-muted">{{ formatTime(chat.created_at) }}</small>
                </div>
              </div>
              <div v-else class="text-center py-3">
                <i class="fas fa-robot text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0">Henüz sohbet geçmişi yok</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Price Prediction -->
      <div class="col-lg-6 mb-4">
        <div class="tool-card">
          <div class="tool-header">
            <h5 class="tool-title">
              <i class="fas fa-calculator text-success me-2"></i>
              Fiyat Tahmini
            </h5>
            <button class="btn btn-sm btn-outline-success" @click="openPricePrediction">
              <i class="fas fa-magic"></i>
              Tahmin Yap
            </button>
          </div>
          <div class="tool-content">
            <p class="text-muted mb-3">
              Emlak özelliklerine göre AI destekli fiyat tahmini yapın.
            </p>
            <div class="prediction-preview" v-if="recentPredictions.length > 0">
              <h6>Son Tahminler</h6>
              <div class="prediction-item" v-for="prediction in recentPredictions.slice(0, 2)" :key="prediction.id">
                <div class="prediction-location">
                  {{ prediction.location }}
                </div>
                <div class="prediction-price">
                  {{ formatPrice(prediction.avg_price) }}
                </div>
                <small class="text-muted">{{ formatTime(prediction.created_at) }}</small>
              </div>
            </div>
            <div v-else class="text-center py-3">
              <i class="fas fa-calculator text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted mb-0">Henüz tahmin yapılmadı</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Description Generator -->
      <div class="col-lg-6 mb-4">
        <div class="tool-card">
          <div class="tool-header">
            <h5 class="tool-title">
              <i class="fas fa-edit text-warning me-2"></i>
              Açıklama Üretici
            </h5>
            <button class="btn btn-sm btn-outline-warning" @click="openDescriptionGenerator">
              <i class="fas fa-pen"></i>
              Oluştur
            </button>
          </div>
          <div class="tool-content">
            <p class="text-muted mb-3">
              İlan bilgilerinize göre çekici başlık ve açıklamalar oluşturun.
            </p>
            <div class="description-styles">
              <h6>Mevcut Stiller</h6>
              <div class="style-tags">
                <span class="badge bg-light text-dark me-1">Profesyonel</span>
                <span class="badge bg-light text-dark me-1">Samimi</span>
                <span class="badge bg-light text-dark me-1">Lüks</span>
                <span class="badge bg-light text-dark">Minimal</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Request Analysis -->
      <div class="col-lg-6 mb-4">
        <div class="tool-card">
          <div class="tool-header">
            <h5 class="tool-title">
              <i class="fas fa-search text-info me-2"></i>
              Talep Analizi
            </h5>
            <button class="btn btn-sm btn-outline-info" @click="openRequestAnalysis">
              <i class="fas fa-analyze"></i>
              Analiz Et
            </button>
          </div>
          <div class="tool-content">
            <p class="text-muted mb-3">
              Müşteri taleplerini analiz edin ve uygun ilanları bulun.
            </p>
            <div class="analysis-features">
              <h6>Özellikler</h6>
              <ul class="feature-list">
                <li><i class="fas fa-check text-success me-1"></i> Metin analizi</li>
                <li><i class="fas fa-check text-success me-1"></i> Otomatik eşleştirme</li>
                <li><i class="fas fa-check text-success me-1"></i> İletişim bilgisi çıkarma</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-history text-secondary me-2"></i>
              Son AI Aktiviteleri
            </h5>
          </div>
          <div class="card-body">
            <div v-if="recentActivities.length > 0" class="activity-timeline">
              <div
                v-for="activity in recentActivities"
                :key="activity.id"
                class="activity-item"
              >
                <div class="activity-icon" :class="getActivityIconClass(activity.type)">
                  <i :class="getActivityIcon(activity.type)"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-title">{{ activity.title }}</div>
                  <div class="activity-description">{{ activity.description }}</div>
                  <small class="activity-time text-muted">{{ formatTime(activity.created_at) }}</small>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <i class="fas fa-clock text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted mb-0">Henüz aktivite yok</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="pricePredictionModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">AI Fiyat Tahmini</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <AIPricePrediction @prediction-made="onPredictionMade" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import AIPricePrediction from './AIPricePrediction.vue';

export default {
  name: 'AIDashboard',
  components: {
    AIPricePrediction
  },
  data() {
    return {
      aiStatus: 'connected', // connected, disconnected, error
      stats: {
        total_chats: 0,
        price_predictions: 0,
        descriptions_generated: 0,
        request_analyses: 0
      },
      recentChats: [],
      recentPredictions: [],
      recentActivities: [],
      loading: false
    }
  },

  mounted() {
    this.loadDashboardData();
    this.checkAIStatus();
  },

  methods: {
    async loadDashboardData() {
      this.loading = true;
      try {
        // Bu endpoint'ler gelecekte implement edilecek
        // const response = await axios.get('/api/ai/dashboard-data');
        // this.stats = response.data.stats;
        // this.recentChats = response.data.recent_chats;
        // this.recentPredictions = response.data.recent_predictions;
        // this.recentActivities = response.data.recent_activities;

        // Şimdilik mock data
        this.loadMockData();
      } catch (error) {
        console.error('Dashboard data loading error:', error);
      } finally {
        this.loading = false;
      }
    },

    loadMockData() {
      this.stats = {
        total_chats: 42,
        price_predictions: 18,
        descriptions_generated: 25,
        request_analyses: 12
      };

      this.recentChats = [
        {
          id: 1,
          question: 'Kadıköy bölgesindeki konut fiyatları nasıl?',
          created_at: new Date(Date.now() - 2 * 60 * 60 * 1000)
        },
        {
          id: 2,
          question: 'Müşteri talebini nasıl analiz edebilirim?',
          created_at: new Date(Date.now() - 4 * 60 * 60 * 1000)
        }
      ];

      this.recentPredictions = [
        {
          id: 1,
          location: 'Kadıköy, İstanbul',
          avg_price: 2500000,
          created_at: new Date(Date.now() - 1 * 60 * 60 * 1000)
        },
        {
          id: 2,
          location: 'Çankaya, Ankara',
          avg_price: 1800000,
          created_at: new Date(Date.now() - 3 * 60 * 60 * 1000)
        }
      ];

      this.recentActivities = [
        {
          id: 1,
          type: 'chat',
          title: 'AI Sohbet',
          description: 'Kadıköy bölgesi hakkında soru soruldu',
          created_at: new Date(Date.now() - 30 * 60 * 1000)
        },
        {
          id: 2,
          type: 'prediction',
          title: 'Fiyat Tahmini',
          description: '3+1 daire için fiyat tahmini yapıldı',
          created_at: new Date(Date.now() - 60 * 60 * 1000)
        },
        {
          id: 3,
          type: 'description',
          title: 'Açıklama Üretimi',
          description: 'Lüks villa için açıklama oluşturuldu',
          created_at: new Date(Date.now() - 90 * 60 * 1000)
        }
      ];
    },

    async checkAIStatus() {
      try {
        const url = window.APIConfig && window.APIConfig.ai && window.APIConfig.ai.status
          ? window.APIConfig.ai.status
          : '/api/v1/ai/status';
        const response = await axios.get(url);
        this.aiStatus = response.data.success ? 'connected' : 'error';
      } catch (error) {
        this.aiStatus = 'error';
        console.error('AI status check error:', error);
      }
    },

    getStatusText() {
      const texts = {
        'connected': 'AI Aktif',
        'disconnected': 'AI Bağlantısı Kesildi',
        'error': 'AI Hatası'
      };
      return texts[this.aiStatus] || 'Bilinmiyor';
    },

    openChatWidget() {
      // Chat widget'ını aç
      this.$emit('open-chat-widget');
    },

    openPricePrediction() {
      const modal = new bootstrap.Modal(document.getElementById('pricePredictionModal'));
      modal.show();
    },

    openDescriptionGenerator() {
      // Description generator modal'ını aç
      this.$emit('open-description-generator');
    },

    openRequestAnalysis() {
      // Request analysis modal'ını aç
      this.$emit('open-request-analysis');
    },

    onPredictionMade(data) {
      // Yeni tahmin yapıldığında
      this.recentPredictions.unshift({
        id: Date.now(),
        location: `${data.form.ilce}, ${data.form.il}`,
        avg_price: data.result.prediction.avg_price,
        created_at: new Date()
      });

      this.stats.price_predictions++;
    },

    formatPrice(price) {
      if (!price) return 'Belirtilmemiş';

      return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(price);
    },

    formatTime(timestamp) {
      const now = new Date();
      const time = new Date(timestamp);
      const diffInMinutes = Math.floor((now - time) / (1000 * 60));

      if (diffInMinutes < 1) return 'Az önce';
      if (diffInMinutes < 60) return `${diffInMinutes} dakika önce`;
      if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} saat önce`;
      return time.toLocaleDateString('tr-TR');
    },

    truncateText(text, length) {
      if (text.length <= length) return text;
      return text.substring(0, length) + '...';
    },

    getActivityIcon(type) {
      const icons = {
        'chat': 'fas fa-comments',
        'prediction': 'fas fa-calculator',
        'description': 'fas fa-edit',
        'analysis': 'fas fa-search'
      };
      return icons[type] || 'fas fa-circle';
    },

    getActivityIconClass(type) {
      const classes = {
        'chat': 'bg-primary',
        'prediction': 'bg-success',
        'description': 'bg-warning',
        'analysis': 'bg-info'
      };
      return classes[type] || 'bg-secondary';
    }
  }
}
</script>

<style scoped>
.ai-dashboard {
  padding: 20px;
}

.dashboard-header {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.ai-status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.status-indicator.connected { background: #28a745; }
.status-indicator.disconnected { background: #ffc107; }
.status-indicator.error { background: #dc3545; }

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 15px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 24px;
  font-weight: bold;
  margin: 0;
  color: #2c3e50;
}

.stat-label {
  margin: 0;
  color: #7f8c8d;
  font-size: 14px;
}

.tool-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  height: 100%;
  transition: transform 0.2s, box-shadow 0.2s;
}

.tool-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.tool-header {
  padding: 20px 20px 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tool-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.tool-content {
  padding: 20px;
}

.quick-chat, .prediction-preview {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
}

.chat-item, .prediction-item {
  padding: 10px 0;
  border-bottom: 1px solid #e9ecef;
}

.chat-item:last-child, .prediction-item:last-child {
  border-bottom: none;
}

.chat-question {
  font-size: 14px;
  color: #2c3e50;
  margin-bottom: 4px;
}

.prediction-location {
  font-size: 14px;
  color: #2c3e50;
  font-weight: 500;
}

.prediction-price {
  font-size: 16px;
  color: #28a745;
  font-weight: bold;
}

.description-styles, .analysis-features {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
}

.style-tags {
  margin-top: 10px;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin: 10px 0 0 0;
}

.feature-list li {
  padding: 4px 0;
  font-size: 14px;
}

.activity-timeline {
  position: relative;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  padding: 15px 0;
  border-bottom: 1px solid #e9ecef;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
}

.activity-description {
  color: #7f8c8d;
  font-size: 14px;
  margin-bottom: 4px;
}

.activity-time {
  font-size: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .ai-dashboard {
    padding: 15px;
  }

  .dashboard-header {
    text-align: center;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
  }

  .tool-header {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }

  .activity-item {
    flex-direction: column;
    text-align: center;
  }
}

/* Animation */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tool-card, .stat-card {
  animation: fadeInUp 0.5s ease;
}
</style>
