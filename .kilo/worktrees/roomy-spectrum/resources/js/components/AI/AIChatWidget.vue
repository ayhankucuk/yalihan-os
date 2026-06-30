<template>
  <div class="ai-chat-widget" :class="{ 'expanded': isExpanded }">
    <!-- Chat Toggle Button -->
    <button
      v-if="!isExpanded"
      @click="toggleChat"
      class="chat-toggle-btn"
      :disabled="loading"
    >
      <i class="fas fa-robot"></i>
      <span class="pulse-dot" v-if="hasNewMessage"></span>
    </button>

    <!-- Chat Window -->
    <div v-if="isExpanded" class="chat-window">
      <!-- Header -->
      <div class="chat-header">
        <div class="chat-title">
          <i class="fas fa-robot text-primary"></i>
          <span>Emlak Pro AI Asistan</span>
          <span class="status-indicator" :class="connectionStatus"></span>
        </div>
        <button @click="toggleChat" class="close-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Messages -->
<div class="chat-messages" ref="messagesContainer" role="log" aria-live="polite" aria-relevant="additions text">
        <div v-if="messages.length === 0" class="welcome-message">
          <div class="welcome-content">
            <i class="fas fa-robot text-primary mb-2"></i>
            <h5>Merhaba! 👋</h5>
            <p>Ben Emlak Pro AI asistanınızım. Size nasıl yardımcı olabilirim?</p>
            <div class="quick-actions">
              <button
                v-for="action in quickActions"
                :key="action.id"
                @click="sendQuickMessage(action.message)"
                class="quick-action-btn"
              >
                <i :class="action.icon"></i>
                {{ action.text }}
              </button>
            </div>
          </div>
        </div>

        <div
          v-for="message in messages"
          :key="message.id"
          class="message"
          :class="message.type"
        >
          <div class="message-content">
            <div class="message-text" v-html="formatMessage(message.text)"></div>
            <div class="message-time">{{ formatTime(message.timestamp) }}</div>
          </div>
        </div>

        <div v-if="loading" class="message ai">
          <div class="message-content">
            <div class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="chat-input">
        <div class="input-group">
          <input
            v-model="currentMessage"
            @keypress.enter="sendMessage"
            @input="handleTyping"
            placeholder="Mesajınızı yazın..."
            class="neo-input"
            :disabled="loading"
            maxlength="1000"
          >
          <button
            @click="sendMessage"
            class="btn neo-btn neo-btn-primary"
            :disabled="loading || !currentMessage.trim()"
          >
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
        <div class="input-footer">
          <small class="text-muted">
            {{ currentMessage.length }}/1000 karakter
          </small>
          <small class="text-muted">
            AI tarafından desteklenmektedir
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AIService from '../../admin/services/AIService.js'

export default {
  name: 'AIChatWidget',
  data() {
    return {
      isExpanded: false,
      loading: false,
      currentMessage: '',
      messages: [],
      hasNewMessage: false,
      connectionStatus: 'connected', // connected, disconnected, error
      quickActions: [
        {
          id: 1,
          text: 'Fiyat Tahmini',
          message: 'Bir emlak için fiyat tahmini yapabilir misin?',
          icon: 'fas fa-calculator'
        },
        {
          id: 2,
          text: 'İlan Yazma',
          message: 'İlan açıklaması yazmama yardım eder misin?',
          icon: 'fas fa-edit'
        },
        {
          id: 3,
          text: 'Pazar Analizi',
          message: 'Bölgesel pazar analizi hakkında bilgi verir misin?',
          icon: 'fas fa-chart-line'
        },
        {
          id: 4,
          text: 'Müşteri Eşleştirme',
          message: 'Müşteri taleplerini nasıl analiz edebilirim?',
          icon: 'fas fa-users'
        }
      ]
    }
  },
  mounted() {
    this.loadChatHistory();
  },
  methods: {
    toggleChat() {
      this.isExpanded = !this.isExpanded;
      if (this.isExpanded) {
        this.hasNewMessage = false;
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      }
    },

    async sendMessage() {
      if (!this.currentMessage.trim() || this.loading) return;

      const userMessage = {
        id: Date.now(),
        type: 'user',
        text: this.currentMessage.trim(),
        timestamp: new Date()
      };

      this.messages.push(userMessage);
      const messageText = this.currentMessage;
      this.currentMessage = '';
      this.loading = true;

      this.$nextTick(() => {
        this.scrollToBottom();
      });

      try {
        const res = await AIService.chat({ session_id: 'default', user_msg: messageText, context: this.getContext() }, { rateMs: 250 })
        if (res && (res.status === true || res.success === true)) {
          const aiMessage = {
            id: Date.now() + 1,
            type: 'ai',
            text: (res.data && (res.data.message || res.data.answer || res.message)) || res.message || 'Yanıt oluşturuldu',
            timestamp: new Date(),
            provider: res.provider || 'backend'
          };

          this.messages.push(aiMessage);
          this.connectionStatus = 'connected';
        } else {
          this.addErrorMessage('Üzgünüm, şu anda yanıt veremiyorum. Lütfen tekrar deneyin.');
        }
      } catch (error) {
        console.error('AI Chat Error:', error);
        this.connectionStatus = 'error';

        if (error.response?.status === 429) {
          this.addErrorMessage('Çok fazla istek gönderdiniz. Lütfen biraz bekleyip tekrar deneyin.');
        } else if (error.response?.status === 401) {
          this.addErrorMessage('Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.');
        } else {
          this.addErrorMessage('Bir hata oluştu. Lütfen tekrar deneyin.');
        }
      } finally {
        this.loading = false;
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      }
    },

    sendQuickMessage(message) {
      this.currentMessage = message;
      this.sendMessage();
    },

    addErrorMessage(text) {
      const errorMessage = {
        id: Date.now(),
        type: 'error',
        text: text,
        timestamp: new Date()
      };
      this.messages.push(errorMessage);
    },

    getContext() {
      // Sayfa bağlamını topla
      const context = {
        page: window.location.pathname,
        user_agent: navigator.userAgent,
        timestamp: new Date().toISOString()
      };

      // Eğer emlak detay sayfasındaysak, emlak bilgilerini ekle
      if (window.currentProperty) {
        context.property = window.currentProperty;
      }

      // Eğer müşteri sayfasındaysak, müşteri bilgilerini ekle
      if (window.currentCustomer) {
        context.customer = window.currentCustomer;
      }

      return context;
    },

    formatMessage(text) {
      // Markdown benzeri formatlamalar
      return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>')
        .replace(/`(.*?)`/g, '<code>$1</code>');
    },

    formatTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    scrollToBottom() {
      const container = this.$refs.messagesContainer;
      if (container) {
        container.scrollTop = container.scrollHeight;
      }
    },

    handleTyping() {
      // Typing indicator için gelecekte kullanılabilir
    },

    loadChatHistory() {
      // LocalStorage'dan chat geçmişini yükle
      const saved = localStorage.getItem('ai_chat_history');
      if (saved) {
        try {
          const history = JSON.parse(saved);
          // Son 10 mesajı yükle
          this.messages = history.slice(-10).map(msg => ({
            ...msg,
            timestamp: new Date(msg.timestamp)
          }));
        } catch (e) {
          console.warn('Chat history could not be loaded:', e);
        }
      }
    },

    saveChatHistory() {
      // Chat geçmişini kaydet (KVKK uyumlu olarak)
      try {
        const toSave = this.messages.slice(-20); // Son 20 mesaj
        localStorage.setItem('ai_chat_history', JSON.stringify(toSave));
      } catch (e) {
        console.warn('Chat history could not be saved:', e);
      }
    }
  },

  watch: {
    messages: {
      handler() {
        this.saveChatHistory();
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.ai-chat-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.chat-toggle-btn {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
  position: relative;
}

.chat-toggle-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.chat-toggle-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.pulse-dot {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 12px;
  height: 12px;
  background: #ff4757;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 71, 87, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 71, 87, 0); }
}

.chat-window {
  width: 380px;
  height: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-left: 8px;
}

.status-indicator.connected { background: #2ed573; }
.status-indicator.disconnected { background: #ffa502; }
.status-indicator.error { background: #ff4757; }

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.2s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.welcome-message {
  text-align: center;
  padding: 20px;
}

.welcome-content h5 {
  color: #2c3e50;
  margin-bottom: 8px;
}

.welcome-content p {
  color: #7f8c8d;
  margin-bottom: 20px;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.quick-action-btn {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.quick-action-btn:hover {
  background: #e9ecef;
  border-color: #667eea;
}

.message {
  display: flex;
  margin-bottom: 8px;
}

.message.user {
  justify-content: flex-end;
}

.message.ai {
  justify-content: flex-start;
}

.message.error {
  justify-content: center;
}

.message-content {
  max-width: 80%;
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
}

.message.user .message-content {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.message.ai .message-content {
  background: #f1f3f4;
  color: #2c3e50;
  border-bottom-left-radius: 4px;
}

.message.error .message-content {
  background: #ffe6e6;
  color: #d63031;
  border: 1px solid #fab1a0;
  text-align: center;
  font-size: 14px;
}

.message-text {
  line-height: 1.4;
  word-wrap: break-word;
}

.message-time {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 4px;
  text-align: right;
}

.message.ai .message-time {
  text-align: left;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 8px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #bdc3c7;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
  0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
  40% { transform: scale(1); opacity: 1; }
}

.chat-input {
  border-top: 1px solid #e9ecef;
  padding: 16px;
}

.input-group {
  display: flex;
  gap: 8px;
}

.input-group input {
  flex: 1;
  border: 1px solid #e9ecef;
  border-radius: 20px;
  padding: 12px 16px;
  outline: none;
  font-size: 14px;
}

.input-group input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-group button {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  cursor: pointer;
  transition: all 0.2s;
}

.input-group button:hover:not(:disabled) {
  transform: scale(1.05);
}

.input-group button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.input-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 12px;
}

/* Responsive */
@media (max-width: 480px) {
  .chat-window {
    width: calc(100vw - 40px);
    height: calc(100vh - 100px);
    position: fixed;
    bottom: 80px;
    right: 20px;
  }

  .chat-toggle-btn {
    width: 50px;
    height: 50px;
    font-size: 20px;
  }
}

/* Scrollbar styling */
.chat-messages::-webkit-scrollbar {
  width: 4px;
}

.chat-messages::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
