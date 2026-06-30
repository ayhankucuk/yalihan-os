<script>
        // ===================================
        // Sticky Navigation - Active Section Highlight
        // Context7: UX iyileştirmesi - Aktif bölüm highlight
        // ===================================
        (function() {
            const sections = document.querySelectorAll('[id^="section-"]');
            const navLinks = document.querySelectorAll('.section-nav-link');

            // Smooth scroll for navigation links
            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) {
                        const offsetTop = targetSection.offsetTop - 100; // Account for sticky nav
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Update active section on scroll
            function updateActiveSection() {
                const scrollPosition = window.scrollY + 150; // Offset for sticky nav

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.offsetHeight;
                    const sectionId = section.getAttribute('id');

                    if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                        // Remove active class from all links
                        navLinks.forEach(link => {
                            link.classList.remove('bg-blue-100', 'dark:bg-blue-900/30',
                                'border-blue-500', 'dark:border-blue-500', 'text-blue-700',
                                'dark:text-blue-300', 'font-semibold');
                        });

                        // Add active class to current section link
                        const activeLink = document.querySelector(
                            `.section-nav-link[data-section="${sectionId}"]`);
                        if (activeLink) {
                            activeLink.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-500',
                                'dark:border-blue-500', 'text-blue-700', 'dark:text-blue-300',
                                'font-semibold');
                        }
                    }
                });
            }

            // Throttle scroll event
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(updateActiveSection, 50);
            });

            // Initial update
            updateActiveSection();
        })();
</script>
