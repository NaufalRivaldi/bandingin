@props([
    'identifier',
    'url',
    'title' => null,
])

@php
    $shortname = config('services.disqus.shortname');
@endphp

@if ($shortname)
    <section class="mx-auto mt-[clamp(40px,5vw,64px)] max-w-[720px] px-[clamp(16px,2.4vw,24px)]">
        <h2 class="font-display text-[clamp(18px,2vw,22px)] font-extrabold tracking-[-.026em]">Komentar</h2>

        <div id="disqus_thread" class="mt-[18px]"></div>

        <noscript>
            <p class="mt-3 text-[13.5px] text-muted">
                Aktifkan JavaScript untuk melihat
                <a href="https://disqus.com/?ref_noscript" rel="nofollow">komentar yang didukung Disqus</a>.
            </p>
        </noscript>
    </section>

    @push('scripts')
        <script>
            window.disqus_config = function () {
                this.page.url = @json($url);
                this.page.identifier = @json($identifier);
                @if ($title)
                    this.page.title = @json($title);
                @endif
            };

            (function () {
                const d = document;
                const s = d.createElement('script');

                s.src = 'https://{{ $shortname }}.disqus.com/embed.js';
                s.setAttribute('data-timestamp', String(+new Date()));
                (d.head || d.body).appendChild(s);
            })();
        </script>
    @endpush
@endif
