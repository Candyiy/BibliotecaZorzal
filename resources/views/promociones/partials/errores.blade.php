@if ($errors->any() && old('_modal') === $modal)
    <div class="error-banner">
        <p class="font-medium">Revisa los datos:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
