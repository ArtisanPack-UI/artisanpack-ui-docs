<?php foreach ((['activateByRoute' => false, 'activeBgColor' => 'bg-base-300']) as $__key => $__value) {
    $__consumeVariable = is_string($__key) ? $__key : $__value;
    $$__consumeVariable = is_string($__key) ? $__env->getConsumableComponentData($__key, $__value) : $__env->getConsumableComponentData($__value);
} ?>

<li class="<?php echo \Illuminate\Support\Arr::toCssClasses(['menu-disabled' => $disabled]); ?>">
    <a
        <?php echo e($attributes->class([
                "my-0.5 py-1.5 px-4 hover:text-inherit whitespace-nowrap",
                "mary-active-menu $activeBgColor" => ($active || ($activateByRoute && $routeMatches()))
            ])); ?>


        <?php if($link): ?>
            href="<?php echo e($link); ?>"

            <?php if($external): ?>
                target="_blank"
            <?php endif; ?>

            <?php if(!$external && !$noWireNavigate): ?>
                <?php echo e($attributes->wire('navigate')->value() ? $attributes->wire('navigate') : 'wire:navigate'); ?>

            <?php endif; ?>
        <?php endif; ?>

        <?php if($spinner): ?>
            wire:target="<?php echo e($spinnerTarget()); ?>"
            wire:loading.attr="disabled"
        <?php endif; ?>
    >
        
        <?php if($spinner): ?>
            <span wire:loading wire:target="<?php echo e($spinnerTarget()); ?>" class="loading loading-spinner w-5 h-5"></span>
        <?php endif; ?>

        <?php if($icon): ?>
            <span class="block py-0.5" <?php if($spinner): ?> wire:loading.class="hidden" wire:target="<?php echo e($spinnerTarget()); ?>" <?php endif; ?>>
                <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $icon] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['mb-0.5', $iconClasses]))]); ?>
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
        <?php endif; ?>

        <?php if($title || $slot->isNotEmpty()): ?>
        <span class="mary-hideable whitespace-nowrap truncate">
            <?php if($title): ?>
                <?php echo e($title); ?>


                <?php if($badge): ?>
                    <span class="badge badge-sm <?php echo e($badgeClasses); ?>"><?php echo e($badge); ?></span>
                <?php endif; ?>
            <?php else: ?>
                <?php echo e($slot); ?>

            <?php endif; ?>
        </span>
        <?php endif; ?>
    </a>
</li>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/menu-item.blade.php ENDPATH**/ ?>