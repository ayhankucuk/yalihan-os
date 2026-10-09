# Design Contract: DC-008 — AI Design Language

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** HIGH

---

## Evidence

### FACT (Current AI Components)

| Component | Location | Design System |
|-----------|----------|---------------|
| `ai-widget.blade.php` | `admin/components/` | Blue gradient, Alpine.js states |
| `context7-components.blade.php` | `admin/components/` | Context7 design tokens |
| `copilot.blade.php` | `advisor/` | Dark theme (--cp-*) |
| `ai-settings/index.blade.php` | `admin/ai-settings/` | Purple/blue gradient |

### FACT (Context7 Design System)

```blade
{{-- context7-components.blade.php --}}
{{-- CSS Variables --}}
$gradientColors = [
    'blue' => 'bg-gradient-to-r from-blue-500 to-indigo-600',
    'green' => 'bg-gradient-to-r from-green-500 to-emerald-600',
    'purple' => 'bg-gradient-to-r from-purple-500 to-violet-600',
    ...
];

{{-- Component Classes --}}
$btnPrimaryClass = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600...';
$statsCardClass = 'bg-white dark:bg-gray-800 rounded-xl border...';
```

### FACT (Copilot Dark Theme)

```blade
{{-- copilot.blade.php --}}
:root {
    --cp-bg: #0f172a;
    --cp-surface: #1e293b;
    --cp-accent: #3b82f6;
    --cp-accent-glow: rgba(59, 130, 246, 0.15);
    --cp-success: #10b981;
    --cp-warning: #f59e0b;
    --cp-danger: #ef4444;
    --cp-text: #f1f5f9;
    --cp-muted: #94a3b8;
    --cp-border: rgba(148, 163, 184, 0.1);
}
```

### FACT (AI Widget States)

```blade
{{-- ai-widget.blade.php --}}
{{-- States: loading, success, error, idle --}}
<div x-show="status === 'loading'">
    <div class="ai-loading-spinner..."></div>
    <p class="text-gray-600" x-text="loadingMessage"></p>
</div>

<div x-show="status === 'success'">
    <div class="ai-result" x-html="result"></div>
</div>

<div x-show="status === 'error'">
    <p class="text-gray-600 mb-4" x-text="errorMessage"></p>
    <button @click="retry()">Tekrar Dene</button>
</div>
```

### FACT (Design System Issues)

1. **Multiple Design Systems** — Context7, Copilot, AI Widget all different
2. **Inconsistent Color Schemes** — Blue, purple, dark themes
3. **No Unified Token System** — CSS variables scattered across files
4. **Component Duplication** — Same patterns repeated differently
5. **No AI Interaction Pattern Standard** — Each page implements differently

---

## Karar

### UNIFIED AI DESIGN LANGUAGE

**Design Philosophy:** "Professional AI, not Sci-Fi AI"
- Clean, minimal interfaces
- Trust-building visual cues
- Clear state communication
- Accessible and performant

### Design Token System

```css
/* AI Design Tokens */
:root {
    /* Surfaces */
    --ai-bg: #F8FAFC;
    --ai-surface: #FFFFFF;
    --ai-surface-elevated: #FFFFFF;
    --ai-border: #E2E8F0;
    --ai-border-subtle: #F1F5F9;

    /* Text */
    --ai-text-primary: #0F172A;
    --ai-text-secondary: #475569;
    --ai-text-muted: #94A3B8;

    /* Brand */
    --ai-brand: #6366F1;        /* Indigo */
    --ai-brand-light: #818CF8;
    --ai-brand-glow: rgba(99, 102, 241, 0.15);

    /* Status */
    --ai-success: #10B981;
    --ai-success-bg: #ECFDF5;
    --ai-warning: #F59E0B;
    --ai-warning-bg: #FFFBEB;
    --ai-error: #EF4444;
    --ai-error-bg: #FEF2F2;
    --ai-info: #3B82F6;
    --ai-info-bg: #EFF6FF;

    /* AI Specific */
    --ai-thinking: #8B5CF6;      /* Purple for thinking states */
    --ai-thinking-bg: #F5F3FF;
    --ai-glow: rgba(99, 102, 241, 0.25);
}

/* Dark Mode */
.dark {
    --ai-bg: #0F172A;
    --ai-surface: #1E293B;
    --ai-surface-elevated: #334155;
    --ai-border: #334155;
    --ai-border-subtle: #1E293B;
    --ai-text-primary: #F8FAFC;
    --ai-text-secondary: #CBD5E1;
    --ai-text-muted: #64748B;
    --ai-brand: #818CF8;
    --ai-brand-light: #A5B4FC;
}
```

### AI Interaction Patterns

| Pattern | Use Case | Components |
|---------|----------|------------|
| **AI Widget** | Single action analysis | `ai-widget.blade.php` |
| **AI Copilot** | Multi-turn chat | `ai-copilot.blade.php` |
| **AI Inline** | Inline suggestions | `ai-inline.blade.php` |
| **AI Toast** | Background notifications | `ai-toast.blade.php` |

---

## Component Specifications

### 1. ai-widget.blade.php

Universal AI analysis widget with states:

```blade
@props([
    'title' => 'AI Analiz',
    'description' => null,
    'action' => 'analyze',
    'endpoint' => null,
    'data' => [],
    'compact' => false,
])

<div class="ai-widget"
     x-data="aiWidget({ endpoint, action, data })">
    {{-- Header --}}
    <div class="ai-widget-header">
        <div class="ai-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor">
                <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <div>
            <h3 class="ai-title">{{ $title }}</h3>
            @if($description)
                <p class="ai-description">{{ $description }}</p>
            @endif
        </div>
        <div class="ai-status">
            <span class="ai-status-dot"></span>
            <span class="ai-status-text">Hazır</span>
        </div>
    </div>

    {{-- Content (collapsible) --}}
    <div class="ai-widget-content" x-show="expanded">
        {{-- States: loading, success, error, idle --}}
        {{ $slot }}
    </div>

    {{-- Actions --}}
    <div class="ai-widget-footer">
        <button x-show="!expanded || status === 'idle'"
                @click="analyze()"
                class="ai-btn-primary">
            <span x-show="status !== 'loading'">Analiz Et</span>
            <span x-show="status === 'loading'" class="ai-spinner"></span>
        </button>
    </div>
</div>
```

### 2. ai-copilot.blade.php

Multi-turn chat interface:

```blade
@props([
    'title' => 'AI Asistan',
    'endpoint' => null,
    'context' => [],
])

<div class="ai-copilot"
     x-data="aiCopilot({ endpoint, context })">
    {{-- Header --}}
    <div class="ai-copilot-header">
        <div class="ai-avatar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor">
                <path d="M9.663 17h4.673..."/>
            </svg>
        </div>
        <div>
            <h3>{{ $title }}</h3>
            <span class="ai-status-text">Çevrimiçi</span>
        </div>
    </div>

    {{-- Messages --}}
    <div class="ai-copilot-messages" x-ref="messages">
        <template x-for="message in messages" :key="message.id">
            <div class="ai-message"
                 :class="{ 'ai-message-user': message.role === 'user', 'ai-message-assistant': message.role === 'assistant' }">
                <div class="ai-message-content" x-html="message.content"></div>
                <div class="ai-message-time" x-text="message.time"></div>
            </div>
        </template>

        {{-- Thinking indicator --}}
        <div x-show="thinking" class="ai-thinking">
            <div class="ai-thinking-dots">
                <span></span><span></span><span></span>
            </div>
            <span>AI düşünüyor...</span>
        </div>
    </div>

    {{-- Input --}}
    <div class="ai-copilot-input">
        <textarea x-model="input"
                 @keydown.enter.ctrl="send()"
                 placeholder="Mesajınızı yazın... (Ctrl+Enter)"></textarea>
        <button @click="send()" :disabled="!input.trim()">
            <svg>...</svg>
        </button>
    </div>
</div>
```

### 3. ai-inline.blade.php

Inline AI suggestion component:

```blade
@props([
    'suggestion' => null,
    'confidence' => null,
    'type' => 'suggestion', // suggestion, warning, info
])

<div class="ai-inline ai-inline-{{ $type }}"
     x-data="{ expanded: false }">
    <div class="ai-inline-header" @click="expanded = !expanded">
        <div class="ai-inline-icon">
            @if($type === 'suggestion')
                <svg class="w-4 h-4"><!-- lightbulb --></svg>
            @elseif($type === 'warning')
                <svg class="w-4 h-4"><!-- warning --></svg>
            @endif
        </div>
        <div class="ai-inline-content">
            <span class="ai-inline-title">{{ $title ?? 'AI Önerisi' }}</span>
            @if($confidence)
                <span class="ai-inline-confidence">{{ $confidence }}% güven</span>
            @endif
        </div>
        <svg class="ai-inline-chevron" :class="{ 'rotate-180': expanded }">...</svg>
    </div>
    <div class="ai-inline-body" x-show="expanded">
        {{ $slot }}
    </div>
</div>
```

### 4. ai-toast.blade.php

Background AI notification:

```blade
@props([
    'title' => null,
    'message' => null,
    'type' => 'info', // success, error, warning, info
    'action' => null,
    'actionUrl' => null,
])

<div x-data="{ show: true }"
     x-show="show"
     x-transition
     class="ai-toast ai-toast-{{ $type }}">
    <div class="ai-toast-icon">
        @if($type === 'success')
            <svg class="w-5 h-5"><!-- check --></svg>
        @elseif($type === 'error')
            <svg class="w-5 h-5"><!-- x --></svg>
        @endif
    </div>
    <div class="ai-toast-content">
        @if($title)
            <p class="ai-toast-title">{{ $title }}</p>
        @endif
        <p class="ai-toast-message">{{ $message ?? $slot }}</p>
    </div>
    @if($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="ai-toast-action">{{ $action }}</a>
    @endif
    <button @click="show = false" class="ai-toast-close">×</button>
</div>
```

---

## State Definitions

### AI Widget States

| State | Icon | Color | Action |
|-------|------|-------|--------|
| **Idle** | Play | Gray | Start analysis |
| **Loading** | Spinner | Brand | Show progress |
| **Success** | Check | Green | Show result |
| **Error** | Alert | Red | Retry/Report |

### AI Copilot States

| State | Visual | Behavior |
|-------|--------|----------|
| **Ready** | Avatar visible | Accept input |
| **Thinking** | Animated dots | Show progress |
| **Responding** | Message appearing | Stream output |
| **Error** | Error message | Retry option |

---

## CSS Specifications

### AI Widget Styles

```css
.ai-widget {
    @apply bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700;
    @apply shadow-sm dark:shadow-none overflow-hidden;
}

.ai-widget-header {
    @apply flex items-center gap-4 px-4 py-3 bg-slate-50 dark:bg-slate-800/50;
    @apply border-b border-slate-100 dark:border-slate-700;
}

.ai-icon {
    @apply w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30;
    @apply flex items-center justify-center text-indigo-600 dark:text-indigo-400;
}

.ai-title {
    @apply text-sm font-semibold text-slate-900 dark:text-white;
}

.ai-description {
    @apply text-xs text-slate-500 dark:text-slate-400;
}

.ai-status-dot {
    @apply w-2 h-2 rounded-full;
    @apply bg-green-500;
}

.ai-btn-primary {
    @apply inline-flex items-center justify-center gap-2;
    @apply px-4 py-2 bg-indigo-600 text-white rounded-lg;
    @apply hover:bg-indigo-700 transition-colors;
    @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.ai-spinner {
    @apply w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin;
}
```

### AI Copilot Styles

```css
.ai-copilot {
    @apply flex flex-col h-full bg-slate-900 rounded-xl overflow-hidden;
}

.ai-copilot-header {
    @apply flex items-center gap-3 px-4 py-3;
    @apply border-b border-slate-700/50;
}

.ai-avatar {
    @apply w-10 h-10 rounded-full bg-indigo-600;
    @apply flex items-center justify-center text-white;
}

.ai-message {
    @apply max-w-[85%] rounded-2xl px-4 py-2 mb-3;
}

.ai-message-user {
    @apply ml-auto bg-indigo-600 text-white rounded-br-md;
}

.ai-message-assistant {
    @apply bg-slate-800 text-slate-100 rounded-bl-md;
}

.ai-thinking {
    @apply flex items-center gap-3 px-4 py-3;
    @apply text-slate-400 text-sm;
}

.ai-thinking-dots span {
    @apply w-2 h-2 bg-slate-500 rounded-full animate-bounce;
    animation-delay: 0ms;
}
```

---

## Accessibility

| Feature | Implementation |
|---------|---------------|
| Screen readers | `aria-live="polite"` for status updates |
| Keyboard | Tab navigation, Enter to submit |
| Focus states | Visible focus rings on all interactive elements |
| Color contrast | 4.5:1 minimum for text |
| Motion | Respect `prefers-reduced-motion` |

---

## Scope

### Files to CREATE

| Component | Purpose |
|-----------|---------|
| `ai-widget.blade.php` | Universal AI analysis widget |
| `ai-copilot.blade.php` | Multi-turn chat interface |
| `ai-inline.blade.php` | Inline AI suggestions |
| `ai-toast.blade.php` | Background notifications |

### Files to MODIFY

| File | Changes |
|------|---------|
| `ai-widget.blade.php` | Enhance to match design language |
| `copilot.blade.php` | Adopt unified tokens |
| `context7-components.blade.php` | Deprecate in favor of new system |

### Files to DEPRECATE

| Pattern | Replacement |
|---------|-------------|
| Inline `--cp-*` variables | `--ai-*` tokens |
| Blue/purple gradients | Brand indigo system |
| Mixed component patterns | Unified AI components |

---

## Migration Strategy

### Phase 1: Create AI Design System
1. Define `--ai-*` CSS tokens
2. Create base component structure
3. Document component API

### Phase 2: Migrate AI Widget
1. Update `ai-widget.blade.php`
2. Replace blue gradient with brand system
3. Add dark mode support

### Phase 3: Migrate Copilot
1. Update `copilot.blade.php`
2. Replace `--cp-*` with `--ai-*`
3. Adopt unified message patterns

### Phase 4: Deprecate Context7
1. Update references to new system
2. Document migration guide
3. Remove old files after full migration

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Unified AI design tokens | `--ai-*` variables defined in CSS |
| AC2 | AI Widget component works | Render widget, trigger analysis |
| AC3 | AI Copilot component works | Send message, receive response |
| AC4 | AI Inline component works | Show inline suggestion |
| AC5 | AI Toast component works | Trigger toast notification |
| AC6 | Dark mode works | Enable dark mode, verify all components |
| AC7 | Loading states work | Trigger loading, verify spinner |
| AC8 | Error states work | Trigger error, verify error UI |
| AC9 | Accessibility compliant | Tab navigation, ARIA labels |

---

## Files to CREATE (4)

```
resources/views/components/admin/ai/
├── ai-widget.blade.php         (100 lines)
├── ai-copilot.blade.php        (150 lines)
├── ai-inline.blade.php         (50 lines)
└── ai-toast.blade.php          (40 lines)
```

### CSS Additions

```
resources/css/admin/ai-design-system.css  (150 lines)
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Define AI design tokens | LOW | 1 |
| Create ai-widget | MEDIUM | 1 |
| Create ai-copilot | HIGH | 1 |
| Create ai-inline | LOW | 1 |
| Create ai-toast | LOW | 1 |
| Update existing AI pages | MEDIUM | 5 |
| Deprecate Context7 | LOW | 1 |
| **TOTAL** | **HIGH** | **~10** |

---

## Rollback Plan

1. Keep old components in separate directory
2. Feature flag new components
3. Gradual rollout per page

---

*YALIHAN TASARIMCI — DC-008 Design Contract v1.0*
