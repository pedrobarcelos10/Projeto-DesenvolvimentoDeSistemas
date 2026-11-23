<?php

use App\Livewire\Admin\Categorias;
use App\Livewire\Admin\Estrutura;
use App\Livewire\Admin\Formularios\Editar as EditarFormulario;
use App\Livewire\Admin\Formularios\Index as ListarFormularios;
use App\Livewire\Admin\Usuarios\Formulario as FormularioUsuario;
use App\Livewire\Admin\Usuarios\Index as ListarUsuarios;
use App\Livewire\Chamados\Exibir as ExibirChamado;
use App\Livewire\Chamados\Index as ListarChamados;
use App\Livewire\Chamados\Novo as AbrirChamado;
use App\Livewire\Painel;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('painel', Painel::class)->name('painel');

    Route::livewire('chamados', ListarChamados::class)->name('chamados.index');
    Route::livewire('chamados/novo', AbrirChamado::class)->name('chamados.novo');
    Route::livewire('chamados/{chamado}', ExibirChamado::class)
        ->whereNumber('chamado')
        ->name('chamados.exibir');
});

/*
| Área administrativa: só o administrador ativo (gate `administrar`).
| A criação de contas existe apenas aqui; não há cadastro público.
*/
Route::middleware(['auth', 'verified', 'can:administrar'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('usuarios', ListarUsuarios::class)->name('usuarios.index');
        Route::livewire('usuarios/novo', FormularioUsuario::class)->name('usuarios.novo');
        Route::livewire('usuarios/{usuario}', FormularioUsuario::class)
            ->whereNumber('usuario')
            ->name('usuarios.editar');

        Route::livewire('estrutura', Estrutura::class)->name('estrutura');
        Route::livewire('categorias', Categorias::class)->name('categorias');

        Route::livewire('formularios', ListarFormularios::class)->name('formularios.index');
        Route::livewire('formularios/novo', EditarFormulario::class)->name('formularios.novo');
        Route::livewire('formularios/{formulario}', EditarFormulario::class)
            ->whereNumber('formulario')
            ->name('formularios.editar');
    });

require __DIR__.'/settings.php';
