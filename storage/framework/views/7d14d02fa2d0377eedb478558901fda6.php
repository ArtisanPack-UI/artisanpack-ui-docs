<div wire:key="<?php echo e($uuid); ?>">
    <div
        <?php echo e($attributes->class([
                "flex justify-start items-center gap-4 px-3",
                "hover:bg-base-200" => !$noHover,
                "cursor-pointer" => $link
            ])); ?>

    >

        <?php if($link && (data_get($item, $avatar) || !is_string($avatar))): ?>
            <div>
                <a href="<?php echo e($link); ?>" wire:navigate>
        <?php endif; ?>

        <!-- AVATAR -->
        <?php if(data_get($item, $avatar)): ?>
            <div class="py-3">
                <div class="avatar">
                    <div class="w-11 rounded-full">
                        <img src="<?php echo e(data_get($item, $avatar)); ?>" />
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!is_string($avatar)): ?>
            <div <?php echo e($avatar->attributes->class(["py-3"])); ?>>
                <?php echo e($avatar); ?>

            </div>
        <?php endif; ?>


        <?php if($link && (data_get($item, $avatar) || !is_string($avatar))): ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- CONTENT -->
        <div class="flex-1 overflow-hidden whitespace-nowrap text-ellipsis truncate w-0 mary-hideable">
            <?php if($link): ?>
                <a href="<?php echo e($link); ?>" wire:navigate>
            <?php endif; ?>

            <div class="py-3">
                <div <?php if(!is_string($value)): ?> <?php echo e($value->attributes->class(["font-semibold truncate"])); ?> <?php else: ?> class="font-semibold truncate" <?php endif; ?>>
                    <?php echo e(is_string($value) ? data_get($item, $value) : $value); ?>

                </div>

                <div <?php if(!is_string($subValue)): ?>  <?php echo e($subValue->attributes->class(["text-base-content/50 text-sm truncate"])); ?> <?php else: ?> class="text-base-content/50 text-sm truncate" <?php endif; ?>>
                    <?php echo e(is_string($subValue) ? data_get($item, $subValue) : $subValue); ?>

                </div>
            </div>

            <?php if($link): ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- ACTION -->
        <?php if($actions): ?>
            <?php if($link && !Str::of($actions)->contains([':click', '@click' , 'href'])): ?>
                <a href="<?php echo e($link); ?>" wire:navigate>
            <?php endif; ?>
                <div <?php echo e($actions->attributes->class(["py-3 flex items-center gap-3 mary-hideable"])); ?>>
                        <?php echo e($actions); ?>

                </div>

            <?php if($link && !Str::of($actions)->contains([':click', '@click' , 'href'])): ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if(!$noSeparator): ?>
        <hr class="border-t-[length:var(--border)] border-base-content/10"/>
    <?php endif; ?>
</div>
<?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/list-item.blade.php ENDPATH**/ ?>