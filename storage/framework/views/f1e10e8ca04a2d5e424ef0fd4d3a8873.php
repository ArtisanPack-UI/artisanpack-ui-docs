<p 
    id="<?php echo e($id); ?>"
    <?php echo e($attributes->class([
        $size ?? 'text-lg',
        $fontWeightClass(),
        $colorClass(),
        'leading-relaxed',
        'text-center' => $center,
    ])); ?>

>
    <?php echo e($slot); ?>

</p><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/subheading.blade.php ENDPATH**/ ?>