<?php

namespace App\Traits\InscripcionCrear;

use App\Enums\EstadoCatalogo;
use App\Models\EstadoCivil;
use App\Models\IdentidadEtnica;
use App\Models\LenguaExtranjera;
use App\Models\LenguaNativa;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\TipoColegio;
use App\Models\TipoDiscapacidad;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Catálogos del asistente de inscripción: se consultan una vez y se guardan en el estado del componente.
 *
 * Lo usa el componente pages::inscripciones.inscripcion-crear, que declara
 * el estado (formularios y propiedades) sobre el que trabajan estos métodos.
 */
trait GestionaCatalogosInscripcion
{
    #[Computed]
    public function tiposDocumento(): EloquentCollection
    {
        return $this->hidratarModelos(TipoDocumento::class, $this->tiposDocumentoCatalogo);
    }

    #[Computed]
    public function estadosCiviles(): EloquentCollection
    {
        return $this->hidratarModelos(EstadoCivil::class, $this->estadosCivilesCatalogo);
    }

    #[Computed]
    public function paises(): EloquentCollection
    {
        return $this->hidratarModelos(Pais::class, $this->paisesCatalogo);
    }

    #[Computed]
    public function nacionalidades(): EloquentCollection
    {
        return $this->hidratarModelos(Nacionalidad::class, $this->nacionalidadesCatalogo);
    }

    #[Computed]
    public function identidadesEtnicas(): EloquentCollection
    {
        return $this->hidratarModelos(IdentidadEtnica::class, $this->identidadesEtnicasCatalogo);
    }

    #[Computed]
    public function lenguasNativas(): EloquentCollection
    {
        return $this->hidratarModelos(LenguaNativa::class, $this->lenguasNativasCatalogo);
    }

    #[Computed]
    public function lenguasExtranjeras(): EloquentCollection
    {
        return $this->hidratarModelos(LenguaExtranjera::class, $this->lenguasExtranjerasCatalogo);
    }

    #[Computed]
    public function tiposDiscapacidad(): EloquentCollection
    {
        return $this->hidratarModelos(TipoDiscapacidad::class, $this->tiposDiscapacidadCatalogo);
    }

    #[Computed]
    public function tiposColegio(): EloquentCollection
    {
        return $this->hidratarModelos(TipoColegio::class, $this->tiposColegioCatalogo);
    }

    /**
     * Convierte los registros de un catalogo guardados como arreglos en el
     * estado del componente de vuelta en modelos, sin consultar la base.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $modelo
     * @param  array<int, array<string, mixed>>  $registros
     * @return EloquentCollection<int, TModel>
     */
    private function hidratarModelos(string $modelo, array $registros): EloquentCollection
    {
        return new EloquentCollection(
            array_map(fn (array $registro) => (new $modelo)->forceFill($registro), $registros),
        );
    }

    #[Computed]
    public function paisPeruId(): string
    {
        $paisPeru = $this->paises()
            ->first(function (Pais $pais): bool {
                $codigo = trim((string) ($pais->codigo_pai ?? ''));
                $nombre = Str::of((string) ($pais->nombre_pai ?? ''))
                    ->ascii()
                    ->upper()
                    ->trim()
                    ->value();

                return in_array($codigo, ['1', 'PE'], true)
                    || $nombre === 'PERU';
            });

        return (string) ($paisPeru?->id_pai ?? '');
    }

    private function hidratarCatalogos(): void
    {
        $this->tiposDocumentoCatalogo = TipoDocumento::query()
            ->where('estado_tdo', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tdo')
            ->get(['id_tdo', 'codigo_tdo', 'nombre_tdo', 'longitud_minima_tdo', 'longitud_maxima_tdo'])
            ->map(fn (TipoDocumento $tipoDocumento): array => $tipoDocumento->toArray())
            ->all();

        $this->estadosCivilesCatalogo = EstadoCivil::query()
            ->where('estado_eci', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_eci')
            ->get(['id_eci', 'nombre_eci'])
            ->map(fn (EstadoCivil $estadoCivil): array => $estadoCivil->toArray())
            ->all();

        $this->paisesCatalogo = Pais::query()
            ->where('estado_pai', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_pai')
            ->get(['id_pai', 'codigo_pai', 'nombre_pai'])
            ->map(fn (Pais $pais): array => $pais->toArray())
            ->all();

        $this->nacionalidadesCatalogo = Nacionalidad::query()
            ->where('estado_nac', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_nac')
            ->get(['id_nac', 'nombre_nac'])
            ->map(fn (Nacionalidad $nacionalidad): array => $nacionalidad->toArray())
            ->all();

        $this->identidadesEtnicasCatalogo = IdentidadEtnica::query()
            ->where('estado_iet', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_iet')
            ->get(['id_iet', 'nombre_iet'])
            ->map(fn (IdentidadEtnica $identidadEtnica): array => $identidadEtnica->toArray())
            ->all();

        $this->lenguasNativasCatalogo = LenguaNativa::query()
            ->where('estado_lna', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lna')
            ->get(['id_lna', 'nombre_lna'])
            ->map(fn (LenguaNativa $lenguaNativa): array => $lenguaNativa->toArray())
            ->all();

        $this->lenguasExtranjerasCatalogo = LenguaExtranjera::query()
            ->where('estado_lex', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_lex')
            ->get(['id_lex', 'nombre_lex'])
            ->map(fn (LenguaExtranjera $lenguaExtranjera): array => $lenguaExtranjera->toArray())
            ->all();

        $this->tiposDiscapacidadCatalogo = TipoDiscapacidad::query()
            ->where('estado_tdi', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tdi')
            ->get(['id_tdi', 'nombre_tdi'])
            ->map(fn (TipoDiscapacidad $tipoDiscapacidad): array => $tipoDiscapacidad->toArray())
            ->all();

        $this->tiposColegioCatalogo = TipoColegio::query()
            ->where('estado_tco', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tco')
            ->get(['id_tco', 'codigo_tco', 'nombre_tco'])
            ->map(fn (TipoColegio $tipoColegio): array => $tipoColegio->toArray())
            ->all();
    }
}
