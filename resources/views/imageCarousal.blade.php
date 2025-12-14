<style>
    .carousel-inner img {
        width: 300px;
        height: auto;
        object-fit: contain;
        image-rendering: auto;
        margin: 0 auto;
        display: block;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        background-color: rgb(79 79 79);
        /* dark semi-transparent background */
        border-radius: 50%;
        /* make it circular */
        padding: 10px;
        /* space around the icon */
    }

    .set-height {
        /* height: 450px !important; */
    }
</style>

<section class="bg-light py-4 ">
    <div class="container">
        <div id="imageCarousel" class="w-77 mx-auto carousel slide rounded shadow overflow-hidden" data-bs-ride="carousel">

            <!-- Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="0" class="active"
                    aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="1"
                    aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="2"
                    aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="3"
                    aria-label="Slide 4"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="4"
                    aria-label="Slide 5"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="5"
                    aria-label="Slide 6"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="6"
                    aria-label="Slide 7"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="7"
                    aria-label="Slide 8"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="8"
                    aria-label="Slide 9"></button>
                <button type="button" data-bs-target="#imageCarousel" data-bs-slide-to="9"
                    aria-label="Slide 10"></button>
            </div>

            <!-- Carousel Items -->
            <div class="carousel-inner">
                @php
                    $images = [
                        'img_1.png',
                        'img_2.png',
                        'img_3.png',
                        'img_4.png',
                        'img_5.png',
                        'img_6.png',
                        'img_7.png',
                        'img_8.png',
                        'img_9.png',
                        'img_10.png',
                    ];
                @endphp

                @foreach ($images as $index => $image)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ asset('carousalImages/' . $image) }}" class="d-block w-100 set-height"
                            alt="Image {{ $index + 1 }}">
                    </div>
                @endforeach
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

        </div>
    </div>
</section>
