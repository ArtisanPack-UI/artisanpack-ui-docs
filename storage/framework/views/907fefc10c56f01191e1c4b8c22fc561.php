<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <?php echo $__env->make('partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">


<?php if (isset($component)) { $__componentOriginalfa7d5644db7532aed2ece1408c230744 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfa7d5644db7532aed2ece1408c230744 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Nav::resolve(['sticky' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Nav::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'lg:hidden']); ?>
     <?php $__env->slot('brand', null, []); ?> 
        <?php if (isset($component)) { $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $attributes = $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $component = $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
     <?php $__env->slot('actions', null, []); ?> 
        <label for="main-drawer" class="lg:hidden me-3">
            <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => 'o-bars-3'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'cursor-pointer']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5f3935e6ccdddc284926e2252ded2692)): ?>
<?php $attributes = $__attributesOriginal5f3935e6ccdddc284926e2252ded2692; ?>
<?php unset($__attributesOriginal5f3935e6ccdddc284926e2252ded2692); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5f3935e6ccdddc284926e2252ded2692)): ?>
<?php $component = $__componentOriginal5f3935e6ccdddc284926e2252ded2692; ?>
<?php unset($__componentOriginal5f3935e6ccdddc284926e2252ded2692); ?>
<?php endif; ?>
        </label>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfa7d5644db7532aed2ece1408c230744)): ?>
<?php $attributes = $__attributesOriginalfa7d5644db7532aed2ece1408c230744; ?>
<?php unset($__attributesOriginalfa7d5644db7532aed2ece1408c230744); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfa7d5644db7532aed2ece1408c230744)): ?>
<?php $component = $__componentOriginalfa7d5644db7532aed2ece1408c230744; ?>
<?php unset($__componentOriginalfa7d5644db7532aed2ece1408c230744); ?>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginale9ee17d17818c5c13399eb6cf9a3ca91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale9ee17d17818c5c13399eb6cf9a3ca91 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Main::resolve(['fullWidth' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-main'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Main::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    
     <?php $__env->slot('sidebar', null, ['drawer' => 'main-drawer','collapsible' => true,'class' => 'bg-base-100 lg:bg-inherit']); ?> 

        
        <?php if (isset($component)) { $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.app-logo','data' => ['class' => 'px-5 pt-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'px-5 pt-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $attributes = $__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__attributesOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3)): ?>
<?php $component = $__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3; ?>
<?php unset($__componentOriginal7b17d80ff7900603fe9e5f0b453cc7c3); ?>
<?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginal0123e2b6593cb89c49a91cf9746e69dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0123e2b6593cb89c49a91cf9746e69dd = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Menu::resolve(['title' => null,'activateByRoute' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Menu::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'flex flex-col flex-1']); ?>

            <?php if (isset($component)) { $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-separator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $attributes = $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $component = $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuItem::resolve(['title' => 'Dashboard','icon' => 'o-sparkles'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('dashboard'))]); ?>
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
            <?php if (isset($component)) { $__componentOriginal7be36e975ef0ac6b40faf27b61f53c5a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7be36e975ef0ac6b40faf27b61f53c5a = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuItem::resolve(['title' => 'Settings','icon' => 'o-sparkles'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('settings.profile'))]); ?>
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

            <?php if (isset($component)) { $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-separator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $attributes = $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $component = $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>

            <div class="mt-auto">
            
            <?php if($user = auth()->user()): ?>

                <?php if (isset($component)) { $__componentOriginalb229d1abcb4d6b36af578036c1361ec9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb229d1abcb4d6b36af578036c1361ec9 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\ListItem::resolve(['item' => $user,'value' => 'name','subValue' => 'email','noSeparator' => true,'noHover' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-list-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\ListItem::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => '-mx-2 !-my-2 rounded mt-auto']); ?>
                     <?php $__env->slot('actions', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal69b14022878dc11662ef95d23a975397 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69b14022878dc11662ef95d23a975397 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Button::resolve(['icon' => 'o-power','tooltipLeft' => 'logoff','noWireNavigate' => true,'link' => '/logout'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'btn-circle btn-ghost btn-xs']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69b14022878dc11662ef95d23a975397)): ?>
<?php $attributes = $__attributesOriginal69b14022878dc11662ef95d23a975397; ?>
<?php unset($__attributesOriginal69b14022878dc11662ef95d23a975397); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69b14022878dc11662ef95d23a975397)): ?>
<?php $component = $__componentOriginal69b14022878dc11662ef95d23a975397; ?>
<?php unset($__componentOriginal69b14022878dc11662ef95d23a975397); ?>
<?php endif; ?>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb229d1abcb4d6b36af578036c1361ec9)): ?>
<?php $attributes = $__attributesOriginalb229d1abcb4d6b36af578036c1361ec9; ?>
<?php unset($__attributesOriginalb229d1abcb4d6b36af578036c1361ec9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb229d1abcb4d6b36af578036c1361ec9)): ?>
<?php $component = $__componentOriginalb229d1abcb4d6b36af578036c1361ec9; ?>
<?php unset($__componentOriginalb229d1abcb4d6b36af578036c1361ec9); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-menu-separator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\MenuSeparator::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $attributes = $__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__attributesOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007)): ?>
<?php $component = $__componentOriginaleceea46bfdc77845f32a74ea7c7d7007; ?>
<?php unset($__componentOriginaleceea46bfdc77845f32a74ea7c7d7007); ?>
<?php endif; ?>
            <?php endif; ?>
            </div>

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
     <?php $__env->endSlot(); ?>

    
     <?php $__env->slot('content', null, []); ?> 
        <?php echo e($slot); ?>

     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale9ee17d17818c5c13399eb6cf9a3ca91)): ?>
<?php $attributes = $__attributesOriginale9ee17d17818c5c13399eb6cf9a3ca91; ?>
<?php unset($__attributesOriginale9ee17d17818c5c13399eb6cf9a3ca91); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale9ee17d17818c5c13399eb6cf9a3ca91)): ?>
<?php $component = $__componentOriginale9ee17d17818c5c13399eb6cf9a3ca91; ?>
<?php unset($__componentOriginale9ee17d17818c5c13399eb6cf9a3ca91); ?>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginalff9b09a99cd6765c689b91fd8f10cfe4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff9b09a99cd6765c689b91fd8f10cfe4 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Toast::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Toast::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff9b09a99cd6765c689b91fd8f10cfe4)): ?>
<?php $attributes = $__attributesOriginalff9b09a99cd6765c689b91fd8f10cfe4; ?>
<?php unset($__attributesOriginalff9b09a99cd6765c689b91fd8f10cfe4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff9b09a99cd6765c689b91fd8f10cfe4)): ?>
<?php $component = $__componentOriginalff9b09a99cd6765c689b91fd8f10cfe4; ?>
<?php unset($__componentOriginalff9b09a99cd6765c689b91fd8f10cfe4); ?>
<?php endif; ?>
</body>
</html>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/resources/views/components/layouts/app.blade.php ENDPATH**/ ?>