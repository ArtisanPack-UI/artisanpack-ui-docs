<aside id="sidebar-right" class="w-full md:w-[20rem] space-y-4">
    @if(isset($tableOfContents) && count($tableOfContents) > 0)
        <div class="bg-secondary-accent-gradient rounded-lg p-[1px] overflow-hidden max-h-[calc(100vh-6rem)]">
            <div class="bg-base-100 p-4 rounded-lg max-h-full overflow-y-auto">
                <x-artisanpack-heading level="2" class="mb-4">Table of Contents</x-artisanpack-heading>

                <nav id="toc-nav" class="space-y-1">
                    @foreach($tableOfContents as $heading)
                        @include('core::partials.toc-item', ['heading' => $heading])
                    @endforeach
                </nav>
            </div>
        </div>

        @push('scripts')
        <script>
            let tocObserver = null;

            function initializeTOC() {
                console.log('Initializing TOC highlighting...');

                // Clean up existing observer
                if (tocObserver) {
                    tocObserver.disconnect();
                    tocObserver = null;
                }

                // Create new observer
                tocObserver = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        const id = entry.target.getAttribute('id');
                        if (!id) return;

                        const tocLink = document.querySelector(`#toc-nav a[data-target="${id}"]`);

                        if (entry.isIntersecting) {
                            console.log(`Section intersecting: ${id}`, tocLink);

                            // Remove active class from all links
                            document.querySelectorAll('#toc-nav .toc-link').forEach(link => {
                                link.classList.remove('font-bold', 'bg-base-200');
                            });

                            // Add active class to current link
                            if (tocLink) {
                                tocLink.classList.add('font-bold', 'bg-base-200');
                                console.log(`TOC highlighting: ${id}`, 'Classes:', tocLink.className);
                            } else {
                                console.warn(`TOC link not found for: ${id}`);
                            }
                        }
                    });
                }, {
                    rootMargin: '-100px 0px -66%',
                    threshold: 0
                });

                // Track all headings with IDs
                const headings = document.querySelectorAll('#main h1[id], #main h2[id], #main h3[id], #main h4[id], #main h5[id], #main h6[id]');
                console.log(`Found ${headings.length} headings to track`);

                headings.forEach(heading => {
                    tocObserver.observe(heading);
                    console.log(`Observing heading: ${heading.id}`);
                });

                // Smooth scroll for TOC links
                document.querySelectorAll('.toc-link').forEach(link => {
                    // Remove existing listeners by cloning
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);

                    newLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('data-target');
                        const targetElement = document.getElementById(targetId);

                        if (targetElement) {
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                            // Update URL hash
                            history.pushState(null, null, `#${targetId}`);
                        }
                    });
                });
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', initializeTOC);

            // Re-initialize after Livewire navigation
            document.addEventListener('livewire:navigated', function() {
                console.log('Livewire navigated, re-initializing TOC...');
                // Wait a bit for Prism and other scripts to finish
                setTimeout(initializeTOC, 200);
            });
        </script>
        @endpush
    @endif

    <section class="bg-primary rounded-lg p-4 border border-secondary">
        <x-artisanpack-heading class="text-primary-content" level="2">Stay in the Loop</x-artisanpack-heading>

        <p class="text-primary-content">Get monthly tips, tutorials, and package updates. Plus a free Quick Start Cheat Sheet when you subscribe.</p>

        <a href="https://artisanpackui.dev" class="btn !inline-flex transition-colors duration-200 ease-in-out btn-md bg-secondary border-secondary text-secondary-content hover:bg-[var(--artisanpack-variant-hover-color)] hover:text-[var(--artisanpack-variant-hover-text)] focus:bg-[var(--artisanpack-variant-focus-color)] focus:text-[var(--artisanpack-variant-focus-text)] text-secondary-content! py-2! h-auto!" style="--artisanpack-variant-hover-color: #475569; --artisanpack-variant-hover-text: #ffffff; --artisanpack-variant-focus-color: #475569; --artisanpack-variant-focus-text: #ffffff;">
            Subscribe to The ArtisanPack UI Dispatch
            <span class="block">
                <svg class="inline w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2024 Fonticons, Inc. --><path d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"></path></svg>
            </span>
        </a>
    </section>
</aside>
