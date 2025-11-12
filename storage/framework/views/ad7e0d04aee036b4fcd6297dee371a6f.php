<!--[if BLOCK]><![endif]--><?php if($link): ?>
    <a href="<?php echo $link; ?>"
<?php else: ?>
    <button
<?php endif; ?>

    wire:key="<?php echo e($uuid); ?>"
    <?php echo e($attributes->whereDoesntStartWith('class')->merge(['type' => 'button'])); ?>

    <?php echo e($attributes->class(['btn', '!inline-flex lg:tooltip ' . $tooltipPosition => $tooltip])); ?>


    <?php if($link && $external): ?>
        target="_blank"
    <?php endif; ?>

    <?php if($link && !$external && !$noWireNavigate): ?>
        wire:navigate
    <?php endif; ?>

    <?php if($tooltip): ?>
        data-tip="<?php echo e($tooltip); ?>"
    <?php endif; ?>

    <?php if($spinner): ?>
        wire:target="<?php echo e($spinnerTarget()); ?>"
        wire:loading.attr="disabled"
    <?php endif; ?>
>

    <!-- SPINNER LEFT -->
    <!--[if BLOCK]><![endif]--><?php if($spinner && !$iconRight): ?>
        <span wire:loading wire:target="<?php echo e($spinnerTarget()); ?>" class="loading loading-spinner w-5 h-5"></span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- ICON -->
    <!--[if BLOCK]><![endif]--><?php if($icon): ?>
        <span class="block" <?php if($spinner): ?> wire:loading.class="hidden" wire:target="<?php echo e($spinnerTarget()); ?>" <?php endif; ?>>
            <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $icon] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
        </span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- LABEL / SLOT -->
    <!--[if BLOCK]><![endif]--><?php if($label): ?>
        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(["hidden lg:block" => $responsive ]); ?>">
            <?php echo e($label); ?>

        </span>
        <!--[if BLOCK]><![endif]--><?php if(strlen($badge ?? '') > 0): ?>
            <span class="badge badge-sm <?php echo e($badgeClasses); ?>"><?php echo e($badge); ?></span>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    <?php else: ?>
        <?php echo e($slot); ?>

    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- ICON RIGHT -->
    <!--[if BLOCK]><![endif]--><?php if($iconRight): ?>
        <span class="block" <?php if($spinner): ?> wire:loading.class="hidden" wire:target="<?php echo e($spinnerTarget()); ?>" <?php endif; ?>>
            <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $iconRight] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
        </span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    <!-- SPINNER RIGHT -->
    <!--[if BLOCK]><![endif]--><?php if($spinner && $iconRight): ?>
        <span wire:loading wire:target="<?php echo e($spinnerTarget()); ?>" class="loading loading-spinner w-5 h-5"></span>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

<!--[if BLOCK]><![endif]--><?php if(!$link): ?>
    </button>
<?php else: ?>
    </a>
<?php endif; ?><!--[if ENDBLOCK]><![endif]--><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/button.blade.php ENDPATH**/ ?>