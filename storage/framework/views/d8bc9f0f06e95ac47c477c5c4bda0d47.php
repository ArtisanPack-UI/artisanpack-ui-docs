<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'description',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title',
    'description',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex w-full flex-col text-center">
    <?php if (isset($component)) { $__componentOriginalf7f23401149ad6e0bb82ac94b1c15fef = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf7f23401149ad6e0bb82ac94b1c15fef = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Heading::resolve(['size' => 'xl'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-heading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Heading::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e($title); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf7f23401149ad6e0bb82ac94b1c15fef)): ?>
<?php $attributes = $__attributesOriginalf7f23401149ad6e0bb82ac94b1c15fef; ?>
<?php unset($__attributesOriginalf7f23401149ad6e0bb82ac94b1c15fef); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf7f23401149ad6e0bb82ac94b1c15fef)): ?>
<?php $component = $__componentOriginalf7f23401149ad6e0bb82ac94b1c15fef; ?>
<?php unset($__componentOriginalf7f23401149ad6e0bb82ac94b1c15fef); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginala0baebea98c9c7b45d27ffe2fa8b078e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala0baebea98c9c7b45d27ffe2fa8b078e = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Subheading::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-subheading'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Subheading::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e($description); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala0baebea98c9c7b45d27ffe2fa8b078e)): ?>
<?php $attributes = $__attributesOriginala0baebea98c9c7b45d27ffe2fa8b078e; ?>
<?php unset($__attributesOriginala0baebea98c9c7b45d27ffe2fa8b078e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala0baebea98c9c7b45d27ffe2fa8b078e)): ?>
<?php $component = $__componentOriginala0baebea98c9c7b45d27ffe2fa8b078e; ?>
<?php unset($__componentOriginala0baebea98c9c7b45d27ffe2fa8b078e); ?>
<?php endif; ?>
</div>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/resources/views/components/auth-header.blade.php ENDPATH**/ ?>