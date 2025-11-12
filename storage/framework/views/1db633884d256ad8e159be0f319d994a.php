<div>
    <fieldset class="fieldset">
        <div class="w-full">
            <label class="<?php echo \Illuminate\Support\Arr::toCssClasses(["flex gap-3 items-center cursor-pointer", "justify-between" => $right, "!items-start" => $hint]); ?>">

                
                <input
                    id="<?php echo e($uuid); ?>"
                    type="checkbox"
                    <?php echo e($attributes->whereDoesntStartWith("id")
                            ->class(["order-2" => $right])
                            ->merge(["class" => "checkbox"])); ?>

                />

                
                 <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(["order-1" => $right]); ?>">
                    <div class="text-sm font-medium">
                        <?php echo e($label); ?>


                        <!--[if BLOCK]><![endif]--><?php if($attributes->get('required')): ?>
                            <span class="text-error">*</span>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    
                    <!--[if BLOCK]><![endif]--><?php if($hint): ?>
                        <div class="<?php echo e($hintClass); ?>" x-classes="fieldset-label"><?php echo e($hint); ?></div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            </label>
        </div>

        
        <!--[if BLOCK]><![endif]--><?php if(!$omitError && $errors->has($errorFieldName())): ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $errors->get($errorFieldName()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = Arr::wrap($message); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="<?php echo e($errorClass); ?>" x-class="text-error"><?php echo e($line); ?></div>
                    <?php if($firstErrorOnly) break; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php if($firstErrorOnly) break; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </fieldset>
</div><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/checkbox.blade.php ENDPATH**/ ?>