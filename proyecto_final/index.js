
        document.addEventListener('DOMContentLoaded', function() {
            const carouselImages = document.querySelectorAll('.carousel-image');
            const prevButton = document.querySelector('.carousel-prev');
            const nextButton = document.querySelector('.carousel-next');
            const indicators = document.querySelectorAll('.indicator');
            let currentIndex = 0;

            function showImage(index) {
                // Remove active class from all images and indicators
                carouselImages.forEach(img => img.classList.remove('active'));
                indicators.forEach(ind => ind.classList.remove('active'));
                
                // Add active class to current image and indicator
                carouselImages[index].classList.add('active');
                indicators[index].classList.add('active');
            }

            // Next image
            nextButton.addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % carouselImages.length;
                showImage(currentIndex);
            });

            // Previous image
            prevButton.addEventListener('click', function() {
                currentIndex = (currentIndex - 1 + carouselImages.length) % carouselImages.length;
                showImage(currentIndex);
            });

            // Indicator click
            indicators.forEach(indicator => {
                indicator.addEventListener('click', function() {
                    currentIndex = parseInt(this.getAttribute('data-slide'));
                    showImage(currentIndex);
                });
            });

            // Automatic slideshow
            setInterval(function() {
                currentIndex = (currentIndex + 1) % carouselImages.length;
                showImage(currentIndex);
            }, 5000); // Change image every 5 seconds
        });
    