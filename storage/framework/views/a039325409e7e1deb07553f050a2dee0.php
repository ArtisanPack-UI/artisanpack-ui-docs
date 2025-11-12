<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

?>

<div class="flex flex-col gap-6">
    <?php if (isset($component)) { $__componentOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale5d2f2831f58fdbe96ad6d7cbd41a7dd = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-header','data' => ['title' => __('Log in to your account'),'description' => __('Enter your email and password below to log in')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Log in to your account')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Enter your email and password below to log in'))]); ?>
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
<?php $component->withAttributes(['wire:submit' => 'login','class' => 'flex flex-col gap-6']); ?>
        <!-- Email Address -->
        <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Email address')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'email','type' => 'email','required' => true,'autofocus' => true,'autocomplete' => 'email','placeholder' => 'email@example.com']); ?>
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
        <div class="relative">
            <?php if (isset($component)) { $__componentOriginal142bec0eb54e444be121573a52fe2b87 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal142bec0eb54e444be121573a52fe2b87 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Input::resolve(['label' => __('Password')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Input::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'password','type' => 'password','required' => true,'autocomplete' => 'current-password','placeholder' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Password')),'viewable' => true]); ?>
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

            <!--[if BLOCK]><![endif]--><?php if(Route::has('password.request')): ?>
                <?php if (isset($component)) { $__componentOriginal669c518b01c3ae653261ed4561a881b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c518b01c3ae653261ed4561a881b4 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Link::resolve(['href' => route('password.request')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Link::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'absolute end-0 top-0 text-sm','wire:navigate' => true]); ?>
                    <?php echo e(__('Forgot your password?')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c518b01c3ae653261ed4561a881b4)): ?>
<?php $attributes = $__attributesOriginal669c518b01c3ae653261ed4561a881b4; ?>
<?php unset($__attributesOriginal669c518b01c3ae653261ed4561a881b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c518b01c3ae653261ed4561a881b4)): ?>
<?php $component = $__componentOriginal669c518b01c3ae653261ed4561a881b4; ?>
<?php unset($__componentOriginal669c518b01c3ae653261ed4561a881b4); ?>
<?php endif; ?>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>

        <!-- Remember Me -->
        <?php if (isset($component)) { $__componentOriginal86e3e28386db5bf0ee6ba4ca2f52d803 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal86e3e28386db5bf0ee6ba4ca2f52d803 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Checkbox::resolve(['label' => __('Remember me')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-checkbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Checkbox::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'remember']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal86e3e28386db5bf0ee6ba4ca2f52d803)): ?>
<?php $attributes = $__attributesOriginal86e3e28386db5bf0ee6ba4ca2f52d803; ?>
<?php unset($__attributesOriginal86e3e28386db5bf0ee6ba4ca2f52d803); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal86e3e28386db5bf0ee6ba4ca2f52d803)): ?>
<?php $component = $__componentOriginal86e3e28386db5bf0ee6ba4ca2f52d803; ?>
<?php unset($__componentOriginal86e3e28386db5bf0ee6ba4ca2f52d803); ?>
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
<?php $component->withAttributes(['variant' => 'primary','type' => 'submit','class' => 'w-full btn-primary']); ?><?php echo e(__('Log in')); ?> <?php echo $__env->renderComponent(); ?>
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

    <!--[if BLOCK]><![endif]--><?php if(Route::has('register')): ?>
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span><?php echo e(__('Don\'t have an account?')); ?></span>
            <?php if (isset($component)) { $__componentOriginal669c518b01c3ae653261ed4561a881b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c518b01c3ae653261ed4561a881b4 = $attributes; } ?>
<?php $component = ArtisanPack\LivewireUiComponents\View\Components\Link::resolve(['href' => route('register')] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('artisanpack-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\ArtisanPack\LivewireUiComponents\View\Components\Link::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:navigate' => true]); ?><?php echo e(__('Sign up')); ?> <?php echo $__env->renderComponent(); ?>
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
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div><?php /**PATH /Users/jacobmartella/Herd/artisanpack-ui-docs/resources/views/livewire/auth/login.blade.php ENDPATH**/ ?>