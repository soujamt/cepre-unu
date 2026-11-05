<?php

use Livewire\Component;

new class extends Component
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function gruposMenu(): array
    {
        return [
            [
                'label' => 'SEGURIDAD',
                'icon' => 'check-shield',
                'active' => $this->routeIs('seguridad.*', 'usuarios.*', 'roles.*', 'permisos.*'),
                'items' => [
                    ['label' => 'USUARIOS', 'href' => route('usuarios.index'), 'active' => $this->routeIs('usuarios.*'), 'permiso' => 'usuario_indice'],
                    ['label' => 'ROLES', 'href' => route('roles.index'), 'active' => $this->routeIs('roles.*'), 'permiso' => 'rol_indice'],
                    ['label' => 'PERMISOS', 'href' => route('permisos.index'), 'active' => $this->routeIs('permisos.*'), 'permiso' => 'permiso_indice'],
                ],
            ],
            [
                'label' => 'MANTENEDORES',
                'icon' => 'folder',
                'active' => $this->routeIs('mantenedores.*', 'mantenedor-aulas.*', 'carreras.*', 'mantenedor-colegios.*', 'lugares-inscripcion.*', 'modalidades.*', 'sedes.*', 'tipos-expediente.*'),
                'items' => [
                    [
                        'label' => 'AULAS',
                        'href' => route('mantenedor-aulas.index'),
                        'active' => $this->routeIs('mantenedor-aulas.*'),
                        'permiso' => 'aula_indice',
                    ],
                    [
                        'label' => 'CARRERAS',
                        'href' => route('carreras.index'),
                        'active' => $this->routeIs('carreras.*'),
                        'permiso' => 'carrera_indice',
                    ],
                    [
                        'label' => 'COLEGIOS',
                        'href' => route('mantenedor-colegios.index'),
                        'active' => $this->routeIs('mantenedor-colegios.*'),
                        'permiso' => 'colegio_indice',
                    ],
                    [
                        'label' => 'LUGARES DE INSCRIPCIÓN',
                        'href' => route('lugares-inscripcion.index'),
                        'active' => $this->routeIs('lugares-inscripcion.*'),
                        'permiso' => 'lugar_inscripcion_indice',
                    ],
                    [
                        'label' => 'MODALIDADES',
                        'href' => route('modalidades.index'),
                        'active' => $this->routeIs('modalidades.*'),
                        'permiso' => 'modalidad_indice',
                    ],
                    [
                        'label' => 'SEDES',
                        'href' => route('sedes.index'),
                        'active' => $this->routeIs('sedes.*'),
                        'permiso' => 'sede_indice',
                    ],
                    [
                        'label' => 'TIPOS DE EXPEDIENTE',
                        'href' => route('tipos-expediente.index'),
                        'active' => $this->routeIs('tipos-expediente.*'),
                        'permiso' => 'tipo_expediente_indice',
                    ],
                ],
            ],
            [
                'label' => 'CONF. ACADEMICA',
                'icon' => 'cog',
                'active' => $this->routeIs(
                    'configuraciones.*',
                    'procesos.*',
                    'ofertas.*',
                    'convenios.*',
                    'reglas-pago.*',
                    'requisitos.*',
                    'requisitos-expediente.*',
                    'aulas.*'
                ),
                'items' => [
                    ['label' => 'PROCESOS', 'href' => route('procesos.index'), 'active' => $this->routeIs('procesos.*'), 'permiso' => 'proceso_indice'],
                    ['label' => 'AULAS', 'href' => route('aulas.index'), 'active' => $this->routeIs('aulas.*'), 'permiso' => 'aula_proceso_indice'],
                    ['label' => 'OFERTAS', 'href' => route('ofertas.index'), 'active' => $this->routeIs('ofertas.*'), 'permiso' => 'oferta_indice'],
                    ['label' => 'CONVENIOS', 'href' => route('convenios.index'), 'active' => $this->routeIs('convenios.*'), 'permiso' => 'convenio_indice'],
                    ['label' => 'REGLAS DE PAGO', 'href' => route('reglas-pago.index'), 'active' => $this->routeIs('reglas-pago.*'), 'permiso' => 'regla_pago_indice'],
                    ['label' => 'REQUISITOS', 'href' => route('requisitos.index'), 'active' => $this->routeIs('requisitos.*', 'requisitos-expediente.*'), 'permiso' => 'requisito_expediente_indice'],
                ],
            ],
            [
                'label' => 'INSCRIPCIONES',
                'icon' => 'clipboard-detail',
                'active' => $this->routeIs('inscripciones.*', 'pagos.*'),
                'items' => [
                    ['label' => 'INSCRIPCIONES', 'href' => route('inscripciones.index'), 'active' => $this->routeIs('inscripciones.*'), 'permiso' => 'inscripcion_indice'],
                    ['label' => 'PAGOS', 'href' => route('pagos.index'), 'active' => $this->routeIs('pagos.index'), 'permiso' => 'pago_indice'],
                    ['label' => 'VALIDAR PAGOS', 'href' => route('pagos.validar'), 'active' => $this->routeIs('pagos.validar'), 'permiso' => 'pago_crear'],
                ],
            ],
            [
                'label' => 'REPORTES',
                'icon' => 'bar-chart',
                'active' => $this->routeIs('reportes.*'),
                'items' => [
                    [
                        'label' => 'REP. INSCRIPCIONES',
                        'href' => route('reportes.inscripciones.index'),
                        'active' => $this->routeIs('reportes.inscripciones.*'),
                        'permiso' => 'reporte_inscripcion_indice',
                    ],
                ],
            ],
        ];
    }

    private function routeIs(string ...$patterns): bool
    {
        return request()->routeIs(...$patterns);
    }
};
?>

<x-sidebar>
    <x-sidebar.panel>
        <x-slot:header>
            <div
                class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]">
                <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-6" />
            </div>
            <div class="min-w-0 leading-tight">
                <p class="text-[15px] font-semibold text-zinc-900 dark:text-white">CEPRE UNU</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">Centro Preuniversitario</p>
            </div>
        </x-slot:header>

        <x-sidebar.item label="INICIO" href="{{ route('inicio') }}" :active="request()->routeIs('inicio')"
            wire:key="menu-inicio" icon="home" />

        @foreach ($this->gruposMenu() as $grupo)
        @php
        $itemsVisibles = collect($grupo['items'])
        ->filter(fn (array $item): bool => ! isset($item['permiso']) ||
        auth()->user()?->hasPermisoAccion($item['permiso']))
        ->values();
        @endphp

        @if ($itemsVisibles->isNotEmpty())
        <x-sidebar.group :label="$grupo['label']" :active="$grupo['active']" :icon="$grupo['icon']"
            wire:key="{{ 'grupo-'.str($grupo['label'])->slug() }}">
            @foreach ($itemsVisibles as $item)
            <x-sidebar.sub-item :label="$item['label']" :href="$item['href']" :active="$item['active']"
                wire:key="{{ 'item-'.str($grupo['label'].'-'.$item['label'])->slug() }}" />
            @endforeach
        </x-sidebar.group>
        @endif
        @endforeach

        <x-slot:footer>
            <livewire:livewire.navegacion.profile.index :isNavbar="false" />
        </x-slot:footer>
    </x-sidebar.panel>
</x-sidebar>
