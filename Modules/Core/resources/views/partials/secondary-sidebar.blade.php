<aside id="sidebar-right" class="w-full md:w-[20rem]">
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
</aside>
