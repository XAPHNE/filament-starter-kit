@props([
    'title' => null,
    'description' => null,
])

<div class="mb-8 flex flex-col items-center text-center">
    @if ($title)
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ $title }}
        </h2>
    @endif

    @if ($description)
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400 max-w-sm">
            {{ $description }}
        </p>
    @endif
</div>
