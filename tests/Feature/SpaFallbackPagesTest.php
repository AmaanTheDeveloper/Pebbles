<?php

test('known storefront paths return the spa shell with http 200', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="root"', false);

    $this->get('/menu')
        ->assertOk()
        ->assertSee('id="root"', false);
});

test('unknown website paths return the spa shell with http 404', function () {
    $this->get('/this-page-definitely-does-not-exist')
        ->assertNotFound()
        ->assertSee('id="root"', false)
        ->assertSee('noindex', false);
});

test('api endpoints still return json not found responses', function () {
    $this->getJson('/api/v1/catalog/products/never-existed-product-xyz')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});

test('unmatched api paths do not render the website 404 shell', function () {
    $this->getJson('/api/v1/this-endpoint-does-not-exist')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json')
        ->assertJson(['message' => 'Not found.'])
        ->assertDontSee('id="root"', false);
});

test('offline fallback asset is publicly available', function () {
    $this->get('/offline.html')
        ->assertOk()
        ->assertSee("You're offline", false)
        ->assertSee('Try Again', false);
});

test('service worker asset is publicly available', function () {
    $this->get('/sw.js')
        ->assertOk()
        ->assertSee('pebbles-offline', false);
});
