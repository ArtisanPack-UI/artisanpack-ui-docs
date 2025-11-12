<main class="<?php echo \Illuminate\Support\Arr::toCssClasses(["w-full mx-auto", "max-w-screen-2xl" => !$fullWidth]); ?>">
   <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
       "drawer lg:drawer-open",
       "drawer-end" => $sidebar?->attributes['right'],
       "max-sm:drawer-end" => $sidebar?->attributes['right-mobile'],
   ]); ?>">
       <input id="<?php echo e($sidebar?->attributes['drawer']); ?>" type="checkbox" class="drawer-toggle" />
       <div <?php echo e($content->attributes->class(["drawer-content w-full mx-auto p-5 lg:px-10 lg:py-5"])); ?>>
           
           <?php echo e($content); ?>

       </div>

       
       <?php if($sidebar): ?>
           <div
               x-data="{
                   collapsed: <?php echo e(session('mary-sidebar-collapsed', 'false')); ?>,
                   collapseText: '<?php echo e($collapseText); ?>',
                   toggle() {
                       this.collapsed = !this.collapsed;
                       fetch('<?php echo e($url); ?>?collapsed=' + this.collapsed);
                       this.$dispatch('sidebar-toggled', this.collapsed);
                   }
               }"

               @menu-sub-clicked="if(collapsed) { toggle() }"
               class="<?php echo \Illuminate\Support\Arr::toCssClasses(["drawer-side z-20 lg:z-auto", "top-0 lg:top-[65px] lg:h-[calc(100vh-65px)]" => $withNav]); ?>"
           >
               <label for="<?php echo e($sidebar?->attributes['drawer']); ?>" aria-label="close sidebar" class="drawer-overlay"></label>

               
               <div
                   :class="collapsed
                       ? '!w-[62px] [&>*_summary::after]:!hidden [&_.mary-hideable]:!hidden [&_.display-when-collapsed]:!block [&_.hidden-when-collapsed]:!hidden'
                       : '!w-[270px] [&>*_summary::after]:!block [&_.mary-hideable]:!block [&_.hidden-when-collapsed]:!block [&_.display-when-collapsed]:!hidden'"

                   <?php echo e($sidebar->attributes->class([
                           "flex flex-col !transition-all !duration-100 ease-out overflow-x-hidden overflow-y-auto h-screen",
                           "w-[62px] [&>*_summary::after]:hidden [&_.mary-hideable]:hidden [&_.display-when-collapsed]:block [&_.hidden-when-collapsed]:hidden" => session('mary-sidebar-collapsed') == 'true',
                           "w-[270px] [&>*_summary::after]:block [&_.mary-hideable]:block [&_.hidden-when-collapsed]:block [&_.display-when-collapsed]:hidden" => session('mary-sidebar-collapsed') != 'true',
                           "lg:h-[calc(100vh-65px)]" => $withNav
                       ])); ?>

               >
                   <div class="flex flex-col flex-1">
                       <?php echo e($sidebar); ?>

                   </div>

                    
                   <?php if($sidebar->attributes['collapsible']): ?>
                   <?php if (isset($component)) { $__componentOriginal0123e2b6593cb89c49a91cf9746e69dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0123e2b6593cb89c49a91cf9746e69dd = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Menu::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Menu::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'hidden lg:block']); ?>
                       <?php if (isset($component)) { $__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuItem::resolve(['icon' => ''.e($sidebar->attributes['collapse-icon'] ?? $collapseIcon).'','title' => ''.e($sidebar->attributes['collapse-text'] ?? $collapseText).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['@click' => 'toggle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a)): ?>
<?php $attributes = $__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a; ?>
<?php unset($__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a)): ?>
<?php $component = $__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a; ?>
<?php unset($__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a); ?>
<?php endif; ?>
                    <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0123e2b6593cb89c49a91cf9746e69dd)): ?>
<?php $attributes = $__attributesOriginal0123e2b6593cb89c49a91cf9746e69dd; ?>
<?php unset($__attributesOriginal0123e2b6593cb89c49a91cf9746e69dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0123e2b6593cb89c49a91cf9746e69dd)): ?>
<?php $component = $__componentOriginal0123e2b6593cb89c49a91cf9746e69dd; ?>
<?php unset($__componentOriginal0123e2b6593cb89c49a91cf9746e69dd); ?>
<?php endif; ?>
                   <?php endif; ?>
               </div>
           </div>
       <?php endif; ?>
       

   </div>
</main>


<?php if($footer): ?>
   <footer <?php echo e($footer?->attributes->class(["mx-auto w-full", "max-w-screen-2xl" => !$fullWidth ])); ?>>
       <?php echo e($footer); ?>

   </footer>
<?php endif; ?>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/main.blade.php ENDPATH**/ ?>