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

<script>
    // ✅ 1. Define cards
    const cards = [{
            title: "Advocate",
            src: "{{ asset('cardSlid/Advocate.png') }}"
        },
        {
            title: "Agriculture",
            src: "{{ asset('cardSlid/Agriculture.png') }}"
        },
        {
            title: "Arts & Entertainment",
            src: "{{ asset('cardSlid/Arts-&-Entertainment.png') }}"
        },
        {
            title: "Automotive",
            src: "{{ asset('cardSlid/Automotive.png') }}"
        },
        {
            title: "Business Consultant",
            src: "{{ asset('cardSlid/Business-Consultant.png') }}"

        },
        {
            title: "Carpenter",
            src: "{{ asset('cardSlid/Carpenter.png') }}"

        },
        {
            title: "Dietitian",
            src: "{{ asset('cardSlid/Dietitiatian.png') }}"
        },
        {
            title: "DJ Service",
            src: "{{ asset('cardSlid/DJ Service.png') }}"
        },
        {
            title: "Driving Instructor",
            src: "{{ asset('cardSlid/Driving Instructor.png') }}"
        },
        {
            title: "E-commerce services",
            src: "{{ asset('cardSlid/E-commerce services.png') }}"
        }, {
            title: "Finance & Insurance",
            src: "{{ asset('cardSlid/Finance & Insurance.png') }}"
        },
        {
            title: "food and be",
            src: "{{ asset('cardSlid/food-and-be.webp') }}"
        },
        {
            title: "Gardening",
            src: "{{ asset('cardSlid/Gardening.png') }}"
        },
        {
            title: "Gym Personal Trainer",
            src: "{{ asset('cardSlid/Gym Personal Trainer.png') }}"
        },
        {
            title: "Health and Wellness",
            src: "{{ asset('cardSlid/Health and Wellness.png') }}"
        },
        {
            title: "House Cleaning",
            src: "{{ asset('cardSlid/House Cleaning.png') }}"
        },
        {
            title: "Manufacturing",
            src: "{{ asset('cardSlid/Manufacturing.png') }}"
        },
        {
            title: "Mobile Repairing",
            src: "{{ asset('cardSlid/Mobile Repairing.png') }}"
        },
        {
            title: "Pet Grooming",
            src: "{{ asset('cardSlid/Pet Grooming.png') }}"
        },
        {
            title: "Photo,Videography",
            src: "{{ asset('cardSlid/Photo,Videography.png') }}"
        },
        {
            title: "Plumber",
            src: "{{ asset('cardSlid/Plumber.png') }}"
        },
        {
            title: "Real Estate",
            src: "{{ asset('cardSlid/Real Estate.png') }}"
        },
        {
            title: "Sofa Cleaning",
            src: "{{ asset('cardSlid/Sofa Cleaning.png') }}"
        },
        {
            title: "Technology",
            src: "{{ asset('cardSlid/Technology.png') }}"
        },
        {
            title: "Tiles & Sanitary",
            src: "{{ asset('cardSlid/Tiles & Sanitary.png') }}"
        },
        {
            title: "Travel & hospitality",
            src: "{{ asset('cardSlid/Travel & hospitality.png') }}"
        },
        {
            title: "Water Purifier",
            src: "{{ asset('cardSlid/Water Purifier.png') }}"
        }
    ];
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
