<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Los tests de Feature corren dentro del contenedor de Laravel (TestCase),
| de modo que tienen acceso a la aplicación, la base de datos y el HTTP kernel.
| Los tests de Unit se mantienen puros, sin levantar el framework.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
