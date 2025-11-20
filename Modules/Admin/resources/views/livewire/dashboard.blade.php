<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <x-artisanpack-header title="Dashboard" subtitle="Overview of your documentation site" />

    {{-- Stats Row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Packages --}}
        <div class="p-[1px] rounded-lg bg-primary-accent-gradient flex">
            <x-artisanpack-stat
                title="Total Packages"
                :value="$this->packages->count()"
                icon="ap.puzzle"
                color="text-primary"
                class="fill-base-content mb-0"
            />
        </div>

        {{-- Total Documentation --}}
        <div class="p-[1px] rounded-lg bg-secondary-accent-gradient flex">
            <x-artisanpack-stat
                title="Documentation Pages"
                :value="$this->totalDocumentation"
                icon="fas.file-lines"
                color="text-secondary"
            />
        </div>

        {{-- Total Downloads --}}
        <div class="p-[1px] rounded-lg bg-primary-secondary-gradient flex">
            <x-artisanpack-stat
                title="Total Downloads"
                :value="$this->formatNumber($this->totalDownloads)"
                icon="fas.download"
                color="text-accent"
                description="Packagist + NPM"
            />
        </div>

        {{-- Needs Re-import Alert --}}
        <div class="p-[1px] rounded-lg bg-secondary-primary-gradient flex">
            <x-artisanpack-stat
                title="Needs Re-import"
                :value="$this->packagesNeedingReimport"
                icon="fas.rotate"
                :color="$this->packagesNeedingReimport > 0 ? 'text-warning' : 'text-success'"
                :description="$this->packagesNeedingReimport > 0 ? 'Packages with stale docs' : 'All packages up to date'"
            />
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Packages Table - Takes 2 columns --}}
        <div class="lg:col-span-2">
            <div class="p-[1px] rounded-lg bg-accent-primary-gradient flex">
                <x-artisanpack-card title="Packages Overview" separator shadow class="w-full">
                    <x-slot:subtitle>
                        All packages with documentation status
                    </x-slot:subtitle>

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th class="text-center">Version</th>
                                    <th class="text-center">Docs</th>
                                    <th class="text-center">Last Import</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->packages as $package)
                                    <tr wire:key="package-{{ $package->id }}">
                                        <td>
                                            <div class="flex items-center gap-3">
                                                @if($package->icon)
                                                    <div class="flex h-8 w-8 items-center justify-center rounded bg-base-200">
                                                        <x-artisanpack-icon :name="$package->icon" class="h-5 w-5 fill-base-content" />
                                                    </div>
                                                @else
                                                    <div class="flex h-8 w-8 items-center justify-center rounded bg-base-200">
                                                        <x-artisanpack-icon name="ap.puzzle" class="h-4 w-4 text-base-content/50" />
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="font-medium">{{ $package->name }}</div>
                                                    @if($package->package_registry)
                                                        <div class="text-xs text-base-content/50">{{ $package->getRegistryPackageName() }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($package->version)
                                                <x-artisanpack-badge :value="$package->version" class="badge-ghost badge-sm" />
                                            @else
                                                <span class="text-base-content/30">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="font-medium">{{ $package->documentation_count }}</span>
                                        </td>
                                        <td class="text-center text-sm">
                                            @if($package->docs_imported_at)
                                                <span title="{{ $package->docs_imported_at->format('M j, Y g:i A') }}">
                                                    {{ $package->docs_imported_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-base-content/30">Never</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($package->needsDocumentationReimport())
                                                <x-artisanpack-badge value="Needs Update" class="badge-warning badge-sm" />
                                            @else
                                                <x-artisanpack-badge value="Up to date" class="badge-success badge-sm" />
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-base-content/50 py-8">
                                            No packages found. <a href="{{ route('dashboard.packages.add') }}" class="link link-primary" wire:navigate>Add your first package</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-slot:actions>
                        <x-artisanpack-button
                            icon="fas.plus"
                            class="btn-primary btn-sm"
                            :href="route('dashboard.packages.add')"
                            wire:navigate
                        >
                            Add Package
                        </x-artisanpack-button>
                    </x-slot:actions>
                </x-artisanpack-card>
            </div>
        </div>

        {{-- Right Column - Packagist Stats --}}
        <div class="flex flex-col gap-6">
            {{-- Packagist Stats Card --}}
            <div class="p-[1px] rounded-lg bg-accent-secondary-gradient flex">
                <x-artisanpack-card title="Packagist Stats" separator shadow class="w-full">
                    <x-slot:subtitle>
                        Composer package downloads
                    </x-slot:subtitle>

                    @if($this->packagistStats['is_configured'])
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Monthly</span>
                                <span class="font-bold">{{ $this->formatNumber($this->packagistStats['monthly_downloads']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Daily</span>
                                <span class="font-bold">{{ $this->formatNumber($this->packagistStats['daily_downloads']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Stars</span>
                                <span class="font-bold">{{ $this->formatNumber($this->packagistStats['total_favers']) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <x-artisanpack-icon name="fas.box-open" class="h-8 w-8 mx-auto text-base-content/30 mb-2" />
                            <p class="text-sm text-base-content/50">
                                No Packagist packages configured.
                            </p>
                        </div>
                    @endif
                </x-artisanpack-card>
            </div>

            {{-- NPM Stats Card --}}
            <div class="p-[1px] rounded-lg bg-primary-accent-gradient flex">
                <x-artisanpack-card title="NPM Stats" separator shadow class="w-full">
                    <x-slot:subtitle>
                        JavaScript package downloads
                    </x-slot:subtitle>

                    @if($this->npmStats['is_configured'])
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Monthly</span>
                                <span class="font-bold">{{ $this->formatNumber($this->npmStats['monthly_downloads']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Weekly</span>
                                <span class="font-bold">{{ $this->formatNumber($this->npmStats['weekly_downloads']) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <x-artisanpack-icon name="fab.npm" class="h-8 w-8 mx-auto text-base-content/30 mb-2" />
                            <p class="text-sm text-base-content/50">
                                No NPM packages configured.
                            </p>
                        </div>
                    @endif
                </x-artisanpack-card>
            </div>

            {{-- Google Analytics Card --}}
            <div class="p-[1px] rounded-lg bg-secondary-accent-gradient flex">
                <x-artisanpack-card title="Google Analytics" separator shadow class="w-full">
                    <x-slot:subtitle>
                        Last 30 days
                    </x-slot:subtitle>

                    @if($this->analyticsData['is_configured'])
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Page Views</span>
                                <span class="font-bold text-lg">{{ $this->formatNumber($this->analyticsData['overview']['page_views']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Sessions</span>
                                <span class="font-bold">{{ $this->formatNumber($this->analyticsData['overview']['sessions']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Users</span>
                                <span class="font-bold">{{ $this->formatNumber($this->analyticsData['overview']['users']) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <x-artisanpack-icon name="fas.chart-line" class="h-8 w-8 mx-auto text-base-content/30 mb-2" />
                            <p class="text-sm text-base-content/50">
                                Configure Google Analytics API to see traffic data.
                            </p>
                            <p class="text-xs text-base-content/30 mt-2">
                                See setup instructions in GoogleAnalyticsService.php
                            </p>
                        </div>
                    @endif
                </x-artisanpack-card>
            </div>

            {{-- Google Search Console Card --}}
            <div class="p-[1px] rounded-lg bg-primary-secondary-gradient flex">
                <x-artisanpack-card title="Search Console" separator shadow class="w-full">
                    <x-slot:subtitle>
                        Last 28 days
                    </x-slot:subtitle>

                    @if($this->searchConsoleData['is_configured'])
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Total Clicks</span>
                                <span class="font-bold text-lg">{{ $this->formatNumber($this->searchConsoleData['overview']['clicks']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Impressions</span>
                                <span class="font-bold">{{ $this->formatNumber($this->searchConsoleData['overview']['impressions']) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/70">Avg. Position</span>
                                <span class="font-bold">{{ number_format($this->searchConsoleData['overview']['position'], 1) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <x-artisanpack-icon name="fas.magnifying-glass-chart" class="h-8 w-8 mx-auto text-base-content/30 mb-2" />
                            <p class="text-sm text-base-content/50">
                                Configure Search Console API to see search data.
                            </p>
                            <p class="text-xs text-base-content/30 mt-2">
                                See setup instructions in GoogleSearchConsoleService.php
                            </p>
                        </div>
                    @endif
                </x-artisanpack-card>
            </div>
        </div>
    </div>
</div>
