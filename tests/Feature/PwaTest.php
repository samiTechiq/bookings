<?php

test('panel pages include the manifest, icons and service worker registration', function () {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertSee('manifest.json', escape: false)
        ->assertSee('apple-touch-icon.png', escape: false)
        ->assertSee('<meta name="theme-color" content="#0284c7">', escape: false)
        ->assertSee('serviceWorker', escape: false);
});

test('the web manifest is installable and its icons exist', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->toHaveKeys(['name', 'short_name', 'start_url', 'display', 'icons'])
        ->and($manifest['display'])->toBe('standalone');

    $sizes = collect($manifest['icons'])->pluck('sizes');

    expect($sizes)->toContain('192x192')->toContain('512x512')
        ->and(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');

    foreach ([...$manifest['icons'], ...collect($manifest['shortcuts'])->flatMap->icons] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }

    foreach (['sw.js', 'offline.html', 'favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $file) {
        expect(public_path($file))->toBeFile();
    }
});
