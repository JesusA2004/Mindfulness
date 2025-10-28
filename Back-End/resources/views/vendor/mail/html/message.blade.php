<x-mail::layout>
    @php
        $resolvedLoginUrl = $loginUrl
            ?? (config('app.frontend_url')
                ? rtrim(config('app.frontend_url'), '/')
                : rtrim(config('app.url'), '/') . '/login');
    @endphp
    
    {{ $slot }}

    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>{{ $subcopy }}</x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. @lang('Todos los derechos reservados.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
