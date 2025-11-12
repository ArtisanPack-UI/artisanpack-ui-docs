<h<?php echo e($level); ?> 
    id="<?php echo e($id); ?>"
    <?php echo e($attributes->class([
        $sizeClass(),
        $fontWeightClass(),
        $color ?? 'text-base-content',
        'tracking-tight',
        'text-center' => $center,
    ])); ?>

>
    <?php echo e($slot); ?>

</h<?php echo e($level); ?>><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/heading.blade.php ENDPATH**/ ?>