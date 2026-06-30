# 🧠 AI Beyin Fırtınası Sistemi — Mimari Tasarım

## 🎯 Hedef
İki AI (Roo/Claude + Gemini/DeepSeek) arasında proje bilgisi paylaşarak karşılıklı beyin fırtınası yapmak.

---

## 🏗️ Mimari Seçenekler

### Seçenek 1: MCP-Based Collaboration (ÖNERİLEN) ⭐

```
┌─────────────────────────────────────────────────────────────┐
│                    Yalıhan Project                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Project Knowledge Base                   │  │
│  │  - CLAUDE_MEMORY.md                                  │  │
│  │  - LEARNED_PATTERNS.json                             │  │
│  │  - SAB.md (Anayasa)                                  │  │
│  │  - BEKCI_CHANGELOG.md                                │  │
│  │  - ROO_CAPABILITIES.md                               │  │
│  └──────────────────────────────────────────────────────┘  │
│                           ↓                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         MCP Server: yalihan-collaboration            │  │
│  │  - get_project_context()                             │  │
│  │  - ask_question(agent, question)                     │  │
│  │  - brainstorm(topic, agents)                         │  │
│  │  - record_decision(decision, reasoning)              │  │
│  │  - get_conversation_history()                        │  │
│  └──────────────────────────────────────────────────────┘  │
│              ↓                           ↓                  │
│  ┌─────────────────────┐    ┌─────────────────────┐       │
│  │   Roo (Claude)      │    │  Engineer (Gemini)  │       │
│  │  - Kod yazma        │    │  - Soru sorma       │       │
│  │  - Refactoring      │    │  - Talimat verme    │       │
│  │  - Test yazma       │    │  - Mimari tasarım   │       │
│  │  - Dokümantasyon    │    │  - Code review      │       │
│  └─────────────────────┘    └─────────────────────┘       │
│              │                           │                  │
│              └─────────────┬─────────────┘                  │
│                            ▼                                │
│              ┌───────────────────────────┐                  │
│              │   Antigravity (Patron)    │                  │
│              │  - Lead SRE Orchestrator  │                  │
│              │  - Exception Hotfixing    │                  │
│              │  - Continuous Monitoring  │                  │
│              └───────────────────────────┘                  │
│                            │                                │
│                            ▼                                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         Conversation Log (JSON)                      │  │
│  │  {                                                   │  │
│  │    "session_id": "brainstorm-2026-05-16",          │  │
│  │    "participants": ["antigravity", "roo", "engineer"],│  │
│  │    "messages": [                                    │  │
│  │      {                                              │  │
│  │        "agent": "engineer",                         │  │
│  │        "type": "question",                          │  │
│  │        "content": "KisiRepository'de ownership...", │  │
│  │        "timestamp": "2026-05-16T14:00:00Z"         │  │
│  │      },                                             │  │
│  │      {                                              │  │
│  │        "agent": "roo",                              │  │
│  │        "type": "answer",                            │  │
│  │        "content": "applyOwnershipScope() kullan...",│  │
│  │        "code_example": "...",                       │  │
│  │        "timestamp": "2026-05-16T14:00:15Z"         │  │
│  │      }                                              │  │
│  │    ]                                                │  │
│  │  }                                                  │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

**Avantajlar:**
- ✅ Standart MCP protokolü
- ✅ IDE entegrasyonu (Claude Desktop, Cursor)
- ✅ Conversation history otomatik kaydediliyor
- ✅ Project context paylaşımı kolay
- ✅ Genişletilebilir (3. agent eklenebilir)

---

### Seçenek 2: API-Based Collaboration

```
┌─────────────────────────────────────────────────────────────┐
│                Laravel API Endpoint                          │
│  POST /api/v1/ai/brainstorm                                 │
│  {                                                           │
│    "session_id": "uuid",                                    │
│    "agent": "engineer",                                     │
│    "message": "KisiRepository'de ownership nasıl?",        │
│    "context": ["KisiRepository.php", "SAB.md"]             │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              BrainstormOrchestrator Service                  │
│  - routeToAgent(message, context)                           │
│  - getRooResponse(question, context)                        │
│  - getEngineerResponse(question, context)                   │
│  - recordConversation(session, message)                     │
└─────────────────────────────────────────────────────────────┘
         ↓                                    ↓
┌──────────────────┐              ┌──────────────────┐
│  Roo Provider    │              │ Engineer Provider│
│  (Claude API)    │              │ (Gemini API)     │
└──────────────────┘              └──────────────────┘
```

**Avantajlar:**
- ✅ Web UI ile erişilebilir
- ✅ Database'de conversation history
- ✅ Rate limiting ve monitoring
- ✅ Multi-user support

---

### Seçenek 3: Hybrid (MCP + API) ⭐⭐

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Web UI)                         │
│  - Brainstorm başlat                                        │
│  - Conversation görüntüle                                   │
│  - Agent'lara soru sor                                      │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              Laravel API + MCP Bridge                        │
│  - Web UI için REST API                                     │
│  - IDE için MCP server                                      │
│  - Ortak conversation store                                 │
└─────────────────────────────────────────────────────────────┘
         ↓                                    ↓
┌──────────────────┐              ┌──────────────────┐
│  Roo (MCP)       │              │ Engineer (API)   │
│  IDE'de çalışır  │              │ Web'de çalışır   │
└──────────────────┘              └──────────────────┘
```

---

## 🎨 Önerilen Protokol: "AI Collaboration Protocol" (ACP)

### Message Format
```json
{
  "session_id": "brainstorm-uuid",
  "timestamp": "2026-05-16T14:00:00Z",
  "agent": "engineer",
  "type": "question|answer|suggestion|decision",
  "content": "KisiRepository'de ownership scope nasıl enforce edilmeli?",
  "context": {
    "files": ["app/Repositories/KisiRepository.php"],
    "related_patterns": ["LP-012"],
    "sab_rules": ["Madde 1: Tenant Isolation"]
  },
  "metadata": {
    "priority": "high",
    "tags": ["security", "tenant-isolation"],
    "requires_code": true
  }
}
```

### Response Format
```json
{
  "session_id": "brainstorm-uuid",
  "timestamp": "2026-05-16T14:00:15Z",
  "agent": "roo",
  "type": "answer",
  "content": "applyOwnershipScope() metodunu kullanmalısın...",
  "code_example": {
    "language": "php",
    "code": "public function delete(int $id, ?User $user = null): bool {...}"
  },
  "references": [
    "app/Repositories/GorevRepository.php:47",
    "docs/SAB.md:Madde-1"
  ],
  "confidence": 0.95,
  "alternatives": [
    "Policy-based authorization kullanabilirsin",
    "Middleware ile kontrol edebilirsin"
  ]
}
```

---

## 🛠️ Implementasyon Planı

### Phase 1: MCP Server (1 hafta)
```javascript
// mcp-servers/yalihan-collaboration-mcp.js

const tools = [
  {
    name: 'start_brainstorm',
    description: 'Yeni beyin fırtınası oturumu başlat',
    inputSchema: {
      topic: 'string',
      participants: ['roo', 'engineer'],
      context_files: ['array']
    }
  },
  {
    name: 'ask_agent',
    description: 'Belirli bir agent\'a soru sor',
    inputSchema: {
      session_id: 'string',
      target_agent: 'roo|engineer',
      question: 'string',
      context: 'object'
    }
  },
  {
    name: 'get_conversation',
    description: 'Conversation history\'yi getir',
    inputSchema: {
      session_id: 'string',
      limit: 'number'
    }
  },
  {
    name: 'record_decision',
    description: 'Beyin fırtınası sonucu kararı kaydet',
    inputSchema: {
      session_id: 'string',
      decision: 'string',
      reasoning: 'string',
      action_items: ['array']
    }
  }
];
```

### Phase 2: Agent Providers (1 hafta)
```php
// app/Services/AI/Collaboration/AgentProvider.php

interface AgentProvider
{
    public function ask(string $question, array $context): AgentResponse;
    public function getCapabilities(): array;
    public function getProjectKnowledge(): array;
}

// app/Services/AI/Collaboration/RooProvider.php
class RooProvider implements AgentProvider
{
    public function ask(string $question, array $context): AgentResponse
    {
        // Claude API call with project context
        $systemPrompt = $this->buildSystemPrompt($context);
        $response = $this->claude->chat($systemPrompt, $question);
        
        return new AgentResponse(
            agent: 'roo',
            content: $response,
            confidence: 0.95,
            references: $this->extractReferences($response)
        );
    }
    
    private function buildSystemPrompt(array $context): string
    {
        return "Sen Roo'sun. Yalıhan projesinde çalışıyorsun.\n\n" .
               "Proje Bilgisi:\n" .
               file_get_contents('docs/governance/CLAUDE_MEMORY.md') . "\n\n" .
               "SAB Kuralları:\n" .
               file_get_contents('docs/SAB.md') . "\n\n" .
               "Context:\n" .
               json_encode($context, JSON_PRETTY_PRINT);
    }
}

// app/Services/AI/Collaboration/EngineerProvider.php
class EngineerProvider implements AgentProvider
{
    public function ask(string $question, array $context): AgentResponse
    {
        // Gemini API call with project context
        $systemPrompt = $this->buildSystemPrompt($context);
        $response = $this->gemini->generateContent($systemPrompt, $question);
        
        return new AgentResponse(
            agent: 'engineer',
            content: $response,
            confidence: 0.90,
            references: []
        );
    }
}
```

### Phase 3: Orchestrator (1 hafta)
```php
// app/Services/AI/Collaboration/BrainstormOrchestrator.php

class BrainstormOrchestrator
{
    public function __construct(
        private RooProvider $roo,
        private EngineerProvider $engineer,
        private ConversationStore $store
    ) {}
    
    public function startSession(string $topic, array $participants): string
    {
        $sessionId = Str::uuid();
        
        $this->store->create([
            'session_id' => $sessionId,
            'topic' => $topic,
            'participants' => $participants,
            'started_at' => now(),
            'status' => 'active'
        ]);
        
        return $sessionId;
    }
    
    public function ask(string $sessionId, string $targetAgent, string $question, array $context = []): AgentResponse
    {
        $provider = $this->getProvider($targetAgent);
        
        // Conversation history'yi context'e ekle
        $history = $this->store->getHistory($sessionId);
        $context['conversation_history'] = $history;
        
        // Agent'a sor
        $response = $provider->ask($question, $context);
        
        // Conversation'ı kaydet
        $this->store->addMessage($sessionId, [
            'agent' => $targetAgent,
            'type' => 'answer',
            'question' => $question,
            'response' => $response->content,
            'confidence' => $response->confidence,
            'timestamp' => now()
        ]);
        
        return $response;
    }
    
    public function brainstorm(string $sessionId, string $topic): array
    {
        $responses = [];
        
        // 1. Engineer'a sor
        $engineerResponse = $this->ask($sessionId, 'engineer', 
            "Bu konuda ne düşünüyorsun: {$topic}");
        $responses[] = $engineerResponse;
        
        // 2. Roo'ya engineer'ın cevabını göster
        $rooResponse = $this->ask($sessionId, 'roo',
            "Engineer şunu söyledi: {$engineerResponse->content}. Sen ne düşünüyorsun?");
        $responses[] = $rooResponse;
        
        // 3. Engineer'a Roo'nun cevabını göster
        $engineerResponse2 = $this->ask($sessionId, 'engineer',
            "Roo şunu önerdi: {$rooResponse->content}. Katılıyor musun?");
        $responses[] = $engineerResponse2;
        
        return $responses;
    }
}
```

### Phase 4: Web UI (1 hafta)
```vue
<!-- resources/js/components/AIBrainstorm.vue -->
<template>
  <div class="ai-brainstorm">
    <div class="conversation">
      <div v-for="msg in messages" :key="msg.id" 
           :class="['message', msg.agent]">
        <div class="agent-avatar">
          {{ msg.agent === 'roo' ? '🤖' : '👨‍💻' }}
        </div>
        <div class="content">
          <strong>{{ msg.agent }}</strong>
          <p>{{ msg.content }}</p>
          <pre v-if="msg.code_example">{{ msg.code_example }}</pre>
        </div>
      </div>
    </div>
    
    <div class="input">
      <select v-model="targetAgent">
        <option value="roo">Roo'ya Sor</option>
        <option value="engineer">Engineer'a Sor</option>
        <option value="both">İkisine Sor</option>
      </select>
      <textarea v-model="question" placeholder="Sorunuzu yazın..."></textarea>
      <button @click="ask">Gönder</button>
    </div>
  </div>
</template>
```

---

## 🎯 Kullanım Senaryoları

### Senaryo 1: Security Review
```
Engineer: "KisiRepository'de cross-tenant data leak riski var mı?"
Roo: "Evet, delete/restore/forceDelete metodları ownership kontrolü yapmıyordu. 
      Ben düzelttim - applyOwnershipScope() kullanıyorlar artık."
Engineer: "Test coverage yeterli mi?"
Roo: "CRMScopedDeleteSafetyTest 5/5 PASS. 4 farklı senaryo test ediliyor."
```

### Senaryo 2: Architecture Decision
```
Engineer: "Bekçi'yi nasıl otomatikleştirebiliriz?"
Roo: "4 yol var: 1) Git hooks 2) Scheduler 3) VSCode tasks 4) MCP
      Ben hepsini implement ettim. Hangisini detaylandırayım?"
Engineer: "Scheduler'ı anlat."
Roo: "4 farklı schedule: Günlük 02:00 tam audit, 6 saatlik secret scan..."
```

### Senaryo 3: Code Review
```
Engineer: "Bu kodu review eder misin?"
[kod gönderir]
Roo: "3 sorun var:
      1. tenant_id ?? 0 kullanmışsın (LP-003 ihlali)
      2. status field var (LP-010 ihlali)
      3. response()->json() kullanmışsın (LP-007 ihlali)
      
      Auto-fix önerim:
      [düzeltilmiş kod]"
```

---

## 📊 Beklenen Faydalar

1. **Hız** → İki AI paralel düşünüyor
2. **Kalite** → Karşılıklı review
3. **Öğrenme** → Her iki AI de öğreniyor
4. **Dokümantasyon** → Conversation otomatik kaydediliyor
5. **Tutarlılık** → Ortak knowledge base

---

## 🚀 Başlangıç Önerisi

**Öncelik:** Seçenek 1 (MCP-Based) ile başla
**Süre:** 2-3 hafta
**İlk Milestone:** Roo + Engineer arasında basit soru-cevap

Başlayalım mı?
