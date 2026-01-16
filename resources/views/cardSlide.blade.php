<style>
    ._carousel-container {
        overflow: hidden;
        width: 100%;
        /* padding: 20px 0; */

    }

    ._carousel-track {
        display: flex;
        width: max-content;
        gap: 1rem;
    }

    ._carousel-item {
        flex: 0 0 auto;
        width: 260px;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }

    ._carousel-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }

    ._carousel-title {
        position: absolute;
        bottom: 0;
        width: 100%;
        padding: 10px;
        color: white;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
        font-weight: bold;
    }

    /* Hide scrollbar */
    ._carousel-scroll {
        scrollbar-width: 11;
        /* Firefox */
        -ms-overflow-style: 11;
        /* IE 10+ */
    }

    /* .carousel-scroll::-webkit-scrollbar {
      display: none;
    } */
</style>
<div class="_carousel-container">
    <div id="carousel" class="_carousel-scroll" style="overflow-x: auto;">
        <div id="carouselTrack" class="_carousel-track">
            <!-- Cards (duplicated for infinite scroll) -->
        </div>
    </div>
</div>
@php
    $cards = collect(config('business_categories'))->map(function ($c) {
        $c['src'] = asset($c['src']);
        return $c;
    });

@endphp
<script>
    // ✅ 1. Define cards
    const cards = @json($cards);
    const carousel = document.getElementById('carousel');
    const track = document.getElementById('carouselTrack');
    let scrollSpeed = 0.5;

    // States for scrolling control
    let isUserScrolling = false;
    let isAutoScrolling = true;
    let scrollTimeout;

    function addCards(cardList) {
        for (const card of cardList) {
            const item = document.createElement('div');
            item.className = '_carousel-item';
            item.innerHTML = `
                <img src="${card.src}" alt="${card.title}">
                <div class="_carousel-title">${card.title}</div>
            `;
            track.appendChild(item);
        }
    }

    addCards(cards);
    addCards(cards);

    function autoScroll() {
        if (isAutoScrolling && !isUserScrolling) {
            carousel.scrollLeft += scrollSpeed;

            if (carousel.scrollLeft >= track.scrollWidth / 2) {
                carousel.scrollLeft = 0;
            }
        }
        // else do nothing (pause auto scroll)

        requestAnimationFrame(autoScroll);
    }

    requestAnimationFrame(autoScroll);


    carousel.addEventListener('wheel', () => {
        console.log('Mouse wheel used');

        // Pause auto scroll
        isUserScrolling = true;
        isAutoScrolling = false;

        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            isUserScrolling = false;
            isAutoScrolling = true;
        }, 3000);
    });


    // Listen for user scroll to pause auto scroll
    carousel.addEventListener('mousedown', () => {
        console.log('Mouse drag started');
        isUserScrolling = true;
        isAutoScrolling = false;
    });

    carousel.addEventListener('mouseup', () => {
        console.log('Mouse drag ended');

        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            isUserScrolling = false;
            isAutoScrolling = true;
        }, 1000);
    });
</script>
