@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="z-auth-title">{{ $title }}</h1>
    <p class="z-auth-description">{{ $description }}</p>
</div>
