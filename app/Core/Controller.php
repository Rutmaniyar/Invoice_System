<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        return View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function backWithErrors(array $errors, array $old = []): never
    {
        Session::flash('errors', $errors);
        Session::flash('_old', $old);
        Response::back();
    }
}
