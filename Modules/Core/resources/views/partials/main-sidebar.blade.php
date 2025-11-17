<aside id="sidebar" class="w-full md:w-[20rem]">
    <div class="bg-primary-accent-gradient rounded-lg p-[1px] fill-base-content max-h-[calc(100vh-6rem)] overflow-y-auto">
        <div class="bg-base-100 p-4 rounded-lg">
            {{-- Pages Menu --}}
            @if(isset($sidebarPages) && count($sidebarPages) > 0)
                <x-artisanpack-menu :title="null" activate-by-route>
                    @foreach($sidebarPages as $page)
                        @include('core::partials.sidebar-page-item', ['page' => $page])
                    @endforeach
                </x-artisanpack-menu>
            @endif

            <x-artisanpack-separator class="menu-separator" />

            {{-- Packages Menu --}}
            @if(isset($sidebarPackages) && count($sidebarPackages) > 0)
                <x-artisanpack-menu :title="null" class="packages-menu">
                    @foreach($sidebarPackages as $package)
                        @include('core::partials.sidebar-package-item', ['package' => $package])
                    @endforeach
                </x-artisanpack-menu>
            @endif
        </div>
    </div>
</aside>
