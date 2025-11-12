<div>
    <?php
        // We need this extra step to support models arrays. Ex: wire:model="emails.0"  , wire:model="emails.1"
        $uuid = $uuid . $modelName()
    ?>

    <fieldset class="fieldset py-0">
        
        <!--[if BLOCK]><![endif]--><?php if($label && !$inline): ?>
            <legend class="fieldset-legend mb-0.5">
                <?php echo e($label); ?>


                <!--[if BLOCK]><![endif]--><?php if($attributes->get('required')): ?>
                    <span class="text-error">*</span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </legend>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        <label class="<?php echo \Illuminate\Support\Arr::toCssClasses(["floating-label" => $label && $inline]); ?>">
            
            <!--[if BLOCK]><![endif]--><?php if($label && $inline): ?>
                <span class="font-semibold"><?php echo e($label); ?></span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(["w-full", "join" => $prepend || $append]); ?>">
                
                <!--[if BLOCK]><![endif]--><?php if($prepend): ?>
                    <?php echo e($prepend); ?>

                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <label
                    <?php if($isDisabled()): ?>
                        disabled
                    <?php endif; ?>

                    <?php echo e($attributes->whereStartsWith('class')->class([
                            "input w-full",
                            "join-item" => $prepend || $append,
                            "border-dashed" => $isReadonly(),
                            "!input-error" => $errorFieldName() && $errors->has($errorFieldName()) && !$omitError
                        ])); ?>

                 >
                    
                    <!--[if BLOCK]><![endif]--><?php if($prefix): ?>
                        <span class="label"><?php echo e($prefix); ?></span>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    
                    <!--[if BLOCK]><![endif]--><?php if($icon): ?>
                        <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $icon] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'pointer-events-none w-4 h-4 opacity-40']); ?>
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
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    
                    <!--[if BLOCK]><![endif]--><?php if($money): ?>
                        <div
                            class="w-full"
                            x-data="{ amount: $wire.get('<?php echo e($modelName()); ?>') }" x-init="$nextTick(() => new Currency($refs.myInput, <?php echo e($moneySettings()); ?>))"
                        >
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        
                        <input
                            id="<?php echo e($uuid); ?>"
                            placeholder="<?php echo e($attributes->get('placeholder')); ?> "

                            <?php if($attributes->has('autofocus') && $attributes->get('autofocus') == true): ?>
                                autofocus
                            <?php endif; ?>

                            <?php if($money): ?>
                                x-ref="myInput"
                                :value="amount"
                                x-on:input="$nextTick(() => $wire.set('<?php echo e($modelName()); ?>', Currency.getUnmasked(), <?php echo e(json_encode($attributes->wire('model')->hasModifier('live'))); ?>))"
                                x-on:blur="$nextTick(() => $wire.set('<?php echo e($modelName()); ?>', Currency.getUnmasked(), <?php echo e(json_encode($attributes->wire('model')->hasModifier('blur'))); ?>))"
                                inputmode="numeric"
                            <?php endif; ?>

                            <?php echo e($attributes
                                    ->merge(['type' => 'text'])
                                    ->except($money ? ['wire:model', 'wire:model.live', 'wire:model.blur'] : '')); ?>

                        />

                    
                    <!--[if BLOCK]><![endif]--><?php if($money): ?>
                            <input type="hidden" <?php echo e($attributes->wire('model')); ?> />
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    
                    <!--[if BLOCK]><![endif]--><?php if($clearable): ?>
                        <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => 'o-x-mark'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['x-on:click' => '$wire.set(\''.e($modelName()).'\', \'\', '.e(json_encode($attributes->wire('model')->hasModifier('live'))).')','class' => 'cursor-pointer w-4 h-4 opacity-40']); ?>
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
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    
                    <!--[if BLOCK]><![endif]--><?php if($iconRight): ?>
                        <?php if (isset($component)) { $__componentOriginal5f3935e6ccdddc284926e2252ded2692 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5f3935e6ccdddc284926e2252ded2692 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Icon::resolve(['name' => $iconRight] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Icon::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'pointer-events-none w-4 h-4 opacity-40']); ?>
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
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    
                    <!--[if BLOCK]><![endif]--><?php if($suffix): ?>
                        <span class="label"><?php echo e($suffix); ?></span>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </label>

                
                <!--[if BLOCK]><![endif]--><?php if($append): ?>
                    <?php echo e($append); ?>

                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
        </label>

        
        <!--[if BLOCK]><![endif]--><?php if(!$omitError && $errors->has($errorFieldName())): ?>
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $errors->get($errorFieldName()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = Arr::wrap($message); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="<?php echo e($errorClass); ?>" x-class="text-error"><?php echo e($line); ?></div>
                    <?php if($firstErrorOnly) break; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php if($firstErrorOnly) break; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

        
        <!--[if BLOCK]><![endif]--><?php if($hint): ?>
            <div class="<?php echo e($hintClass); ?>" x-classes="fieldset-label"><?php echo e($hint); ?></div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </fieldset>
</div><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/vendor/artisanpack-ui/livewire-ui-components/src/../resources/views/components/input.blade.php ENDPATH**/ ?>