<header id="header" class="w-full bg-base-100 min-h-[60px] py-3 border-b-primary border-b sticky top-0 z-50">
    <div class="mx-auto max-w-[90rem] px-4 flex justify-between items-center gap-2 md:gap-4 flex-wrap md:flex-nowrap">
        <a href="{{ route('home') }}" class="text-xl font-bold shrink-0">
            <img src="{{ asset('images/artisanpack-ui-wordmark-light@3x.png') }}" alt="ArtisanPack Logo" class="max-h-[30px] hidden dark:block">
            <img src="{{ asset('images/artisanpack-ui-wordmark-dark-color@3x.png') }}" alt="ArtisanPack Logo" class="max-h-[30px] block dark:hidden">
            <span class="sr-only">ArtisanPack UI</span>
        </a>

        <div class="flex-1 min-w-0 order-3 md:order-2 w-full md:w-auto">
            <x-artisanpack-input
                :placeholder="__('Search...')"
                icon="fas.magnifying-glass"
                @click.stop="$dispatch('mary-search-open')"
                :aria-label="__('Search')"
            />
        </div>

        <div class="flex items-center gap-2 md:gap-4 order-2 md:order-3">
            <x-artisanpack-theme-toggle class="btn btn-sm md:btn-md" />

            <div class="border-l border-secondary flex items-center pl-2 gap-1 md:gap-2">
                <a href="https://gitlab.com/jacob-martella-web-design/artisanpack-ui" target="_blank" class="hover:text-primary">
                    <x-artisanpack-icon name="fab.gitlab" class="w-4 h-4 md:w-5 md:h-5" />
                </a>
                <a href="https://bsky.app/profile/artisanpackui.dev" target="_blank" class="hover:text-primary">
                    <x-artisanpack-icon name="fab.bluesky" class="w-4 h-4 md:w-5 md:h-5" />
                </a>
                <a href="https://mastodon.social/@artisanpackui" target="_blank" class="hover:text-primary">
                    <x-artisanpack-icon name="fab.mastodon" class="w-4 h-4 md:w-5 md:h-5" />
                </a>
            </div>
        </div>
    </div>
</header>
