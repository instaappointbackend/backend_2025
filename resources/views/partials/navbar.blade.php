@php
    use App\Enums\NavbarLink;
@endphp

<ul class="navbar-nav ms-auto">
    @foreach (NavbarLink::cases() as $link)
        <li class="nav-item">
            <a class="nav-link {{ $link->isActive() ? 'active fw-semibold' : '' }}" href="{{ $link->url() }}"
                data-hash="{{ $link->value }}">
                {{ $link->label() }}
            </a>
        </li>
    @endforeach

    <!-- CTA Button -->
    {{-- <li class="nav-item ms-lg-3">
        <a class="btn btn-primary" href="#">
            Get App
        </a>
    </li> --}}
</ul>

<script>
    function setActiveHash() {
        const hash = window.location.hash;

        document.querySelectorAll('[data-hash]').forEach(link => {
            link.classList.remove('active');

            if (link.dataset.hash === hash) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('hashchange', setActiveHash);
    window.addEventListener('load', setActiveHash);
</script>
