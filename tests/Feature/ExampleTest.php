<?php

test('the home page redirects to the admin panel', function () {
    $this->get('/')->assertRedirect('/admin');
});
