<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::app')]
#[Title('INICIO')]
class extends Component
{
    public $usuario;

    public $correo;

    public $descripcion;

    public $carrera = '';

    public array $modulos = [];

    public $programa = null;

    public bool $acepta_terminos = false;

    public bool $activo = true;

    public function save()
    {
        $this->validate([
            'usuario' => 'required|string|max:255',
            'correo' => 'required|email',
            'descripcion' => 'required|string',
            'carrera' => 'required|string',
            'modulos' => 'required|array|min:1',
            'programa' => 'required'
        ]);

        // Simulación de búsqueda de usuario por correo
        $usuario = [
            'usuario' => $this->usuario,
            'correo' => $this->correo,
            'descripcion' => $this->descripcion,
            'carrera' => $this->carrera,
            'modulos' => $this->modulos,
            'programa' => $this->programa,
            'acepta_terminos' => $this->acepta_terminos,
            'activo' => $this->activo
        ];

        dd($usuario);
    }

};
