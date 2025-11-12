<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

?>

<div class="flex flex-col gap-6">
    <?php if (isset($component)) { $__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-header','data' => ['title' => __('Forgot password'),'description' => __('Enter your email to receive a password reset link')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Forgot password')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Enter your email to receive a password reset link'))]); ?>
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
<?php $component->withAttributes(['wire:submit' => 'sendPasswordResetLink','class' => 'flex flex-col gap-6']); ?>
        <!-- Email Address -->
        <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Email Address')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'email','type' => 'email','required' => true,'autofocus' => true,'placeholder' => 'email@example.com']); ?>
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

        <?php if (isset($component)) { $__componentOriginal69b14022878dc11662ef95d23a975397 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69b14022878dc11662ef95d23a975397 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Button::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Button::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','type' => 'submit','class' => 'w-full btn-primary']); ?><?php echo e(__('Email password reset link')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69b14022878dc11662ef95d23a975397)): ?>
<?php $attributes = $__attributesOriginal69b14022878dc11662ef95d23a975397; ?>
<?php unset($__attributesOriginal69b14022878dc11662ef95d23a975397); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69b14022878dc11662ef95d23a975397)): ?>
<?php $component = $__componentOriginal69b14022878dc11662ef95d23a975397; ?>
<?php unset($__componentOriginal69b14022878dc11662ef95d23a975397); ?>
<?php endif; ?>
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

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        <span><?php echo e(__('Or, return to')); ?></span>
        <?php if (isset($component)) { $__componentOriginal669c518b01c3ae653261ed4561a881b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c518b01c3ae653261ed4561a881b4 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Link::resolve(['href' => route('login')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Link::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:navigate' => true]); ?><?php echo e(__('log in')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c518b01c3ae653261ed4561a881b4)): ?>
<?php $attributes = $__attributesOriginal669c518b01c3ae653261ed4561a881b4; ?>
<?php unset($__attributesOriginal669c518b01c3ae653261ed4561a881b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c518b01c3ae653261ed4561a881b4)): ?>
<?php $component = $__componentOriginal669c518b01c3ae653261ed4561a881b4; ?>
<?php unset($__componentOriginal669c518b01c3ae653261ed4561a881b4); ?>
<?php endif; ?>
    </div>
</div><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/resources/views/livewire/auth/forgot-password.blade.php ENDPATH**/ ?>