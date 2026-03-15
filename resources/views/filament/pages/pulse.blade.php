<x-filament-panels::page>
    <style>
        /* Hide the main Filament content scrollbar to prevent double scrolling */
        .fi-main-ctn { overflow: hidden !important; }
        .fi-main { overflow: hidden !important; }
        .fi-content { padding-bottom: 0 !important; height: 100% !important; }
        .fi-page-header { margin-bottom: 1rem !important; }
    </style>

    <div class="w-full rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm" style="height: calc(100vh - 11rem);">
        <iframe src="/pulse" class="w-full h-full border-none"></iframe>
    </div>
</x-filament-panels::page>
