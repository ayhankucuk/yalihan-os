@props(['verified' => false])

@if($verified)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 rounded-full bg-blue-900/30 border border-blue-500/50 text-blue-400 text-[10px] font-bold tracking-tighter uppercase cursor-help transition-all duration-700 ease-in-out transform scale-105 shadow-[0_0_10px_rgba(59,130,246,0.5)]']) }} 
         title="Cortex Precision Seal: AI decision verified by SQL Control Group (Zero-Drift Guarantee)">
        <svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        Precision Verified
    </div>
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 rounded-full bg-slate-800/50 border border-slate-700 text-slate-500 text-[10px] font-medium tracking-tighter uppercase transition-all duration-700']) }}>
        <span class="mr-1 h-1.5 w-1.5 rounded-full bg-slate-600 animate-pulse"></span>
        AI Optimized
    </div>
@endif

<style>
    /* Neural Pulse Animation - Phase 5 */
    .neural-pulse-skeleton {
        background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
        background-size: 200% 100%;
        animation: neural-pulse 2s infinite ease-in-out;
    }

    @keyframes neural-pulse {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .seal-transition {
        transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>
