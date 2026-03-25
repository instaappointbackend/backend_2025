@php
    use App\Enums\NavbarLink;
@endphp
<style>
    .navbar-nav .nav-link {
        position: relative;
        font-weight: 500;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
        color: #0d6efd;
    }

    .navbar-nav .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
    }

    /* Modern animated underline */
    .navbar-nav .nav-link::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0%;
        height: 2px;
        background: #0d6efd;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .navbar-nav .nav-link.active::after {
        width: 60%;
    }
</style>
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
        let hash = window.location.pathname;

        if (hash === '/') {
            hash = window.location.hash;
        }
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
