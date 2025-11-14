<header id="header" class="w-full bg-base-100 h-[60px] py-3 border-b-primary border-b">
    <div class="mx-auto max-w-[90rem] px-4 flex justify-between items-center gap-4">
        <a href="{{ route('home') }}" class="text-xl font-bold">
            <img src="{{ asset('images/artisanpack-ui-wordmark-light@3x.png') }}" alt="ArtisanPack Logo" class="max-h-[30px] hidden dark:block">
            <img src="{{ asset('images/artisanpack-ui-wordmark-dark-color@3x.png') }}" alt="ArtisanPack Logo" class="max-h-[30px] block dark:hidden">
            <span class="sr-only">ArtisanPack UI</span>
        </a>

        <div class="flex-1">
            <x-artisanpack-input
                :placeholder="__('Search...')"
                icon="fas.magnifying-glass"
                @click.stop="$dispatch('mary-search-open')"
                :aria-label="__('Search')"
            />
        </div>

        <div class="flex items-center gap-4">
            <x-artisanpack-theme-toggle class="btn" />

            <div class="border-l border-secondary flex items-center pl-2 gap-2">
                <a href="https://gitlab.com/jacob-martella-web-design/artisanpack-ui" target="_blank">
                    <x-artisanpack-icon name="fab.gitlab" />
                </a>
                <a href="https://bsky.app/profile/artisanpackui.dev" target="_blank">
                    <x-artisanpack-icon name="fab.bluesky" />
                </a>
                <a href="https://mastodon.social/@artisanpackui" target="_blank">
                    <x-artisanpack-icon name="fab.mastodon" />
                </a>
            </div>
        </div>
    </div>
</header>
