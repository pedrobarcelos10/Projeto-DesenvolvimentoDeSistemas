<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('painel', 'pages::painel')->name('painel');

    Route::livewire('chamados', 'pages::chamados.index')->name('chamados.index');
    Route::livewire('chamados/novo', 'pages::chamados.novo')->name('chamados.novo');
    Route::livewire('chamados/{chamado}', 'pages::chamados.exibir')
        ->whereNumber('chamado')
        ->name('chamados.exibir');
});

require __DIR__.'/settings.php';
