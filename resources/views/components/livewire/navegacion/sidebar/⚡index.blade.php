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
                'icon' => 'shield-check',
                'active' => $this->routeIs('seguridad.*', 'usuarios.*', 'roles.*', 'permisos.*'),
                'items' => [
                    ['label' => 'USUARIOS', 'href' => '#', 'active' => $this->routeIs('usuarios.*')],
                    ['label' => 'ROLES', 'href' => '#', 'active' => $this->routeIs('roles.*')],
                    ['label' => 'PERMISOS', 'href' => route('permisos.index'), 'active' => $this->routeIs('permisos.*')],
                ],
            ],
            [
                'label' => 'MANTENEDORES',
                'icon' => 'folders',
                'active' => $this->routeIs(
                    'mantenedores.*',
                    'sedes.*',
                    'colegios.*',
                    'tipos-documento.*',
                    'tipos-expediente.*',
                    'turnos.*'
                ),
                'items' => [
                    ['label' => 'SEDES', 'href' => '#', 'active' => $this->routeIs('sedes.*')],
                    ['label' => 'COLEGIOS', 'href' => '#', 'active' => $this->routeIs('colegios.*')],
                    ['label' => 'TIPOS DE DOCUMENTO', 'href' => '#', 'active' => $this->routeIs('tipos-documento.*')],
                    ['label' => 'TIPOS DE EXPEDIENTE', 'href' => '#', 'active' => $this->routeIs('tipos-expediente.*')],
                    ['label' => 'TURNOS', 'href' => '#', 'active' => $this->routeIs('turnos.*')],
                ],
            ],
            [
                'label' => 'CONF. ACADEMICA',
                'icon' => 'gear-six',
                'active' => $this->routeIs(
                    'configuraciones.*',
                    'periodos.*',
                    'procesos.*',
                    'modalidades.*',
                    'carreras.*',
                    'ofertas.*'
                ),
                'items' => [
                    ['label' => 'PERÍODOS', 'href' => '#', 'active' => $this->routeIs('periodos.*')],
                    ['label' => 'PROCESOS', 'href' => '#', 'active' => $this->routeIs('procesos.*')],
                    ['label' => 'MODALIDADES', 'href' => '#', 'active' => $this->routeIs('modalidades.*')],
                    ['label' => 'CARRERAS', 'href' => '#', 'active' => $this->routeIs('carreras.*')],
                    ['label' => 'OFERTAS', 'href' => '#', 'active' => $this->routeIs('ofertas.*')],
                ],
            ],
            [
                'label' => 'INSCRIPCIONES',
                'icon' => 'clipboard-text',
                'active' => $this->routeIs('inscripciones.*', 'postulantes.*', 'expedientes.*', 'pagos.*'),
                'items' => [
                    ['label' => 'POSTULANTES', 'href' => '#', 'active' => $this->routeIs('postulantes.*')],
                    ['label' => 'INSCRIPCIONES', 'href' => '#', 'active' => $this->routeIs('inscripciones.*')],
                    ['label' => 'PAGOS', 'href' => '#', 'active' => $this->routeIs('pagos.*')],
                    ['label' => 'EXPEDIENTES', 'href' => '#', 'active' => $this->routeIs('expedientes.*')],
                ],
            ],
            [
                'label' => 'REPORTES',
                'icon' => 'chart-bar',
                'active' => $this->routeIs('reportes.*'),
                'items' => [
                    ['label' => 'REPORTES GENERALES', 'href' => '#', 'active' => $this->routeIs('reportes.generales.*')],
                    ['label' => 'REPORTES DE PAGOS', 'href' => '#', 'active' => $this->routeIs('reportes.pagos.*')],
                    ['label' => 'REPORTES ACADÉMICOS', 'href' => '#', 'active' => $this->routeIs('reportes.academicos.*')],
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
                class="flex size-10 items-center justify-center rounded-md border border-zinc-200/80 bg-white shadow-sm shadow-zinc-950/5 dark:border-white/10 dark:bg-zinc-950/70 dark:shadow-black/20"
            >
                <img src="{{ asset('assets/media/logo_unu.png') }}" alt="Logo UNU" class="w-6" />
            </div>
            CEPRE UNU
        </x-slot:header>

        <x-sidebar.item
            label="INICIO"
            href="{{ route('inicio') }}"
            :active="request()->routeIs('inicio')"
            wire:key="menu-inicio"
            icon="house-simple"
        />

        @foreach ($this->gruposMenu() as $grupo)
            <x-sidebar.group
                :label="$grupo['label']"
                :active="$grupo['active']"
                :icon="$grupo['icon']"
                wire:key="{{ 'grupo-'.str($grupo['label'])->slug() }}"
            >
                @foreach ($grupo['items'] as $item)
                    <x-sidebar.sub-item
                        :label="$item['label']"
                        :href="$item['href']"
                        :active="$item['active']"
                        wire:key="{{ 'item-'.str($grupo['label'].'-'.$item['label'])->slug() }}"
                    />
                @endforeach
            </x-sidebar.group>
        @endforeach

        <x-slot:footer>
            <livewire:livewire.navegacion.profile.index :isNavbar="false" />
        </x-slot:footer>
    </x-sidebar.panel>
</x-sidebar>
