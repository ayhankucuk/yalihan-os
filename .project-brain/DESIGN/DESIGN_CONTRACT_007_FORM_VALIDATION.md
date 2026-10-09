# Design Contract: DC-007 — Form Validation UX Standardization

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** MEDIUM
**Complexity:** MEDIUM

---

## Evidence

### FACT (Current Validation Patterns)

**Inline error styling:**
```blade
<!-- Pattern 1: Border + text -->
<input class="... @error('name') border-red-500 @enderror ...">
@error('name')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror

<!-- Pattern 2: Invalid pseudo-class -->
<input class="... invalid:border-red-500 ...">

<!-- Pattern 3: JavaScript alert -->
alert('Site başlığı zorunludur.');
```

### FACT (Inconsistent Error Colors)

| Location | Light Mode | Dark Mode |
|----------|------------|-----------|
| `danisman/create.blade.php` | `text-red-600` | `dark:text-red-400` |
| `ups/features/edit.blade.php` | `text-red-600` | `dark:text-red-400` |
| `kullanicilar/edit.blade.php` | `border-red-500` | `dark:border-red-500` |
| `page-analyzer/create.blade.php` | `border-red-500` | none |
| `input.blade.php` | `text-red-600` | none |

### FACT (Existing Input Component)

```blade
{{-- resources/views/components/admin/input.blade.php --}}
@props([
    'label' => null,
    'name',
    'type' => 'text',
    'required' => false,
    'help' => null,
    'value' => null,
    'error' => null,
    'wrapperClass' => 'mb-4',
])

<input class="... @error($name) border-red-500 @enderror ...">
@error($name)
    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
@enderror
```

**Problems:**
- No dark mode error text
- Limited field types
- No textarea/select support
- Error message styling inconsistent

### FACT (Form Validation Issues)

1. **Inline styles** — No reusable validation component
2. **JavaScript alerts** — Anti-pattern for form validation
3. **Inconsistent colors** — Mix of error styling approaches
4. **Missing dark mode** — Error text not styled for dark mode
5. **No inline validation** — Only server-side validation shown

---

## Karar

### PROPOSED: Unified Form Validation System

**Components to create:**
1. `form-field.blade.php` — Wrapper with label + input + error
2. `input.blade.php` — Enhanced with validation states
3. `textarea.blade.php` — Textarea with validation
4. `select.blade.php` — Select with validation
5. `checkbox.blade.php` — Checkbox with validation
6. `validation-error.blade.php` — Reusable error message
7. `form-alert.blade.php` — Success/error alert component

### Design Token System

```css
/* Error states */
--error-border: #EF4444;
--error-border-dark: #F87171;
--error-bg: #FEF2F2;
--error-bg-dark: #7F1D1D;
--error-text: #DC2626;
--error-text-dark: #FCA5A5;
--error-ring: rgba(239, 68, 68, 0.2);

/* Success states */
--success-border: #22C55E;
--success-border-dark: #4ADE80;
--success-bg: #F0FDF4;
--success-text: #16A34A;
--success-text-dark: #86EFAC;

/* Focus states */
--focus-ring: rgba(59, 130, 246, 0.3);
--focus-border: #3B82F6;
```

---

## Component Specifications

### 1. form-field.blade.php (Wrapper)

```blade
@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'hint' => null,
    'error' => null,
    'wrapperClass' => '',
])

<div class="relative {{ $wrapperClass }}">
    {{-- Label --}}
    @if($label)
        <label for="{{ $name }}"
               class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Input Slot --}}
    {{ $slot }}

    {{-- Hint --}}
    @if($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    {{-- Error Message --}}
    @if($error || $name)
        @error($name)
            <x-admin.validation-error :message="$message" />
        @enderror
    @endif
</div>
```

### 2. Enhanced input.blade.php

```blade
@props([
    'type' => 'text',
    'name' => null,
    'value' => null,
    'placeholder' => '',
    'disabled' => false,
    'readonly' => false,
    'error' => false, // Auto-detect error state
])

@php
    $hasError = $error || (($name || $attributes->has('wire:model')) && $errors->has($name ?? ''));
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name ?? $attributes->get('id') }}"
    value="{{ old($name, $value) }}"
    placeholder="{{ $placeholder }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $readonly ? 'readonly' : '' }}
    {{ $attributes->class([
        'w-full h-11 px-4 rounded-xl border text-sm transition-all',
        'bg-white dark:bg-slate-800 text-slate-900 dark:text-white',
        'placeholder-slate-400 dark:placeholder-slate-500',
        'border-slate-300 dark:border-slate-600',
        'focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
        'hover:border-slate-400 dark:hover:border-slate-500',
        $hasError ? 'border-red-500 dark:border-red-400 focus:ring-red-500/20 focus:border-red-500' : '',
        $disabled ? 'opacity-50 cursor-not-allowed' : '',
    ]) }}
>
```

### 3. validation-error.blade.php

```blade
@props([
    'message' => '',
    'class' => '',
])

@if($message)
    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5 {{ $class }}">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ $message }}
    </p>
@endif
```

### 4. form-alert.blade.php

```blade
@props([
    'type' => 'info', // info, success, warning, error
    'title' => null,
    'message' => null,
    'dismissible' => true,
])

@php
    $styles = match($type) {
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-900/20',
            'border' => 'border-green-200 dark:border-green-800',
            'icon' => 'text-green-600 dark:text-green-400',
            'title' => 'text-green-800 dark:text-green-200',
            'text' => 'text-green-700 dark:text-green-300',
        ],
        'error' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-200 dark:border-red-800',
            'icon' => 'text-red-600 dark:text-red-400',
            'title' => 'text-red-800 dark:text-red-200',
            'text' => 'text-red-700 dark:text-red-300',
        ],
        'warning' => [
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'icon' => 'text-yellow-600 dark:text-yellow-400',
            'title' => 'text-yellow-800 dark:text-yellow-200',
            'text' => 'text-yellow-700 dark:text-yellow-300',
        ],
        default => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'title' => 'text-blue-800 dark:text-blue-200',
            'text' => 'text-blue-700 dark:text-blue-300',
        ],
    };
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     class="rounded-xl border p-4 {{ $styles['bg'] }} {{ $styles['border'] }}">
    <div class="flex gap-3">
        {{-- Icon --}}
        <div class="flex-shrink-0 {{ $styles['icon'] }}">
            @if($type === 'success')
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            @elseif($type === 'error')
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            @elseif($type === 'warning')
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            @else
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            @if($title)
                <h4 class="text-sm font-semibold {{ $styles['title'] }}">{{ $title }}</h4>
            @endif
            @if($title && $message)
                <div class="mt-1 {{ $styles['text'] }}">{{ $slot }}</div>
            @elseif($slot->isNotEmpty())
                <div class="{{ $title ? 'mt-1' : '' }} {{ $styles['text'] }}">{{ $slot }}</div>
            @endif
        </div>

        {{-- Dismiss Button --}}
        @if($dismissible)
            <button type="button" @click="show = false"
                    class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity">
                <svg class="w-5 h-5 {{ $styles['text'] }}" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        @endif
    </div>
</div>
```

---

## Scope

### Files to CREATE

| File | Purpose |
|------|---------|
| `form-field.blade.php` | Label + input wrapper |
| `validation-error.blade.php` | Reusable error message |
| `form-alert.blade.php` | Alert component |
| `textarea.blade.php` | Textarea with validation |
| `select.blade.php` | Select with validation |
| `checkbox.blade.php` | Checkbox with validation |

### Files to MODIFY

| File | Changes |
|------|---------|
| `input.blade.php` | Enhance with error states + dark mode |

### Files to DEPRECATE

| Pattern | Replacement |
|---------|-------------|
| Inline `@error` styling | Use `form-field` wrapper |
| JavaScript `alert()` validation | Server-side validation |

---

## Usage Examples

### Before (Inconsistent)

```blade
<!-- Inline error styling -->
<input type="text" name="name"
       class="... @error('name') border-red-500 @enderror">
@error('name')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror

<!-- JavaScript alert -->
<script>alert('Zorunlu alan');</script>
```

### After (Standardized)

```blade
<!-- Using form-field wrapper -->
<x-admin.form-field label="İsim" name="name" required>
    <x-admin.input name="name" placeholder="İsim girin" />
</x-admin.form-field>

<!-- Using form-alert -->
<x-admin.form-alert type="error" title="Hata">
    Form gönderilemedi. Lütfen hataları düzeltin.
</x-admin.form-alert>

<!-- Using validation-error directly -->
@error('email')
    <x-admin.validation-error :message="$message" />
@enderror
```

### With Dark Mode

```blade
<!-- Error states automatically handle dark mode -->
<x-admin.form-field label="E-posta" name="email" required>
    <x-admin.input type="email" name="email" />
</x-admin.form-field>

<!-- When error occurs: -->
<!-- Light: border-red-500, text-red-600 -->
<!-- Dark: border-red-400, text-red-400 -->
```

---

## Migration Strategy

### Phase 1: Create Components (No breaking changes)
1. Create new component files
2. Add CSS tokens to `app.css`
3. Test in isolation

### Phase 2: Gradual Migration
1. Migrate one form at a time
2. Replace inline `@error` with `form-field`
3. Replace JavaScript alerts with `form-alert`

### Phase 3: Cleanup
1. Remove inline error styles from forms
2. Document new patterns

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Error text shows in dark mode | Test with dark mode |
| AC2 | Error border shows in dark mode | Test with dark mode |
| AC3 | Form field wrapper works | Use in a form, trigger error |
| AC4 | Alert component works | Render each type (success, error, warning, info) |
| AC5 | No JavaScript alerts in forms | Grep for `alert(` in forms |
| AC6 | Consistent error colors | Compare across forms |
| AC7 | Validation error icon shows | Trigger validation error |

---

## Files to CREATE (6)

```
resources/views/components/admin/
├── form-field.blade.php           (35 lines)
├── validation-error.blade.php     (15 lines)
├── form-alert.blade.php          (85 lines)
├── textarea.blade.php             (40 lines)
├── select.blade.php              (50 lines)
└── checkbox.blade.php           (35 lines)
```

## Files to MODIFY (1)

```
resources/views/components/admin/input.blade.php (enhance error states)
```

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Create validation-error | LOW | 1 |
| Create form-alert | MEDIUM | 1 |
| Create form-field | LOW | 1 |
| Create textarea/select/checkbox | MEDIUM | 3 |
| Enhance input component | LOW | 1 |
| Add CSS tokens | LOW | 1 |
| Test components | LOW | - |
| **TOTAL** | **MEDIUM** | **~8** |

---

## Rollback Plan

1. Keep old forms unchanged during migration
2. Only delete inline styles after full migration
3. Components are additive, no breaking changes

---

*YALIHAN TASARIMCI — DC-007 Design Contract v1.0*
