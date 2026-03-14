<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::app')]
#[Title('INICIO')]
class extends Component {
    //
};
?>

<div class="flex w-full flex-col gap-5">
    <div class="flex flex-col gap-1">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Hola, Usuario 👋</h1>
        <p class="text-zinc-500 dark:text-zinc-400">Bienvenido al Sistema de Gestión de Inscripciones del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU. Selecciona un módulo para comenzar.</p>
    </div>
</div>
