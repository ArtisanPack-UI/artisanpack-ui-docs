<ul <?php echo e($attributes->class(["menu w-full"])); ?> >
    <?php if($title): ?>
        <li class="menu-title text-inherit uppercase">
            <div class="flex items-center gap-2">

                <?php if($icon): ?>
                    <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $icon] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['inline-flex', $iconClasses]))]); ?>
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
                <?php endif; ?>

                <?php echo e($title); ?>

            </div>
        </li>
    <?php endif; ?>

    <?php if($separator): ?>
        <hr class="mb-3 border-t-[length:var(--border)] border-base-content/10" />
    <?php endif; ?>

    <?php echo e($slot); ?>

</ul>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/menu.blade.php ENDPATH**/ ?>