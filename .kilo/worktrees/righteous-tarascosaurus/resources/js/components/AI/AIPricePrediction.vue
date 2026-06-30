<template>
  <div class="ai-price-prediction">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-calculator text-primary me-2"></i>
          AI Fiyat Tahmini
        </h5>
        <small class="text-muted">Yapay zeka destekli emlak değerleme</small>
      </div>

      <div class="card-body">
        <!-- Form -->
        <form @submit.prevent="predictPrice" class="prediction-form">
          <div class="row">
            <!-- Lokasyon -->
            <div class="col-md-4 mb-3">
              <label class="form-label">İl *</label>
              <select v-model="form.il" class="form-select" required>
                <option value="">İl Seçin</option>
                <option v-for="il in provinces" :key="il.id" :value="il.name">
                  {{ il.name }}
                </option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">İlçe *</label>
              <select v-model="form.ilce" class="form-select" required :disabled="!form.il">
                <option value="">İlçe Seçin</option>
                <option v-for="ilce in districts" :key="ilce.id" :value="ilce.name">
                  {{ ilce.name }}
                </option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Mahalle</label>
              <select v-model="form.mahalle" class="form-select" :disabled="!form.ilce">
                <option value="">Mahalle Seçin</option>
                <option v-for="mahalle in neighborhoods" :key="mahalle.id" :value="mahalle.name">
                  {{ mahalle.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="row">
            <!-- Emlak Türü -->
            <div class="col-md-6 mb-3">
              <label class="form-label">İlan Türü *</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" id="satilik" v-model="form.tur" value="satilik">
                <label class="btn btn-outline-primary" for="satilik">Satılık</label>

                <input type="radio" class="btn-check" id="kiralik" v-model="form.tur" value="kiralik">
                <label class="btn btn-outline-primary" for="kiralik">Kiralık</label>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Kategori *</label>
              <select v-model="form.kategori" class="form-select" required>
                <option value="">Kategori Seçin</option>
                <option value="konut">Konut</option>
                <option value="ticari">Ticari</option>
                <option value="arsa">Arsa</option>
              </select>
            </div>
          </div>

          <div class="row">
            <!-- Emlak Özellikleri -->
            <div class="col-md-4 mb-3">
              <label class="form-label">Metrekare *</label>
              <input
                type="number"
                v-model.number="form.metrekare"
                class="neo-input"
                placeholder="m²"
                min="1"
                required
              >
            </div>

            <div class="col-md-4 mb-3" v-if="form.kategori === 'konut'">
              <label class="form-label">Oda Sayısı</label>
              <select v-model="form.oda_sayisi" class="form-select">
                <option value="">Seçin</option>
                <option value="1+0">1+0</option>
                <option value="1+1">1+1</option>
                <option value="2+1">2+1</option>
                <option value="3+1">3+1</option>
                <option value="4+1">4+1</option>
                <option value="5+1">5+1</option>
                <option value="6+1">6+1 ve üzeri</option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="form-label">Yaş</label>
              <input
                type="number"
                v-model.number="form.yas"
                class="neo-input"
                placeholder="Bina yaşı"
                min="0"
                max="100"
              >
            </div>
          </div>

          <div class="row" v-if="form.kategori === 'konut'">
            <div class="col-md-6 mb-3">
              <label class="form-label">Kat</label>
              <input
                type="number"
                v-model.number="form.kat"
                class="neo-input"
                placeholder="Hangi kat"
                min="-5"
                max="100"
              >
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Özellikler</label>
              <div class="features-grid">
                <div class="form-check" v-for="feature in availableFeatures" :key="feature.id">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    :id="'feature-' + feature.id"
                    :value="feature.name"
                    v-model="form.ozellikler"
                  >
                  <label class="form-check-label" :for="'feature-' + feature.id">
                    {{ feature.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="d-grid">
            <button
              type="submit"
              class="btn neo-btn neo-btn-primary btn-lg"
              :disabled="loading || !isFormValid"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="fas fa-magic me-2"></i>
              {{ loading ? 'Tahmin Yapılıyor...' : 'Fiyat Tahmini Yap' }}
            </button>
          </div>
        </form>

        <!-- Results -->
        <div v-if="prediction" class="prediction-results mt-4">
          <div class="alert alert-success">
            <h6 class="alert-heading">
              <i class="fas fa-chart-line me-2"></i>
              Fiyat Tahmini Sonuçları
            </h6>

            <div class="row mt-3">
              <div class="col-md-4">
                <div class="result-card">
                  <div class="result-label">Minimum Fiyat</div>
                  <div class="result-value text-success">
                    {{ formatPrice(prediction.prediction.min_price) }}
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="result-card">
                  <div class="result-label">Ortalama Fiyat</div>
                  <div class="result-value text-primary">
                    {{ formatPrice(prediction.prediction.avg_price) }}
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="result-card">
                  <div class="result-label">Maksimum Fiyat</div>
                  <div class="result-value text-warning">
                    {{ formatPrice(prediction.prediction.max_price) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <div class="confidence-indicator">
                <span class="badge" :class="getConfidenceBadgeClass(prediction.prediction.confidence)">
                  {{ getConfidenceText(prediction.prediction.confidence) }}
                </span>
                <small class="text-muted ms-2">
                  {{ prediction.similar_count }} benzer emlak analiz edildi
                </small>
              </div>

              <div class="reasoning mt-2">
                <small class="text-muted">
                  <strong>Açıklama:</strong> {{ prediction.prediction.reasoning }}
                </small>
              </div>
            </div>

            <!-- Market Data -->
            <div v-if="prediction.market_data" class="market-data mt-3">
              <h6>Pazar Verileri</h6>
              <div class="row">
                <div class="col-md-6">
                  <small class="text-muted">
                    <strong>Ortalama m² Fiyatı:</strong>
                    {{ formatPrice(prediction.market_data.avg_price_per_sqm) }}
                  </small>
                </div>
                <div class="col-md-6">
                  <small class="text-muted">
                    <strong>Pazar Aralığı:</strong>
                    {{ formatPrice(prediction.market_data.price_range.min) }} -
                    {{ formatPrice(prediction.market_data.price_range.max) }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="alert alert-danger mt-4">
          <i class="fas fa-exclamation-triangle me-2"></i>
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AIService from '../../admin/services/AIService.js'

export default {
  name: 'AIPricePrediction',
  data() {
    return {
      loading: false,
      prediction: null,
      error: null,
      form: {
        il: '',
        ilce: '',
        mahalle: '',
        tur: 'satilik',
        kategori: '',
        metrekare: null,
        oda_sayisi: '',
        yas: null,
        kat: null,
        ozellikler: []
      },
      provinces: [],
      districts: [],
      neighborhoods: [],
      availableFeatures: [
        { id: 1, name: 'Asansör' },
        { id: 2, name: 'Balkon' },
        { id: 3, name: 'Güvenlik' },
        { id: 4, name: 'Otopark' },
        { id: 5, name: 'Havuz' },
        { id: 6, name: 'Spor Salonu' },
        { id: 7, name: 'Bahçe' },
        { id: 8, name: 'Teras' }
      ]
    }
  },

  computed: {
    isFormValid() {
      return this.form.il &&
             this.form.ilce &&
             this.form.tur &&
             this.form.kategori &&
             this.form.metrekare > 0;
    }
  },

  watch: {
    'form.il'() {
      this.form.ilce = '';
      this.form.mahalle = '';
      this.loadDistricts();
    },

    'form.ilce'() {
      this.form.mahalle = '';
      this.loadNeighborhoods();
    }
  },

  mounted() {
    this.loadProvinces();
  },

  methods: {
    async loadProvinces() {
      try {
        const url = window.APIConfig && window.APIConfig.location && window.APIConfig.location.provinces
          ? window.APIConfig.location.provinces
          : '/api/v1/location/provinces';
        const response = await axios.get(url);
        this.provinces = response.data.results || [];
      } catch (error) {
        console.error('Error loading provinces:', error);
      }
    },

    async loadDistricts() {
      if (!this.form.il) return;

      try {
        const url = window.APIConfig && window.APIConfig.location && window.APIConfig.location.districts
          ? window.APIConfig.location.districts(this.form.il)
          : `/api/v1/location/districts/${this.form.il}`;
        const response = await axios.get(url);
        this.districts = response.data.results || [];
      } catch (error) {
        console.error('Error loading districts:', error);
      }
    },

    async loadNeighborhoods() {
      if (!this.form.ilce) return;

      try {
        const url = window.APIConfig && window.APIConfig.location && window.APIConfig.location.neighborhoods
          ? window.APIConfig.location.neighborhoods(this.form.ilce)
          : `/api/v1/location/neighborhoods/${this.form.ilce}`;
        const response = await axios.get(url);
        this.neighborhoods = response.data.results || [];
      } catch (error) {
        console.error('Error loading neighborhoods:', error);
      }
    },

    async predictPrice() {
      if (!this.isFormValid) return;

      this.loading = true;
      this.error = null;
      this.prediction = null;

      try {
        const res = await AIService.pricePredict({ features: { ...this.form } }, { rateMs: 250 })
        if (res && (res.status === true || res.success === true)) {
          this.prediction = res.data || res

          // Analytics event
          this.$emit('prediction-made', {
            form: { ...this.form },
            result: this.prediction
          });
        } else {
          this.error = (res && res.message) || 'Fiyat tahmini yapılamadı.';
        }
      } catch (error) {
        console.error('Price prediction error:', error);

        if (error.response?.status === 429) {
          this.error = 'Çok fazla istek gönderdiniz. Lütfen biraz bekleyip tekrar deneyin.';
        } else if (error.response?.status === 422) {
          this.error = 'Lütfen tüm gerekli alanları doldurun.';
        } else {
          this.error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        }
      } finally {
        this.loading = false;
      }
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

    getConfidenceBadgeClass(confidence) {
      const classes = {
        'high': 'badge-success',
        'medium': 'badge-warning',
        'low': 'badge-danger'
      };
      return classes[confidence] || 'badge-secondary';
    },

    getConfidenceText(confidence) {
      const texts = {
        'high': 'Yüksek Güvenilirlik',
        'medium': 'Orta Güvenilirlik',
        'low': 'Düşük Güvenilirlik'
      };
      return texts[confidence] || 'Bilinmiyor';
    },

    resetForm() {
      this.form = {
        il: '',
        ilce: '',
        mahalle: '',
        tur: 'satilik',
        kategori: '',
        metrekare: null,
        oda_sayisi: '',
        yas: null,
        kat: null,
        ozellikler: []
      };
      this.prediction = null;
      this.error = null;
    }
  }
}
</script>

<style scoped>
.ai-price-prediction {
  max-width: 800px;
  margin: 0 auto;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: none;
}

.prediction-form {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 8px;
  max-height: 120px;
  overflow-y: auto;
}

.form-check {
  margin-bottom: 4px;
}

.form-check-label {
  font-size: 14px;
  cursor: pointer;
}

.prediction-results {
  animation: fadeInUp 0.5s ease;
}

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

.result-card {
  text-align: center;
  padding: 15px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 10px;
}

.result-label {
  font-size: 12px;
  color: #6c757d;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 5px;
}

.result-value {
  font-size: 18px;
  font-weight: bold;
}

.confidence-indicator {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.badge {
  font-size: 12px;
  padding: 6px 12px;
}

.badge-success { background-color: #28a745; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger { background-color: #dc3545; }
.badge-secondary { background-color: #6c757d; }

.market-data {
  border-top: 1px solid #dee2e6;
  padding-top: 15px;
}

.market-data h6 {
  color: #495057;
  margin-bottom: 10px;
}

.btn-group .btn {
  flex: 1;
}

.btn-check:checked + .btn {
  background-color: #667eea;
  border-color: #667eea;
}

/* Responsive */
@media (max-width: 768px) {
  .result-card {
    margin-bottom: 15px;
  }

  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .confidence-indicator {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* Loading animation */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Form validation */
.neo-input:invalid {
  border-color: #dc3545;
}

.neo-input:valid {
  border-color: #28a745;
}

/* Custom scrollbar for features */
.features-grid::-webkit-scrollbar {
  width: 4px;
}

.features-grid::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.features-grid::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}
</style>
