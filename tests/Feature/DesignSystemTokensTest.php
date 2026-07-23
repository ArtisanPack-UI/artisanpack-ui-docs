<?php

declare(strict_types=1);

it('ships the AP-UI design-system token files in resources/css/tokens', function () {
    $tokensDir = base_path('resources/css/tokens');

    foreach (['colors', 'typography', 'spacing', 'base', 'fonts'] as $token) {
        expect(file_exists("{$tokensDir}/{$token}.css"))
            ->toBeTrue("Expected resources/css/tokens/{$token}.css to exist");
    }
});

it('imports every AP-UI token file from resources/css/app.css', function () {
    $appCss = (string) file_get_contents(base_path('resources/css/app.css'));

    expect($appCss)
        ->toContain("@import './tokens/fonts.css'")
        ->toContain("@import './tokens/colors.css'")
        ->toContain("@import './tokens/typography.css'")
        ->toContain("@import './tokens/spacing.css'")
        ->toContain("@import './tokens/base.css'");
});

it('extends Tailwind v4 @theme so utilities map to AP-UI tokens', function () {
    $appCss = (string) file_get_contents(base_path('resources/css/app.css'));

    expect($appCss)
        ->toContain('@theme')
        ->toContain('--color-base:')
        ->toContain('--color-primary:')
        ->toContain('--font-display:')
        ->toContain('--font-mono:')
        ->toContain('--radius-box:')
        ->toContain('--shadow-glow-gradient:');
});

it('carries the neon triad and dark-first color tokens', function () {
    $colors = (string) file_get_contents(base_path('resources/css/tokens/colors.css'));

    expect($colors)
        ->toContain('--color-primary:   #2962FF')
        ->toContain('--color-secondary: #00E5FF')
        ->toContain('--color-accent:    #E040FB')
        ->toContain('[data-theme="light"]');
});

it('declares Poppins + Space Mono in the typography and fonts tokens', function () {
    $typography = (string) file_get_contents(base_path('resources/css/tokens/typography.css'));
    $fonts = (string) file_get_contents(base_path('resources/css/tokens/fonts.css'));

    expect($typography)
        ->toContain('"Poppins"')
        ->toContain('"Space Mono"');

    expect($fonts)
        ->toContain('fonts.googleapis.com')
        ->toContain('family=Poppins')
        ->toContain('family=Space+Mono');
});

it('freezes the design-system reference bundle under docs/design-system', function () {
    $reference = base_path('docs/design-system');

    expect(is_dir($reference))->toBeTrue();
    expect(file_exists("{$reference}/styles.css"))->toBeTrue();

    foreach (['colors', 'typography', 'spacing', 'base', 'fonts'] as $token) {
        expect(file_exists("{$reference}/tokens/{$token}.css"))
            ->toBeTrue("Expected reference docs/design-system/tokens/{$token}.css to exist");
    }
});
