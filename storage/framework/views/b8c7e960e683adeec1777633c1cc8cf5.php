<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

?>

<div class="flex flex-col gap-6">
    <?php if (isset($component)) { $__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-header','data' => ['title' => __('Reset password'),'description' => __('Please enter your new password below')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Reset password')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Please enter your new password below'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd)): ?>
<?php $attributes = $__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd; ?>
<?php unset($__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd)): ?>
<?php $component = $__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd; ?>
<?php unset($__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd); ?>
<?php endif; ?>

    <!-- Session Status -->
    <?php if (isset($component)) { $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-session-status','data' => ['class' => 'text-center','status' => session('status')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-session-status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'text-center','status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('status'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $attributes = $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $component = $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal021d4cf6b3679f7214b0e745e0414add = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal021d4cf6b3679f7214b0e745e0414add = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Form::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Form::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:submit' => 'resetPassword','class' => 'flex flex-col gap-6']); ?>
        <!-- Email Address -->
        <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Email')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'email','type' => 'email','required' => true,'autocomplete' => 'email']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $attributes = $__attributesOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__attributesOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $component = $__componentOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__componentOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>

        <!-- Password -->
        <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Password')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'password','type' => 'password','required' => true,'autocomplete' => 'new-password','placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Password')),'viewable' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $attributes = $__attributesOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__attributesOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $component = $__componentOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__componentOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>

        <!-- Confirm Password -->
        <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Confirm password')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'password_confirmation','type' => 'password','required' => true,'autocomplete' => 'new-password','placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Confirm password')),'viewable' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $attributes = $__attributesOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__attributesOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal142bec0eb54e444be121573a52fe2b87)): ?>
<?php $component = $__componentOriginal142bec0eb54e444be121573a52fe2b87; ?>
<?php unset($__componentOriginal142bec0eb54e444be121573a52fe2b87); ?>
<?php endif; ?>

        <div class="flex items-center justify-end">
            <?php if (isset($component)) { $__componentOriginal69b14022878dc11662ef95d23a975397 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69b14022878dc11662ef95d23a975397 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'w-full btn-primary']); ?>
                <?php echo e(__('Reset password')); ?>

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
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal021d4cf6b3679f7214b0e745e0414add)): ?>
<?php $attributes = $__attributesOriginal021d4cf6b3679f7214b0e745e0414add; ?>
<?php unset($__attributesOriginal021d4cf6b3679f7214b0e745e0414add); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal021d4cf6b3679f7214b0e745e0414add)): ?>
<?php $component = $__componentOriginal021d4cf6b3679f7214b0e745e0414add; ?>
<?php unset($__componentOriginal021d4cf6b3679f7214b0e745e0414add); ?>
<?php endif; ?>
</div><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/resources/views/livewire/auth/reset-password.blade.php ENDPATH**/ ?>