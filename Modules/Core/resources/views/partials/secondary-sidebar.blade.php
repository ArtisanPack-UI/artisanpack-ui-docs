<aside id="sidebar-right" class="w-full md:w-[20rem] bg-base-100 h-full sticky top-24">
    @if(isset($tableOfContents) && count($tableOfContents) > 0)
        <div class="bg-secondary-accent-gradient rounded-lg p-[1px] overflow-hidden">
            <div class="bg-base-100 p-4 rounded-lg">
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
            document.addEventListener('DOMContentLoaded', function() {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        const id = entry.target.getAttribute('id');
                        const tocLink = document.querySelector(`a[data-target="${id}"]`);

                        if (entry.isIntersecting) {
                            // Remove active class from all links
                            document.querySelectorAll('.toc-link').forEach(link => {
                                link.classList.remove('font-bold', 'bg-base-200');
                            });

                            // Add active class to current link
                            if (tocLink) {
                                tocLink.classList.add('font-bold', 'bg-base-200');
                            }
                        }
                    });
                }, {
                    rootMargin: '-100px 0px -66%',
                    threshold: 0
                });

                // Track all headings with IDs
                document.querySelectorAll('h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]').forEach(heading => {
                    observer.observe(heading);
                });

                // Smooth scroll for TOC links
                document.querySelectorAll('.toc-link').forEach(link => {
                    link.addEventListener('click', function(e) {
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
            });
        </script>
        @endpush
    @endif
</aside>
